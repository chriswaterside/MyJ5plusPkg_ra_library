var L, ra;
L.Control.SmartRoute = L.Control.extend({
    options: {
        title: 'Turn Smart Routing on/off to follow paths',
        position: 'bottomright',
        _routingKey: 'nil'
    },
    _userOptions: null,
    onAdd: function (map) {
        this._map = map;
        // default is off as relies on Map Tools being loaded first.
        this.alert = true;
        this.enabled = false;
        this._saveRoute = false;
        this._smartRouteLayer = L.featureGroup([]).addTo(map);
        var containerAll = L.DomUtil.create('div', 'leaflet-control-smart-route ra-smart-toolbar-button-disabled');
        this.containerAll = containerAll;
        var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control', containerAll);
        this._createIcon(container);
        this._container = container;
        L.DomEvent.disableClickPropagation(containerAll);
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.on(this.link, 'click', this._smart_routes, this);
        return containerAll;
    },
    routingKey: function (value) {
        this.options._routingKey = value;
    },
    userOptions: function (value) {
        this._userOptions = value;
    },
    drawControl: function (value) {
        this._drawControl = value;
    },
    disable: function () {
        this.containerAll.style.display = "none";
    },
    enable: function () {
        this.containerAll.style.display = "initial";
    },
    setRouteItems: function (itemsCollection) {
        this._itemsCollection = itemsCollection;
    },
    saveRoute: function (value) {
        this._saveRoute = value;
    },
    _createIcon: function (container) {
        var icon = L.DomUtil.create('a', '', container);
        this.link = icon;
        this.link.id = "leaflet-smart-route";
        this.link.title = this.options.title;
        return this.link;
    },
    _smart_routes: function () {
        this.enabled = !this.enabled;
        if (this.enabled) {
            if (this.alert) {
                this.alert = false;
                ra.showMsg('Smart Route follows paths/roads etc defined on the Open Street Map,regardless of which map is displayed.');
            }
            L.DomUtil.removeClass(this._container, 'ra-smart-toolbar-button-disabled');
            this.setOpacityZero();
        } else {
            L.DomUtil.addClass(this._container, 'ra-smart-toolbar-button-disabled');
            this.resetOpacity();
        }
    },
    displaySmartPoints: function (layers) {
        var no = 0;
        var latlngs = [];
        for (const property in layers) {
            latlngs[no] = {};
            latlngs[no] = layers[property]._latlng;
            no += 1;
        }
        if (latlngs.length <= 1) {
            this._smartRouteLayer.clearLayers();
            return;
        }
        this.pending = true;
        var self = this;
        let request = new XMLHttpRequest();
        request.open('POST', "https://api.openrouteservice.org/v2/directions/foot-hiking/geojson");
        request.setRequestHeader('Accept', 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8');
        request.setRequestHeader('Content-Type', 'application/json');
        request.setRequestHeader('Authorization', this.options._routingKey);
        request.onreadystatechange = function () {
            if (this.readyState === 4) {
                //console.log('Status:', this.status);
                //console.log('Headers:', this.getAllResponseHeaders());
                //console.log('Body:', this.responseText);
                var data = JSON.parse(this.responseText);

                // check for '' response

                if (this.status === 200) {
                    console.log('Smart route retrieved');
                    var headers = this.getAllResponseHeaders();
                    if (!self.checkLimits(headers)) {
                        return;
                    }
                    self.drawSmartRoute(data); // also saves latlngs
                    if (self._saveRoute) {
                        self.saveSmartRoute(self);
                        self._saveRoute = false;
                    }
                } else {
                    ra.showError("Unable to fetch route, status " + this.status);
                }
                self.pending = false;
            }
        };
        var body = {};
        body.instructions = false;
        body.geometry_simplify = true;
        body.coordinates = ra.map.getLngLats(latlngs);
        var search = JSON.stringify(body);
        request.send(search);
    },
    drawSmartRoute: function (data) {
        var geometry = data.features[0].geometry;
        this._smartRouteLayer.clearLayers();
        var latlngs = ra.map.getLatlngs(geometry.coordinates);
        this.latlngs = latlngs; // latlngs for when we save route.
        var style = this._userOptions.style;
        this._smartRouteLayer.addLayer(L.polyline(latlngs, style));

    },
    setOpacityZero: function () {
        //console.log("Opacity set to ZERO");
        var style = {};
        style.color = this._userOptions.style.color;
        style.weight = this._userOptions.style.weight;
        style.opacity = 0;
        this._drawControl.setDrawingOptions({
            polyline: {shapeOptions: style}});
    },
    resetOpacity: function () {
        // console.log("Opacity reset");
        var style = this._userOptions.style;
        this._drawControl.setDrawingOptions({
            polyline: {shapeOptions: style}});
    },
    checkLimits: function (headers) {
        var available = 0;
        var lines = headers.split('\r\n');
        var items = [];
        lines.forEach(splitLine);
        function splitLine(value) {
            var temp = value.split(': ');
            if (temp.length > 1) {
                items[temp[0]] = temp[1];
            }
        }
        if (typeof items["x-ratelimit-remaining"] !== 'undefined') {
            available = parseInt(items["x-ratelimit-remaining"]);
        }
        if (available < 20) {
            ra.showError('Our usage of the Open Routing Service has reached the limit for today.\n\rPlease try again tomorrow or continue with straight line segments.');
            this.turnOffSmartRouting();
            return false;
        }

        return true;
    },
    turnOffSmartRouting: function () {
        this.enabled = false;
        this.resetOpacity();
        this._map.removeControl(this);
    },

    saveSmartRoute: function (self) {
        if (this.latlngs !== null) {
            //console.log("Save");
            var layer;
            var layers = self._itemsCollection.getLayers();

            // only save if route has not been cancelled
            if (layers.length > this.NoLayers) {
                // delete the draw layer and replace
                layer = layers[layers.length - 1];
                self._itemsCollection.removeLayer(layer);
                this.resetOpacity();
                var latlngs = ra.map.removeShortSegments(this.latlngs);
                var line = new L.Polyline(latlngs, this._userOptions.style);
                this.latlngs = null;
                self._itemsCollection.addLayer(line);

                if (this._userOptions.draw.joinSegments) {
                    this._map.fire('join:attach', null);
                } else {
                    self._itemsCollection.fire('upload:addline', {line: line});
                }
                this.setOpacityZero();
            }
            this._smartRouteLayer.clearLayers();
        }
    }
});

L.control.smartroute = function (options) {
    return new L.Control.SmartRoute(options);
};
