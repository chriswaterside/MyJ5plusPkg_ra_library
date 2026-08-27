var ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.display) === "undefined") {
    ra.display = {};
}
ra.display.organisationMap = function (options, data) {

    var masterdiv = document.getElementById(options.divId);
    this.lmap = new ra.leafletmap(masterdiv, options);
    this.lmap.display();
    this.cluster = new ra.map.cluster(this.lmap.map);
    this.data = data;
    this.load = function () {
        this.addMarkers(this.data.areas);
        //    this.addMarkers(data.groups);
        this.cluster.addClusterMarkers();
        this.cluster.zoomAll();
    };
    this.addMarkers = function (areas) {
        var area, group;
        for (const name in areas) {
            area = areas[name];
            this.addMarker(area);
            for (const groupname in area.groups) {
                group = area.groups[groupname];
                this.addMarker(group, area);
            }
        }
    };
    this.addMarker = function (item, area) {
        var $popup, $lat, $long, $iclass, $areatext = "", $icon, $website;
        var $url, $lat, $long, $desc, $title, $code;


        $title = item.name.replace("'", "&apos;");


        $long = item.longitude;
        $lat = item.latitude;
        $url = item.url;
        $website = item.website;
        //  $desc = "<br/>"+htmlentities(item.description);
        $desc = "<br/>" + item.description;
        $desc = $desc.replace("\n", '<br />');
        $desc = $desc.replace("\r", '');
        $code = " [" + item.code + "]";
        switch (item.scope) {
            case "A":
                $iclass = "group-icon a";
                break;
            case "G":
                $iclass = "group-icon g";
                $areatext = "Part of " + area.name + " area";
                break;
            default:
                $iclass = "group-icon s";
                $areatext = "Part of " + area.name + " area";
                break;
        }
        var $class = "group" + item.scope;
        $popup = "<div class='" + $class + "'><h4><a href='" + $url + "' target='_blank'>" + $title + "</a>" + $code + "</h4>" + $areatext + $desc + "</div>";
        if ($website) {
            $popup += "<a href='" + $website + "' target='_blank'>" + $website + "</a>";
        }
        $icon = L.divIcon({className: $iclass, iconSize: null, html: $title, popupAnchor: [10, 30]});

        this.cluster.addMarker($popup, $lat, $long, {icon: $icon, riseOnHover: true, title: $title});

    };


};
ra.display.organisationMyGroup = function (options, data) {

    var masterdiv = document.getElementById(options.divId);

    this.data = data;
    this.colours = data.colours;
    this.lmap = new ra.leafletmap(masterdiv, options);
    this.groupsLayer = L.featureGroup([]).addTo(this.lmap.map);
    this.otherAreasLayer = L.featureGroup([]).addTo(this.lmap.map);
    this.boundaryLayer = L.featureGroup([]).addTo(this.lmap.map);
    this.myAreaLayer = L.featureGroup([]).addTo(this.lmap.map);


    this.load = function () {
        this.addAreas();

        //    this.cluster.zoomAll();
    };
    this.addAreas = function () {
        var area, key;
        for (const key in this.data.areas) {
            area = this.data.areas[key];
            //    marker = this.getMarker(area);
            if (area.code === this.data.myArea) {
                this.displayMyArea(area);
            } else {
                this.displayOtherAreas(area);
            }
        }
    };
    this.displayMyArea = function (myArea) {
        var _this = this;
        this.displayBoundary(myArea, this.myAreaLayer);
        var marker = this.getMarker(myArea);
        marker.addTo(this.myAreaLayer);
        var _this = this;
        marker.addEventListener("mouseover", function (e) {
            _this.groupsLayer.clearLayers();
            _this.clearBoundarys();
        });
        for (const key in myArea.groups) {
            var group = myArea.groups[key];
            if (group.code !== this.data.myGroup) {
                var marker = this.getMarker(group);
                marker.addTo(this.myAreaLayer);
                marker.ra = {};
                marker.ra.group = group;
                _this.addGroupHover(marker);
            } else {
                this.displayBoundary(group, this.myAreaLayer);
                this.lmap.map.setView([group.latitude, group.longitude], this.data.zoom);
                var marker = this.getMarker(group);
                marker.addTo(this.myAreaLayer);
                var _this = this;
                marker.addEventListener("mouseover", function (e) {
                     _this.groupsLayer.clearLayers();
                    _this.clearBoundarys();
                });
            }
        }



    };
    this.displayOtherAreas = function (area) {
        var marker = this.getMarker(area);
        marker.addTo(this.otherAreasLayer);
        marker.ra = {};
        marker.ra.area = area;
        var _this = this;
        marker.addEventListener("mouseover", function (e) {
            _this.groupsLayer.clearLayers();
            var marker = e.target;
            var area = marker.ra.area;
            _this.clearBoundarys();
            _this.displayBoundary(area, _this.boundaryLayer);
            for (const key in area.groups) {
                var group = area.groups[key];
                var gMarker = _this.getMarker(group);
                gMarker.addTo(_this.groupsLayer);
                gMarker.ra = {};
                gMarker.ra.group = group;
                _this.addGroupHover(gMarker);

            }

        });
    };
    this.clearBoundarys = function () {
        this.boundaryLayer.clearLayers();
    };
    this.addGroupHover = function (marker) {
        var _this = this;

        marker.addEventListener("mouseover", function (e) {
            marker = e.target;
            var group = marker.ra.group;
            _this.clearBoundarys();
            _this.displayBoundary(group, _this.boundaryLayer);
        });
    };

    this.displayBoundary = function (group, layer) {

        var data = this.data;
        var colours = this.data.colours;
        var url = "https://www.ramblers.org.uk/LBSData.ashx?type=boundaries&group=" + group.code;
        var color = colours.otherGroups;
        if (group.code.startsWith(data.myArea)) {
            color = colours.myArea;
        }
        if (group.code === data.myGroup) {
            color = colours.myGroup;
        }

        var myStyle = {
            "color": color,
            "weight": 5,
            "opacity": 0.65
        };
        var _layer = layer;
        ra.ajax.getJSON(url, function (status, items) {
            if (status === null) {
                var bLayer = L.geoJSON(items, {
                    style: myStyle
                });
                _layer.addLayer(bLayer);
            } else {
                //error
            }
        });
    };

    this.addMarkers = function (groups) {
        var group;
        for (const group in groups) {
            this.addMarker(groups[group]);
        }
    };
//    this.getAreaMarker = function (item) {
//        var $popup, $lat, $long, $iclass, $areatext = "", $icon, $website;
//        var $url, $lat, $long, $desc, $title, $code;
//        $title = item.name.replace("'", "&apos;");
//        $long = item.longitude;
//        $lat = item.latitude;
//        $url = item.url;
//        $website = item.website;
//        //  $desc = "<br/>"+htmlentities(item.description);
//        $desc = "<br/>" + item.description;
//        $desc = $desc.replace("\n", '<br />');
//        $desc = $desc.replace("\r", '');
//        $code = " [" + item.code + "]";
//        switch (item.scope) {
//            case "A":
//                $iclass = "group-icon a";
//                break;
//            case "G":
//                $iclass = "group-icon g";
//                // $areatext = "Part of " + area.name + " area";
//                break;
//            default:
//                $iclass = "group-icon s";
//                // $areatext = "Part of " + area.name + " area";
//                break;
//        }
//        var $class = "group" + item.scope;
//        $popup = "<div class='" + $class + "'><h4><a href='" + $url + "' target='_blank'>" + $title + "</a>" + $code + "</h4>" + $areatext + $desc + "</div>";
//        if ($website) {
//            $popup += "<a href='" + $website + "' target='_blank'>" + $website + "</a>";
//        }
//        $icon = L.divIcon({className: $iclass, iconSize: null, html: $title, popupAnchor: [10, 30]});
//
//        return  this.getLeafletMarker($popup, $lat, $long, {icon: $icon, riseOnHover: true, title: $title});
//
//    };
    this.getMarker = function (item) {
        var $popup, $lat, $long, $iclass, $areatext = "", $icon, $website;
        var $url, $lat, $long, $desc, $title, $code;
        $title = item.name.replace("'", "&apos;");
        $long = item.longitude;
        $lat = item.latitude;
        $url = item.url;
        $website = item.website;
        //  $desc = "<br/>"+htmlentities(item.description);
        $desc = "<br/>" + item.description;
        $desc = $desc.replace("\n", '<br />');
        $desc = $desc.replace("\r", '');
        $code = " [" + item.code + "]";
        switch (item.scope) {
            case "A":
                $iclass = "group-icon a";
                break;
            case "G":
                $iclass = "group-icon g";
                // $areatext = "Part of " + area.name + " area";
                break;
            default:
                $iclass = "group-icon s";
                // $areatext = "Part of " + area.name + " area";
                break;
        }
        var $class = "group" + item.scope;
        $popup = "<div class='" + $class + "'><h4><a href='" + $url + "' target='_blank'>" + $title + "</a>" + $code + "</h4>" + $areatext + $desc + "</div>";
        if ($website) {
            $popup += "<a href='" + $website + "' target='_blank'>" + $website + "</a>";
        }
        $icon = L.divIcon({className: $iclass, iconSize: null, html: $title, popupAnchor: [10, 30]});


        return  this.getLeafletMarker($popup, $lat, $long, {icon: $icon, riseOnHover: true, title: $title});




    };
    this.getLeafletMarker = function ($popup, $lat, $long, markeroptions = {}) {
        var marker = L.marker([$lat, $long], markeroptions);
        //  var $pop = $popup.replace(/&quot;/g, '"'); // replace quots in popup text
        marker.bindPopup($popup, {offset: new L.Point(0, -20)});
        return marker;
    };

};