var L, ra, OsGridRef;
if (typeof (ra) === "undefined") {
    ra = {};
}
ra.map = (function () {
    var my = {};
    my.getMapLink = function (latitude, longitude, $text) {
        var $code = "http://maps.google.com/maps?z=13&amp;t=h&amp;ll=[lat],[long]";
        $code = $code.replace("[lat]", latitude, $code);
        $code = $code.replace("[long]", longitude, $code);
        return   "<span class='mappopup' onClick=\"javascript:window.open('" + $code + "', '_blank','toolbar=yes,scrollbars=yes,left=50,top=50,width=800,height=600');\">[" + $text + "]</span>";
    };
    my.helpBase = "https://maphelp7.ramblers-webs.org.uk/";
    my.helpNaismith = "https://maphelp7.ramblers-webs.org.uk/naismith.html";
    my.icon = (function () {
        var icon = {};
        icon.postcode = function () {
            if (typeof (icon._postcode) === "undefined") {
                icon._postcode = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/postcode-icon.png',
                    iconSize: [24, 18], // size of the icon
                    shadowSize: [26, 20], // size of the shadow
                    iconAnchor: [12, 9], // point of the icon which will correspond to marker's location
                    shadowAnchor: [0, 0], // the same for the shadow
                    popupAnchor: [0, -12] // point from which the popup should open relative to the iconAnchor
                });
            }
            return icon._postcode;
        };
        icon.postcodeClosest = function () {
            if (typeof (icon._postcodeClosest) === "undefined") {
                icon._postcodeClosest = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/postcode-icon-closest.png',
                    iconSize: [24, 18], // size of the icon
                    shadowSize: [26, 20], // size of the shadow
                    iconAnchor: [12, 9], // point of the icon which will correspond to marker's location
                    shadowAnchor: [0, 0], // the same for the shadow
                    popupAnchor: [0, -12] // point from which the popup should open relative to the iconAnchor
                });
            }
            return icon._postcodeClosest;
        };
        icon.redmarker = function () {
            if (typeof (icon._redmarker) === "undefined") {
                icon._redmarker = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/redmarker.png',
                    iconSize: [32, 32], // size of the icon
                    shadowSize: [26, 20], // size of the shadow
                    iconAnchor: [16, 32], // point of the icon which will correspond to marker's location
                    shadowAnchor: [0, 0], // the same for the shadow
                    popupAnchor: [0, -12] // point from which the popup should open relative to the iconAnchor
                });
            }
            return icon._redmarker;
        };
        icon.s0 = function () {
            if (typeof (icon._s0) === "undefined") {
                icon._s0 = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/rejected.png',
                    iconSize: [15, 15]
                });
            }
            return icon._s0;
        };
        icon.s1 = function () {
            if (typeof (icon._s1) === "undefined") {
                icon._s1 = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/1star.png',
                    iconSize: [19, 19]
                });
            }
            return icon._s1;
        };
        icon.s2 = function () {
            if (typeof (icon._s2) === "undefined") {
                icon._s2 = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/2star.png',
                    iconSize: [21, 21]
                });
            }
            return icon._s2;
        };
        icon.s3 = function () {
            if (typeof (icon._s3) === "undefined") {
                icon._s3 = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/3star.png',
                    iconSize: [23, 23]
                });
            }
            return icon._s3;
        };
        icon.s4 = function () {
            if (typeof (icon._s4) === "undefined") {
                icon._s4 = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/4star.png',
                    iconSize: [25, 25]
                });
            }
            return icon._s4;
        };
        icon.s5 = function () {
            if (typeof (icon._s5) === "undefined") {
                icon._s5 = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/5star.png',
                    iconSize: [27, 27]
                });
            }
            return icon._s5;
        };
        icon.markerRoute = function () {
            if (typeof (icon._markerRoute) === "undefined") {
                icon._markerRoute = L.icon({
                    iconUrl: ra.baseDirectory() + "media/com_ra_library/images/marker-route.png",
                    iconSize: [30, 40],
                    iconAnchor: [15, 36]
                });
            }
            return icon._markerRoute;
        };
        icon.markerStart = function () {
            if (typeof (icon._markerStart) === "undefined") {
                icon._markerStart = L.icon({
                    iconUrl: ra.baseDirectory() + "media/com_ra_library/images/marker-start.png",
                    iconSize: [35, 35]
                });
            }
            return icon._markerStart;
        };
        icon.markerFinish = function () {
            if (typeof (icon._markerFinish) === "undefined") {
                icon._markerFinish = L.icon({
                    iconUrl: ra.baseDirectory() + "media/com_ra_library/images/marker-finish.png",
                    iconSize: [28, 28]
                });
            }
            return icon._markerFinish;
        };
        icon.markerArea = function () {
            if (typeof (icon._markerArea) === "undefined") {
                icon._markerArea = L.icon({
                    iconUrl: ra.baseDirectory() + "media/com_ra_library/images/marker-area.png",
                    iconSize: [35, 35]
                });
            }
            return icon._markerArea;
        };
        icon.markerCancelled = function () {
            if (typeof (icon._markerCancelled) === "undefined") {
                icon._markerCancelled = L.icon({
                    iconUrl: ra.baseDirectory() + "media/com_ra_library/images/marker-cancelled.png",
                    iconSize: [35, 35]
                });
            }
            return icon._markerCancelled;
        };
        icon.walkingarea = function () {
            if (typeof (icon._walkingarea) === "undefined") {
                icon._walkingarea = L.icon({
                    iconUrl: ra.baseDirectory() + "media/com_ra_library/images/area.png",
                    iconSize: [40, 35]
                });
            }
            return icon._walkingarea;
        };
        icon.event = function () {
            if (typeof (icon._event) === "undefined") {
                icon._event = L.icon({
                    iconUrl: ra.baseDirectory() + "media/com_ra_library/images/grades/event.png",
                    iconSize: [40, 35]
                });
            }
            return icon._event;
        };
        icon.walkinggroup = function () {
            if (typeof (icon._walkinggroup) === "undefined") {
                icon._walkinggroup = L.icon({
                    iconUrl: ra.baseDirectory() + "media/com_ra_library/images/group.png",
                    iconSize: [40, 35]
                });
            }
            return icon._walkinggroup;
        };
        icon.walkingspecial = function () {
            if (typeof (icon._walkingspecial) === "undefined") {
                icon._walkingspecial = L.icon({
                    iconUrl: ra.baseDirectory() + "media/com_ra_library/images/specialgroup.png",
                    iconSize: [40, 35]
                });
            }
            return icon._walkingspecial;
        };
        icon.grade = function (nationalGrade) {
            var icon = null;
            switch (nationalGrade) {
                case "Easy Access":
                    icon = L.icon({
                        iconUrl: ra.baseDirectory() + "media/com_ra_library/images/grades/e.png",
                        iconSize: [40, 35]
                    });
                    break;
                case "Easy":
                    icon = L.icon({
                        iconUrl: ra.baseDirectory() + "media/com_ra_library/images/grades/ea.png",
                        iconSize: [40, 35]
                    });
                    break;
                case "Leisurely":
                    icon = L.icon({
                        iconUrl: ra.baseDirectory() + "media/com_ra_library/images/grades/l.png",
                        iconSize: [40, 35]
                    });
                    break;
                case "Moderate":
                    icon = L.icon({
                        iconUrl: ra.baseDirectory() + "media/com_ra_library/images/grades/m.png",
                        iconSize: [40, 35]
                    });
                    break;
                case "Strenuous":
                    icon = L.icon({
                        iconUrl: ra.baseDirectory() + "media/com_ra_library/images/grades/s.png",
                        iconSize: [40, 35]
                    });
                    break;
                case "Technical":
                    icon = L.icon({
                        iconUrl: ra.baseDirectory() + "media/com_ra_library/images/grades/t.png",
                        iconSize: [40, 35]
                    });
                    break;
                default:
                    break;
            }
            return icon;
        };
        icon.setMarker = function (marker, name) {
            var icon1;
            if (name === "") {
                icon1 = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/leaflet/images/marker-icon.png',
                    iconSize: [25, 41], // size of the icon
                    iconAnchor: [12, 41],
                    popupAnchor: [0, -41]
                });
                marker.setIcon(icon1);
                return;
            }

            marker.file = ra.baseDirectory() + "media/com_ra_library/gpxsymbols/" + name + ".png";
            var file = ra.baseDirectory() + "?option=com_ra_library&view=checkimage&format=json&file=" + name + ".png";
            ra.ajax.getUrl(file, "", marker, function (marker, response) {
                var url = marker.file;
                var icon1;
                response = JSON.parse(response);
                marker.file = null;
                if (response.data.exists) {
                    icon1 = L.icon({
                        iconUrl: url,
                        iconSize: [32, 37],
                        iconAnchor: [16, 37],
                        popupAnchor: [0, -41]
                    });

                } else {
                    icon1 = L.icon({
                        iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/redmarker.png',
                        iconSize: [36, 41], // size of the icon
                        iconAnchor: [18, 41],
                        popupAnchor: [0, -41]
                    });
                }
                marker.setIcon(icon1);
            });
        };
        icon._setIcon = function (marker, response) {
            var url = marker.file;
            var icon1;
            marker.file = null;
            if (response === 'true') {
                icon1 = L.icon({
                    iconUrl: url,
                    iconSize: [32, 37],
                    iconAnchor: [16, 37],
                    popupAnchor: [0, -41]
                });
            } else {
                icon1 = L.icon({
                    iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/redmarker.png',
                    iconSize: [36, 41], // size of the icon
                    iconAnchor: [18, 41],
                    popupAnchor: [0, -41]
                });
            }
            marker.setIcon(icon1);
        };
        return icon;
    }
    ());
    my.os = (function () {
        var os = {};
        os.display = function (map, layer) {
            var boundstr = "Bounds: <br/>";
            for (j = 0; j < map.bounds.length; j++) {
                var rect;
                var bounds = map.bounds[j];
                var pt1 = os._getPointInfo(bounds.eastingmin, bounds.northingmin);
                var pt2 = os._getPointInfo(bounds.eastingmax, bounds.northingmax);
                var area = [pt1.latlng, pt2.latlng];
                boundstr += pt1.gr + " to " + pt2.gr + "<br/>";
            }
            //  console.log(map.type + " " + map.number);
            //  console.log(boundstr);
            for (j = 0; j < map.bounds.length; j++) {
                var rect;
                var bounds = map.bounds[j];
                var pt1 = os._getPointInfo(bounds.eastingmin, bounds.northingmin);
                var pt2 = os._getPointInfo(bounds.eastingmin, bounds.northingmax);
                var pt3 = os._getPointInfo(bounds.eastingmax, bounds.northingmax);
                var pt4 = os._getPointInfo(bounds.eastingmax, bounds.northingmin);
                var area = [pt1.latlng, pt2.latlng, pt3.latlng, pt4.latlng];
                if (map.scale === "50000") {
                    rect = L.polygon(area, {color: "#ff0000", weight: 1});
                } else {
                    rect = L.polygon(area, {color: "#ff7800", weight: 1});
                }
                var msg = "<h4>" + map.type + " " + map.number + "</h4>";
                msg += "<p>" + map.title + "</p>";
                msg += "<p>Scale: 1:" + map.scale.substr(0, 2) + "k</p>";
                msg += boundstr;
                rect.bindPopup(msg);
                layer.addLayer(rect);
            }
        };
        os._getPointInfo = function (east, north) {
            var value = {};
            var os = new OsGridRef(east, north);
            var pt = OsGridRef.osGridToLatLon(os);
            value.latlng = L.latLng(pt.lat, pt.lon);
            value.gr = os.toString(6);
            return value;
        };
        os.getSquarePixelSize = function (map, meters) {
            var zoom = map.getZoom();
            var centre = map.getCenter();
            var metersPerPixel = 156543.03392 * Math.cos(centre.lat * Math.PI / 180) / Math.pow(2, zoom);
            var pixelSize = meters / metersPerPixel; // 10,000 meters = 10km
            return pixelSize; // Returns width/height in pixels for 10km at this zoom/latitude
        };
        os.gridLevel = function (map, size) {
            var sq1km = os.getSquarePixelSize(map, 1000);
            if (sq1km / 100 > size) {
                return 8;
            }
            if (sq1km / 10 > size) {
                return 6;
            }
            if (sq1km > size) {
                return 4;
            }
            if (sq1km * 10 > size) {
                return 2;
            }
            if (sq1km * 100 > size) {
                return 0;
            }
            return 0;
        };

        os.getOSMapsAtLoc = function (lat, lng, callback) {
            var p = new LatLon(lat, lng);
            var grid = OsGridRef.latLonToOsGrid(p);
            var result = {error: false,
                errorMsg: "",
                maps: []};
            if (grid.toString(6) !== "") {
                var east = Math.round(grid.easting);
                var north = Math.round(grid.northing);
                var url = "https://osmaps.theramblers.org.uk/index.php?easting=" + east + "&northing=" + north;
                ra.ajax.getJSON(url, function (err, items) {
                    if (err !== null) {
                        result.error = true;
                        result.errorMsg = "Error: Something went wrong: " + err;
                    } else {
                        if (items.length !== 0) {
                            items.forEach(item => {
                                result.maps.push(item);
                            });
                        } else {
                            result.errorMsg = "No Ordnance Survey Maps found for this location";
                        }
                    }
                    callback(result);
                });
            } else {
                result.errorMsg = "Location outside OS Grid";
                callback(result);
            }
        };
        return os;
    }
    ());
    my.getGPXDistance = function (distance) { // distance in metres
        var dist, miles;
        dist = ra.units.metresTokm(distance);
        miles = ra.units.metresToMi(distance);
        return dist.toFixed(1) + ' km / ' + miles.toFixed(2) + 'mi';
    };
    my.getLngLats = function (latlngs) {
        var i = 0;
        var lnglats = [];
        var len = latlngs.length;
        for (i = 0; i < len; i++) {
            // change to long/lat rather than lat/long
            lnglats[i] = [];
            lnglats[i][0] = latlngs[i].lng;
            lnglats[i][1] = latlngs[i].lat;
        }
        return lnglats;
    };
    my.getLatlngs = function (lnglats) {
        var latlngs = [];
        for (const lnglat of lnglats) {
            var latlng = L.latLng(lnglat[1], lnglat[0]);
            latlngs.push(latlng);
        }
        return latlngs;
    };
    // remove segments less than 2 metres
    my.removeShortSegments = function (latlngsOrig) {
        var latlngs = [];
        var last = null;
        var dist;
        for (const latlng of latlngsOrig) {
            if (last !== null) {
                dist = last.distanceTo(latlng);
                if (dist > 2) {
                    latlngs.push(latlng);
                }
            } else {
                latlngs.push(latlng);
            }
            last = latlng;
        }
        return latlngs;
    };
    my.addPostcodeIcon = function (pc, latlng, layer) {
        var icon = my.icon.postcode();
        var marker = L.marker(latlng, {icon: icon}).addTo(layer);
        marker.bindPopup(pc);
        return marker;
    };
    return my;
}
());
if (typeof (ra.data) === "undefined") {
    ra.data = {};
}
if (typeof (ra.data.location) === "undefined") {
    ra.data.location = {};
    ra.data.location.found = false;
    ra.data.location.latitude = 0;
    ra.data.location.longitude = 0;
    ra.data.location.accuracy = 99999;
}

