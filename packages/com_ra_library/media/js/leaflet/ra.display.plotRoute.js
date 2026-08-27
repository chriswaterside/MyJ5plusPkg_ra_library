var L, ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.display) === "undefined") {
    ra.display = {};
}
ra.display.plotRoute = function (options, data) {
    this._userOptions = {
        userOptionsDEL: false,
        draw: {
            panToNewPoint: true,
            joinSegments: true
        },
        style: {
            color: '#782327',
            weight: 3,
            opacity: 1
        }};
    this.options = options;  //public
    this.masterdiv = document.getElementById(options.divId);
    var lmap = new ra.leafletmap(this.masterdiv, options);
    lmap.display();
    this._map = lmap.map();
    this.controls = {
        rightclick: lmap.rightclickControl(),
        mouse: lmap.mouseControl(),
        //  settingsControl: lmap.settingsControl(),
        elevation: lmap.elevationControl()};
    this.pan = null;
    this.join = null;
    lmap.SetPlotUserControl(this);
    this.preMapDiv = lmap.preMapDiv();

    this.SmartRouteControl = null;
    this.drawnItems = new L.FeatureGroup();
    this._map.addLayer(this.drawnItems);

// code for drawing route/track
    this.load = function () {
        var self = this;

        this.comment1 = '<p><b>Plot a walking Route:</b> No routes or markers defined ';
        this.comment1 += '<br/>If you need help to get started please see the Help, top right of map</p>';
        this.comment1 += '<p><b>NOTE: </b>If you leave this web page, without saving your data, you will lose any routes you have created.</p>';
        this.comment2 = '<br/>If you need help please see the Help, top right of map</p>';
        this.comment2 += '<p><b>NOTE: </b>If you leave this web page, without saving your data, you will lose any routes you have created.</p>';

        L.drawLocal.draw.toolbar.buttons.polyline = 'Plot a walking route(s)';
        L.drawLocal.draw.toolbar.buttons.marker = 'Add a marker';
        L.drawLocal.edit.toolbar.buttons.edit = 'Edit walking route(s) and markers';
        L.drawLocal.edit.toolbar.buttons.editDisabled = 'No routes(s) to edit';
        L.drawLocal.edit.toolbar.buttons.remove = 'Delete walking route(s) or markers';
        L.drawLocal.edit.toolbar.buttons.removeDisabled = 'No route(s) to delete';
        L.drawLocal.edit.handlers.edit.tooltip.subtext = 'Drag line markers to change route.';
        L.drawLocal.edit.handlers.edit.tooltip.text = 'Click darker marker to delete point.';

        // load gpx download control
        this.download = new L.Control.GpxDownload();
        var download = this.download;
        download.setRouteItems(this.drawnItems);
        this._map.addControl(download);
        var upload = new L.Control.GpxUpload();
        this.upload = upload;
        upload.setRouteItems(this.drawnItems);
        this._map.addControl(upload);
        upload.set_polyline_style(this._userOptions.style);
        var reverse = new L.Control.ReverseRoute();
        this.reverse = reverse;
        reverse.setRouteItems(this.drawnItems);
        this._map.addControl(reverse);
        var simplify = new L.Control.GpxSimplify();
        this.simplify = simplify;
        simplify.setRouteItems(this.drawnItems);
        this._map.addControl(simplify);
        this.drawControl = new L.Control.Draw({
            position: 'bottomright',
            draw: {
                polyline: {
                    metric: true,
                    shapeOptions: {
                        color: this._userOptions.style.color,
                        opacity: this._userOptions.style.opacity,
                        weight: this._userOptions.style.weight
                    }
                },
                polygon: false,
                marker: true,
                circle: false,
                circlemarker: false,
                rectangle: false
            },
            edit: {
                featureGroup: this.drawnItems,
                title: 'Edit walking route',
                polyline: {
                    metric: true,
                    shapeOptions: {
                        color: '#007A87',
                        opacity: 1
                    }
                }
            }
            //  color: '#007A87',
        });

        if (options.licenseKeys.ORSkey !== null) {
            // add smart route layer
            this.SmartRouteControl = new L.Control.SmartRoute();
            this.SmartRouteControl.routingKey(options.licenseKeys.ORSkey);
            this.SmartRouteControl.userOptions(this._userOptions);
            this.SmartRouteControl.drawControl(this.drawControl);
            this.SmartRoute = this.SmartRouteControl;
            this.SmartRoute.setRouteItems(this.drawnItems);
        } else {
            this.SmartRouteControl = null;
            this.SmartRoute = {};
            this.SmartRoute.enabled = false;
        }
        //  enableDraw();
        this._map.addControl(this.drawControl);
        if (this.SmartRouteControl !== null) {
            this._map.addControl(this.SmartRouteControl);
        }
        this._map.on(L.Draw.Event.CREATED, function (e) {
            var type = e.layerType,
                    layer = e.layer;
            if (type === 'marker') {
                layer.bindPopup('A popup!');
                var marker = layer;
                marker.name = '';
                marker.desc = '';
                marker.symbol = '';
            }
            self.drawnItems.addLayer(layer);
            self._map.addLayer(self.drawnItems);
            self.addElevations(false);
        });
        this._map.on('browser-print-start', function (e) {
            L.control.scale({
                position: 'topleft',
                imperial: false,
                maxWidth: 200
            }).addTo(e.printMap);
        });
        this.drawnItems.on("upload:addline", function (e) {
            var bounds = self.drawnItems.getBounds();
            self._map.fitBounds(bounds);
            self.setGpxToolStatus('auto');
            self.addElevations(false);
        });
        this.drawnItems.on('upload:addpoint', function (e) {
            if (e.point_type === "waypoint") {
                var marker = e.point;
                var sSymbol = marker.symbol;
                ra.map.icon.setMarker(marker, sSymbol);
            }
        });

        this.drawnItems.on('upload:loaded', function (e) {
            var download = self.download;
            download.set_name(upload.get_name());
            download.set_desc(upload.get_desc());
            download.set_author(upload.get_author());
            download.set_copyright(upload.get_copyright());
            download.set_date(upload.get_date());
            download.set_links(upload.get_links());
        });
        this.drawnItems.on("reverse:reversed", function (e) {
            self.addElevations(false);
        });

        this._map.on('draw:color-change', function (e) {
            // var drawnItems = this.drawnItems;

            self.drawnItems.eachLayer(function (layer) {
                if (layer instanceof L.Polyline) {
                    // update style of lines
                    layer.setStyle(self._userOptions.style);
                }
            });
            self.drawControl.setDrawingOptions({
                polyline: {shapeOptions: self._userOptions.style}});
            if (self.SmartRoute.enabled) {
                self.SmartRoute.setOpacityZero();
            }
            upload.set_polyline_style(self._userOptions.style);

        });
        this._map.on(L.Draw.Event.DRAWSTART, function (e) {
            console.log('DRAW START');
            if (self.SmartRouteControl !== null) {
                self.SmartRouteControl.disable();
            }
            if (self.SmartRoute.enabled) {
                //setOpacityZero();
                // save number of layers so we can see if cancel used
                self.SmartRoute.NoLayers = self.drawnItems.getLayers().length;
                self.SmartRoute.latlngs = null;
                self.SmartRoute.pending = false;
                self.SmartRoute.saveRoute(false);
            }

            self.gridSquarePause();
            self.enableMapMoveDrawing();
            self.setGpxToolStatus('off');
        });
        this._map.on(L.Draw.Event.DRAWVERTEX, function (e) {
            var layer = e.layers._layers;
            if (self.SmartRoute.enabled) {
                console.log('DRAW VERTEX');
                self.SmartRouteControl.displaySmartPoints(layer);
            }
            if (self._userOptions.draw.panToNewPoint) {
                var latlong; //= new L.LatLng(lat, long);
                for (const property in layer) {
                    latlong = layer[property]._latlng;
                }

                self._map.panTo(latlong);
            }
            if (self._userOptions.draw.joinSegments) {

            }
        });

        this._map.on(L.Draw.Event.DRAWSTOP, function (e) {
            if (self.SmartRoute.enabled) {
                console.log('DRAW STOP');
                if (self.SmartRoute.pending) {
                    // Let displaySmartPoints save route
                    self.SmartRoute.saveRoute(true);
                } else {
                    self.SmartRouteControl.saveSmartRoute(self.SmartRouteControl);
                }

            }
            if (self.SmartRouteControl !== null) {
                self.SmartRouteControl.enable();
            }
            //self.displayMouseGridSquare = self.displayMouseGridSquare;
            self.gridSquareResume();
            self.disableMapMoveDrawing();
            if (!self.SmartRoute.enabled) {
                if (self._userOptions.draw.joinSegments) {
                    self._map.fire('join:attach', null);
                }
            }
            self.addElevations(false);
            self.setGpxToolStatus('auto');
            console.log('DRAW EXIT');
        });
        this._map.on('join:attach', function () {
            self.joinLastSegment();
        });
        this._map.on(L.Draw.Event.EDITSTART, function (e) {
            self._map.setMaxZoom(22);
            self.gridSquarePause();
            self.enableMapMoveDrawing();
            self.setGpxToolStatus('off');
        });
        this._map.on(L.Draw.Event.EDITED, function (e) {
            self._map.setMaxZoom(18);
            self.gridSquareResume();
            self.disableMapMoveDrawing();
            self.addElevations(true);
            self.setGpxToolStatus('auto');
        });
        this._map.on(L.Draw.Event.EDITSTOP, function (e) {
            self._map.setMaxZoom(18);
            self.gridSquareResume();
            self.disableMapMoveDrawing();
            self.addElevations(true);
            self.setGpxToolStatus('auto');
        });
        this._map.on(L.Draw.Event.EDITMOVE, function (e) {
        });
        this._map.on(L.Draw.Event.EDITVERTEX, function (e) {
        });
        this._map.on(L.Draw.Event.DELETESTART, function (e) {
            self.gridSquarePause();
            self.enableMapMoveDrawing();
            self.setGpxToolStatus('off');
            if (self.SmartRoute.enabled) {
                self.SmartRouteControl.setOpacityZero();
            }
        });
        this._map.on(L.Draw.Event.DELETESTOP, function (e) {
            self.gridSquareResume();
            self.disableMapMoveDrawing();
            self.listDrawnItems();
            self.resetMetadata();
            self.setGpxToolStatus('auto');
            if (self.SmartRoute.enabled) {
                self.SmartRouteControl.setOpacityZero();
            }
        });
        this._map.on(L.Draw.Event.DELETED, function (e) {
            self.gridSquareResume();
            self.disableMapMoveDrawing();
            self.listDrawnItems();
            self.resetMetadata();
            self.setGpxToolStatus('auto');
            if (self.SmartRoute.enabled) {
                self.SmartRouteControl.setOpacityZero();
            }
        });
        this._map.on('simplify:started', function () {
            self.setGpxToolStatus('off');
            self.disableDraw();
        });
        this._map.on('simplify:saved', function () {
            self.setGpxToolStatus('auto');
            self.addElevations(true);
            self.enableDraw();
        });
        this._map.on('simplify:cancelled', function () {
            self.setGpxToolStatus('auto');
            self.enableDraw();
        });

        this.drawnItems.on('popupopen', function (e) {
            if (self.processPopups === 'off') {
                return;
            }
            var marker = e.popup._source;
            if (typeof (marker) === 'undefined') {
                return;
            }
            self.findMarker(marker);
            if (marker.found) {
                var popup = marker.getPopup();
                var content = '<span><b>Name</b></span><br/><input id="markerName" type="text"/ value="' + marker.name + '" /><br/><span><b>Description<b/></span><br/><textarea id="markerDesc" style="resize:vertical;">' + marker.desc + '</textarea><br/><span><b>Symbol</b></span><br/><input id="markerSymbol" type="text" value="' + marker.symbol + '"/>';
                popup.setContent(content);
            }
        });

        this.drawnItems.on('popupclose', function (e) {
            if (self.processPopups === 'off') {
                return;
            }
            var marker = e.popup._source;
            if (typeof (marker) === 'undefined') {
                return;
            }
            self.findMarker(marker);
            if (marker.found) {
                var popup = marker.getPopup();
                var sName = self._escapeChars(self.getElementValue('markerName'));
                var sDesc = self._escapeChars(self.getElementValue('markerDesc'));
                var sSymbol = self._escapeChars(self.getElementValue('markerSymbol'));
                popup.setContent(sDesc);
                marker.name = sName;
                marker.desc = sDesc;
                marker.symbol = sSymbol;
                marker.title = sName + " - " + sDesc;
                ra.map.icon.setMarker(marker, sSymbol);
                //       marker.fire('revert-edited', {layer: marker});
            }
        });
        this.listDrawnItems();
        this._readSettings();
        window.addEventListener("beforeunload", function (event) {
            event.returnValue = "Write something clever here..";
        });
    };

    this.setGpxToolStatus = function (status) {
        this.processPopups = status;
        this.reverse.setStatus(status);
        this.download.setStatus(status);
        this.simplify.setStatus(status);
        this.upload.setStatus(status);
        if (this.controls.rightclick !== null) {
            this.controls.rightclick.Enabled(status !== 'off');
        }
    };
    this.resetMetadata = function () {
        if (this.drawnItems.getLayers().length === 0) {
            this.download.set_name('');
            this.download.set_desc('');
            this.download.set_author('');
            this.download.set_copyright('');
            this.download.set_date('');
            this.download.set_links([]);
        }
    };
    this.gridSquarePause = function () {
        if (this.controls.mouseControl) {
            this.controls.mouseControl.gridSquarePause();
        }
    };
    this.gridSquareResume = function () {
        if (this.controls.mouseControl) {
            this.controls.mouseControl.gridSquareResume();
        }
    };
    this.findMarker = function (marker) {
        marker.found = false;
        this.drawnItems.eachLayer(function (layer) {
            if (layer instanceof L.Marker) {
                if (layer === marker) {
                    marker.found = true;
                }
            }
        });
    };


    this.listDrawnItems = function () {
        var _this = this;
        var hasItems = this.drawnItems.getLayers().length !== 0;
        this.controls.elevation.clear();
        var text = "";
        if (!hasItems) {
            text = this.comment1;
        } else {
            text += "<table>";
            text += "<tr><th>Segment</th><th>Length</th><th>Elevation Gain</th><th>Est Time</th><th>Number of points</th></tr>";
            this.drawnItems.eachLayer(function (layer) {
                var i = 1;
                if (layer instanceof L.Polyline) {
                    _this.controls.elevation.addData(layer);
                    text += _this.listpath(i, layer.getLatLngs());
                    i += 1;
                }
            });
            text += "</table>";
            text += this.comment2;

        }
        this.preMapDiv.innerHTML = text;
    };
    this.getElementValue = function (id) {
        var node = document.getElementById(id);
        if (node !== null) {
            return node.value;
        }
        return "Invalid";
    };


//Move the map around when we're editing or drawing
    this.enableMapMoveDrawing = function () {
        this._map.on('mousemove', this.mapMoveDrawingMouseMove, this);
    };
    this._escapeChars = function (text) {
        text = text.replace(/&/g, "&amp;"); // do this first so others are not changed
        text = text.replace(/"/g, "&quot;");
        text = text.replace(/'/g, "&apos;");
        text = text.replace(/</g, "&lt;");
        text = text.replace(/>/g, "&gt;");
        return text;
    };
    this.mapMoveDrawingMouseMove = function (e) {
        var mousePos = e.containerPoint;
        var mapSize = this._map.getSize();
        if (mousePos.y <= 20) {
            this._map.panBy([0, -40]);
        } else if (mousePos.y + 20 > mapSize.y) {
            this._map.panBy([0, 40]);
        }

        if (mousePos.x <= 20) {
            this._map.panBy([-40, 0]);
        } else if (mousePos.x + 20 > mapSize.x) {
            this._map.panBy([40, 0]);
        }
    };
    this.disableMapMoveDrawing = function () {
        this._map.off('mousemove', this.mapMoveDrawingMouseMove, this);
    };
    this.disableDraw = function () {
        // do not display, not abled to disable draw buttons!
        var buttons = document.getElementsByClassName("leaflet-draw")[0];
        buttons.style.display = "none";
        if (this.SmartRouteControl !== null) {
            this.SmartRouteControl.disable();
        }
    };
    this.enableDraw = function () {
        var buttons = document.getElementsByClassName("leaflet-draw")[0];
        buttons.style.display = "initial";
        if (this.SmartRouteControl !== null) {
            this.SmartRouteControl.enable();
        }
    };

    this.joinLastSegment = function () {
        var no = this.drawnItems.getLayers().length;
        if (no > 1) {
            var layers = this.drawnItems.getLayers();
            var isLine = layers[no - 1] instanceof L.Polyline;
            if (!isLine) {
                return;
            }
            var nearest = this.findNearestLine(layers, layers[no - 1]);
            if (nearest === null) {
                return;
            }
            var latlngs = [];
            var l1 = nearest.l1;
            var l2 = nearest.l2;

            latlngs = this.getMergedLinePoints(l1, l2);

            var line = new L.Polyline(latlngs, this._userOptions.style);

            this.drawnItems.removeLayer(l1.line);
            this.drawnItems.removeLayer(l2.line);
            this.drawnItems.addLayer(line);
            this.drawnItems.fire('upload:addline', {line: line});
        }
    };
    this.getMergedLinePoints = function (l1, l2) {
        var latlngs;
        if (l1.index > 0 && l2.index === 0) {
            latlngs = l1.line._latlngs.concat(l2.line._latlngs);
            return latlngs;
        }
        if (l1.index === 0 && l2.index > 0) {
            latlngs = l2.line._latlngs.concat(l1.line._latlngs);
            return latlngs;
        }
        if (l1.index > 0 && l2.index > 0) {
            latlngs = l1.line._latlngs.concat(l2.line._latlngs.reverse());
            return latlngs;
        }
        if (l1.index === 0 && l2.index === 0) {
            latlngs = l2.line._latlngs.reverse().concat(l1.line._latlngs);
            return latlngs;
        }
    };
    this.findNearestLine = function (layers, joinLine) {
        var nearest = null;
        var shortest = Number.MAX_VALUE;
        for (var i = 0; i < layers.length - 1; i++) {
            var line = layers[i];
            if (line instanceof L.Polyline) {
                for (var j of [0, line._latlngs.length - 1]) {
                    var pt1 = line._latlngs[j];
                    for (var k of [0, joinLine._latlngs.length - 1]) {
                        var pt2 = joinLine._latlngs[k];
                        var dist = pt2.distanceTo(pt1);
                        if (dist < shortest) {
                            shortest = dist;
                            //  console.log(dist);
                            nearest = {l1: {
                                    line: line,
                                    index: j,
                                    pt: pt1},
                                l2: {
                                    line: joinLine,
                                    index: k,
                                    pt: pt2},
                                distance: dist};
                        }
                    }
                }
            }
        }
        //  console.log(nearest);
        return nearest;
    };

    this.listpath = function (i, latlngs) {
        var dist = this.polylineDistance(latlngs);
        var elevGain = this.polylineElevationGain(latlngs);
        var estTime = ra.math.naismith(dist, elevGain);
        var text = "<tr><td>" + i + "</td><td> " + ra.map.getGPXDistance(dist) + "</td><td>" + elevGain + "m</td><td>" + estTime + "</td><td>" + latlngs.length + "</td></tr>";
        return text;
    };

    this.polylineDistance = function (latlngs) {
        var i, len;
        i = 0;
        len = latlngs.length;
        var tempLatLng = null;
        var totalDistance = 0.00000;
        for (i = 0, len = latlngs.length; i < len; i++) {
            var latlng = latlngs[i];
            if (tempLatLng === null) {
                tempLatLng = latlng;
            } else {
                totalDistance += tempLatLng.distanceTo(latlng);
                tempLatLng = latlng;
            }
        }
        return totalDistance;
    };
    this.polylineElevationGain = function (latlngs) {
        var i, len;
        i = 0;
        len = latlngs.length;
        var tempLatLng = null;
        var elevationGain = 0.00000;
        for (i = 0, len = latlngs.length; i < len; i++) {
            var latlng = latlngs[i];
            if (tempLatLng === null) {
                tempLatLng = latlng;
            } else {
                if (latlng.alt > tempLatLng.alt) {
                    elevationGain += latlng.alt - tempLatLng.alt;
                }
                tempLatLng = latlng;
            }
        }
        return  elevationGain;
    };
    this.addElevations = function (force) {
        this.preMapDiv.innerHTML = "<br/><h4>Fetching elevations - please wait...</h4>";
        var hasItems = this.drawnItems.getLayers().length !== 0;
        if (hasItems) {
            var _this = this;
            this.drawnItems.eachLayer(function (layer) {
                if (layer instanceof L.Polyline) {
                    _this.addElevation(layer.getLatLngs());
                }
            });
        }
        this.getElevations(force);
    };
    this.addElevation = function (latlngs) {
        var i, len;
        i = 0;
        len = latlngs.length;
        for (i = 0, len = latlngs.length; i < len; i++) {
            if (typeof (latlngs[i].alt) === 'undefined') {
                // latlngs[i].meta = {ele: null};
                latlngs[i].alt = -999;
            }
        }
        return;
    };
    this.getElevations = function (force) {
        var _this = this;
        var hasItems = this.drawnItems.getLayers().length !== 0;
        var nullItems = [];
        if (hasItems) {
            this.drawnItems.eachLayer(function (layer) {
                if (layer instanceof L.Polyline) {
                    _this.hasNullElevation(layer.getLatLngs(), nullItems, force);
                }
            });
        }
        if (nullItems.length > 0) { // need to fetch elevations
            if (nullItems.length > 1000) {
                ra.showMsg("Tracks contains more than 1000 points, response may be slow, please wait...");
            }
            var points = "data=" + JSON.stringify(nullItems);
            var url = "https://elevation.theramblers.org.uk/";
            ra.ajax.postJSON(url, points, null, function (err, items) {
                if (err !== null) {
                    var msg = "Error: Something went wrong: " + err;
                    ra.showError(msg);
                } else {
                    for (i = 0; i < items.length; i++) {
                        var item = items[i];
                        _this.updateElevations(item);
                    }
                }
                _this.listDrawnItems();
            });
        } else {
            this.listDrawnItems();
        }
    };
    this.hasNullElevation = function (latlngs, nullItems, force) {
        var i, len;
        i = 0;
        len = latlngs.length;
        for (i = 0, len = latlngs.length; i < len; i++) {
            //   if (latlngs[i].meta.ele === null || force) {
            if (latlngs[i].alt === "undefined" || latlngs[i].alt === -999 || force) {
                var point = [latlngs[i].lat, latlngs[i].lng];
                nullItems.push(point);
            }
        }
    };
    this.updateElevations = function (item) {
        var _this = this;
        var noItems = this.drawnItems.getLayers().length;
        if (noItems > 0) {
            this.drawnItems.eachLayer(function (layer) {
                if (layer instanceof L.Polyline) {
                    _this.updateElevation(layer.getLatLngs(), item);
                }
            });
        }
    };
    this.updateElevation = function (latlngs, item) {
        var i, len;
        i = 0;
        len = latlngs.length;
        for (i = 0, len = latlngs.length; i < len; i++) {
            if (Math.abs(latlngs[i].lat - item[0]) < .000001 & Math.abs(latlngs[i].lng - item[1]) < .000001) {
                latlngs[i].alt = item[2];
            }
        }
    };
    this.settingsForm = function (tag) {
        var _this = this;
        var title = document.createElement('h3');
        title.textContent = 'Plot walking route settings';
        tag.appendChild(title);
        var titleoptions = document.createElement('h4');
        titleoptions.textContent = 'Options';
        tag.appendChild(titleoptions);
        var panHelp = 'If set (recommended), while plotting a route, the map automatically recentres on each new point as it is added.';
        this.pan = ra.html.input.yesNo(tag, '', "Pan: Centre map on last point added to route", this._userOptions.draw, 'panToNewPoint');
        ra.html.input.helpComment(this.pan, panHelp);
        this.join = ra.html.input.yesNo(tag, '', "Join: Join new route to nearest existing route", this._userOptions.draw, 'joinSegments');
        var joinHelp = 'If set, then if a new route is created while an existing one is displayed, the two routes are automatically joined together. The join is made between the nearest pair of start / end points on the two route segments.';
        ra.html.input.helpComment(this.join, joinHelp);
        tag.appendChild(document.createElement('hr'));
        var titlestyle = document.createElement('h5');
        titlestyle.textContent = 'Display: Style of route';
        tag.appendChild(titlestyle);
        var h = document.createElement('p');
        h.innerHTML = 'The colour, line thickness and line opacity of plotted routes can all be changed.';
        tag.appendChild(h);
        this.line = ra.html.input.lineStyle(tag, '', 'Track style', this._userOptions.style);
        tag.appendChild(document.createElement('hr'));

        this.pan.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this.join.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this.line.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
            _this._map.fire("draw:color-change", null);
        });
    };

    this.resetSettings = function () {
        ra.html.input.yesNoReset(this.pan, true);
        ra.html.input.yesNoReset(this.join, true);
        ra.html.input.lineStyleReset(this.line, {
            color: '#782327',
            weight: 3,
            opacity: 1
        });
        this._map.fire("draw:color-change", null);
    };
    this._readSettings = function () {
        ra.settings.read('__raDraw', this._userOptions);
        this._map.fire("draw:color-change", null);
    };
    this.saveSettings = function (save) {
        ra.settings.save(save, '__raDraw', this._userOptions);
    };
};