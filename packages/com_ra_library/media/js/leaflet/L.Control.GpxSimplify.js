var L, document;

L.Control.GpxSimplify = L.Control.extend({
    options: {
        title: 'Simplify - reduce number of points defining walking route',
        titledisabled: 'No routes defined',
        position: 'bottomright',
        options: ["Save", "Cancel"]
    },
    onAdd: function (map) {
        this._map = map;
        this.simplify2D = false;
        this.factor = 111000; // One degree is 111Km
        this._simplifylayer = null;
        this._simplifylayer = L.featureGroup([]);
        this._simplifylayer.addTo(this._map);
        var containerAll = L.DomUtil.create('div', 'leaflet-control-gpx-simplify  ra-simplify-toolbar-button-disabled');
        var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control', containerAll);
        this._createIcon(container);
        this._container = container;
        L.DomEvent.disableClickPropagation(containerAll);
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.on(this.link, 'click', this._simplifyGpx, this);
        this._appendButtons(containerAll);
        this.holder.style.display = "none";
        this.setStatus("off");
        return containerAll;
    },
    _appendButtons: function (container) {
        this.holder = L.DomUtil.create('div', 'leaflet-gpx-options', container);

        this.status = L.DomUtil.create('div', 'points', this.holder);
        this.status.innerHTML = "Points";

        var element = L.DomUtil.create('div', 'slider', this.holder);
        element.innerHTML = "<input type=\"range\" min=\"1\" max=\"100\" value=\"49\" class=\"slider\" id=\"gpxsimplify\">";
        this.slider = element.childNodes[0];
        L.DomEvent.addListener(this.slider, 'change', this._simplify, this);

        element = L.DomUtil.create('div', 'elevation', this.holder);
        element.innerHTML = "Maintain elevation";
        L.DomEvent.addListener(element, 'click', this._elevation, this);

        element = L.DomUtil.create('div', 'save', this.holder);
        element.innerHTML = "Save";
        L.DomEvent.addListener(element, 'click', this._save, this);

        element = L.DomUtil.create('div', 'cancel', this.holder);
        element.innerHTML = "Cancel";
        L.DomEvent.addListener(element, 'click', this._cancel, this);
        var self = this;
        this.slider.addEventListener("input", processEvent = function (e) {
            self._simplify();
        }, false);
        // this.slider.oninput = function () {
        //       this._simplify();////change
        //   };
    },
    _simplify: function () {
        var tolerance = this.slider.value / this.factor;
        this._simplifylayer.clearLayers();
        var text = "Pts: ";
        var sub = "";
        var _self = this;
        this._itemsCollection.eachLayer(function (layer) {

            if (layer instanceof L.Polyline) {
                var points = layer.getLatLngs();
                var newPoints = simplify(points, tolerance, true);
                var polyline = L.polyline(newPoints, {color: 'red', opacity: '0.5'});
                _self._simplifylayer.addLayer(polyline);
                text += sub + newPoints.length;
                sub = "/";
            }
            _self.status.innerHTML = text;
        });

    },
    triggerEvent: function (el, type) {
        if ('createEvent' in document) {
            // modern browsers, IE9+
            var e = document.createEvent('HTMLEvents');
            e.initEvent(type, false, true);
            e._this = this;
            el.dispatchEvent(e);
        } else {
            // IE 8
            var e = document.createEventObject();
            e.eventType = type;
            e._this = this;
            el.fireEvent('on' + e.eventType, e);
        }
    },
    setRouteItems: function (itemsCollection) {
        this._itemsCollection = itemsCollection;
    },
    setStatus: function (status) {
        this.enabled = true;
        if (status === "off") {
            this.enabled = false;
        }
        if (status === "auto") {
            this.enabled = this._itemsCollection.getLayers().length !== 0;
        }
        if (this.enabled) {
            L.DomUtil.removeClass(this._container, 'ra-simplify-toolbar-button-disabled');
            this.link.title = this.options.title;
        } else {
            L.DomUtil.addClass(this._container, 'ra-simplify-toolbar-button-disabled');
            this.link.title = this.options.titledisabled;
        }
    },
    _createIcon: function (container) {
        var icon = L.DomUtil.create('a', '', container);
        this.link = icon;
        this.link.id = "leaflet-gpx-simplify";
        this.link.title = this.options.title;
        return this.link;
    },
    _simplifyGpx: function (e) {
        if (this.enabled) {
            this._map.fire('simplify:started');
            this.holder.style.display = "inline-block";
            this.slider.value = 1;
            this.triggerEvent(this.slider, 'input');
        }
    },
    _save: function (evt) {
        var tolerance = this.slider.value / this.factor;
        this._itemsCollection.eachLayer(function (layer) {
            if (layer instanceof L.Polyline) {
                var points = layer.getLatLngs();
                var newPoints = simplify(points, tolerance);
                layer.setLatLngs(newPoints);
                //  following line ensures the new points are noticed by Leaflet.Draw
                layer.fire('revert-edited', {layer: layer});
            }
        });

        this._simplifylayer.clearLayers();
        this._map.fire('simplify:saved');
        this.holder.style.display = "none";
    },
    _cancel: function (evt) {
        this._simplifylayer.clearLayers();
        this._map.fire('simplify:cancelled');
        this.holder.style.display = "none";
    },
    _elevation: function (evt) {
        var element = evt.currentTarget;
        switch (this.simplify2D) {
            case "3D":
                this.simplify2D = "2D";
                element.innerHTML = "2D simplify Lat/Long";
                break;
            case "2D":
                this.simplify2D = "elev";
                element.innerHTML = "Maintain elevation";
                break;
            default:
                this.simplify2D = "3D";
                element.innerHTML = "3D simplify Lat/long and Alt";
        }
        this._simplify();
    }


});
L.control.gpxsimplify = function (options) {
    return new L.Control.GpxSimplify(options);
};