ra.loc = (function () {
    var loc = {};
    loc.directionsSpan = function ($lat, $long) {
        return "<a class='mappopup' href='javascript:ra.loc.directions(" + $lat + "," + $long + ")' >[Directions]</a>";
    };
    loc.directions = function ($lat, $long) {
        var page = -'';
        if (ra.data.location.found) {
            var myloc = ra.data.location;
            if (ra.data.location.accuracy > 500) {
                ra.showMsg("Unable to accurately obtain your location.\nYou may need to tell Google your true location.");
            }
            page = "https://maps.google.com?saddr=" + myloc.latitude.toString() + "," + myloc.longitude.toString() + "&daddr=" + $lat.toString() + "," + $long.toString();
        } else {
            ra.showMsg("Sorry - Unable to find your location, we will ask Google to try");
            page = "https://www.google.com/maps/dir/Current+Location/" + $lat.toString() + "," + $long.toString();
        }
        // console.log(page);
        window.open(page, "_blank", "scrollbars=yes,width=900,height=580,menubar=yes,resizable=yes,status=yes");
    };
    loc.setPosition = function (e) {
        ra.data.location.found = true;
        ra.data.location.latitude = e.latitude;
        ra.data.location.longitude = e.longitude;
        ra.data.location.accuracy = e.accuracy;
    };
    loc.setPositionError = function (e) {
        ra.data.location.found = false;
        ra.data.location.latitude = 0;
        ra.data.location.longitude = 0;
        ra.data.location.accuracy = 999999;
    };
    return loc;
}
());
/**
 * Convert lat/long to string
 * @param  {Number} lat value of latutude
 * @param  {Number} long value of latutude
 * @return {string} degrees minutes seconds string in the format 49°15'51.35"N4 9°15'51.35"E
 */
