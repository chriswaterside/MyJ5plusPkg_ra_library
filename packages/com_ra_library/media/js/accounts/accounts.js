var ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.display) === "undefined") {
    ra.display = {};
}
 ra.display.accountsMap = function (options, data) {
        var masterdiv = document.getElementById(options.divId);
        this.lmap = new ra.leafletmap(masterdiv, options);
        this.lmap.display();
        this.cluster = new ra.map.cluster(this.lmap.map);
        this.data = data;
        this.load = function () {
            this.addMarkers(this.data.hostedsites);
            //    this.addMarkers(data.groups);
            this.cluster.addClusterMarkers();
            this.cluster.zoomAll();
        };
        this.addMarkers = function (websites) {
            var website;
            for (var i = 0; i < websites.length; i++) {
                website = websites[i];
                this.addMarker(website);

            }
        };
        this.addMarker = function (item) {
            if (item.status === "DELETED") {
                return;
            }
            var $popup, $lat, $long, $iclass, $icon;
            var $url, $lat, $long, $area, $group, $code, $text;
            $long = item.longitude;
            $lat = item.latitude;
            $url = "https://" + item.domain;
            $code = " [" + item.code + "]";

            $iclass = "group-icon g";
            $area = item.areaname.replace("'", "");
            if (item.code.length === 2) {
                $group = $area;
                $iclass = "group-icon a";
                $class = "groupA";
                $text = "<h4>Area: " + $area + " [" + item.code + "]</h4>";
            } else {
                $group = item.groupname.replace("'", "&apos;");
                $iclass = "group-icon g";
                $class = "groupG";
                $text = "<h4>Group: " + $group + " [" + item.code + "]</h4>";
                $text += "<h5>Part of Area: " + $area + "</h5>";

            }

            var $class = "group" + item.scope;
            $popup = "<div class='" + $class + "'>" + $text + "Website status: " + item.status + "</div>";
            if (item.status) {
                $popup += "Domain name: <a href='" + $url + "' target='_blank'>" + $url + "</a>";
            } else {
                $popup += "<p>" + $url + "</p>";
            }
            $icon = L.divIcon({className: $iclass, iconSize: null, html: $group, popupAnchor: [10, 30]});

            this.cluster.addMarker($popup, $lat, $long, {icon: $icon, riseOnHover: true, title: $group});

        };
    };
    