ra.latlongDecToDms = function (lat, long) {

    return convert(lat, false) + "  " + convert(long, true);
    /**
     * Converts decimal degrees to degrees minutes seconds.
     * 
     * @param dd the decimal degrees value.
     * @param isLng specifies whether the decimal degrees value is a longitude.
     * @return degrees minutes seconds string in the format 49°15'51.35"N
     */
    function convert(dd, isLng) {
        var dir = dd < 0
                ? isLng ? 'W' : 'S'
                : isLng ? 'E' : 'N';
        var absDd = Math.abs(dd);
        var deg = absDd | 0;
        var frac = absDd - deg;
        var min = (frac * 60) | 0;
        var sec = frac * 3600 - min * 60;
        // Round it to 2 decimal points.
        sec = Math.round(sec * 100) / 100;
        return deg + "°" + min.toString().padStart(2, '0') + "'" + sec.toString().padStart(5, '0') + '"' + dir;
    }

};
ra.map.cluster = function (map) {
    var body = document.getElementsByTagName("BODY")[0];
    this.progressBar = document.createElement('div');
    this.progressBar.setAttribute('class', 'ra-cluster-progress-bar');
    body.appendChild(this.progressBar);
    this._map = map;
    this.markersCG = new L.MarkerClusterGroup({chunkedLoading: true,
        chunkProgress: this.updateClusterProgressBar,
        maxClusterRadius: 50});
    this.markerList = [];
    this.progressBar.style.display = "none";
    this.markersCG.addLayers(this.markerList);
    this._map.addLayer(this.markersCG);
    this.updateClusterProgressBar = function (processed, total, elapsed) {
        if (elapsed > 1000) {
// if it takes more than a second to load, display the progress bar:
            this.progressBar.innerHTML = "Loading: " + Math.round(processed / total * 100) + "%";
            this.progressBar.style.display = "block";
        }
        if (processed === total) {
            this.progressBar.style.display = "none"; // all markers processed - hide the progress bar:
        }
    };
    this.removeClusterMarkers = function () {
        this.markersCG.removeLayers(this.markerList);
        this.markerList = [];
    };
    this.addClusterMarkers = function () {
        this.markersCG.addLayers(this.markerList);
    };
    this.zoomAll = function (options = {padding: [20, 20]}) {
        if (this.markerList.length > 0) {
            var bounds = this._getBounds(this.markerList);
            this._map.fitBounds(bounds, options);
    }
    };
    this.zoomMarker = function (marker) {
        this._map.panTo(marker.getLatLng());
        this.markersCG.zoomToShowLayer(marker, () => {
            marker.openPopup();
        });
    };
    this._getBounds = function (list) {
        var bounds = new L.LatLngBounds();
        var marker, i;
        for (i = 0; i < list.length; i++) {
            marker = list[i];
            bounds.extend(marker.getLatLng());
        }
        return bounds;
    };
    this.addMarker = function ($popup, $lat, $long, markeroptions = {}) {
        var marker = L.marker([$lat, $long], markeroptions);
        marker.bindPopup($popup, {offset: new L.Point(0, -20)});
        this.markerList.push(marker);
        return marker;
    };
};