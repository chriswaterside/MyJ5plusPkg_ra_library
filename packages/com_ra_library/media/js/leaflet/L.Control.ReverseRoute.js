var L;
L.Control.ReverseRoute = L.Control.extend({
    options: {
        title: 'Reverse the direction of walking route',
        titledisabled: 'No routes defined',
        position: 'bottomright'
    },
    onAdd: function (map) {
        this._map = map;
        this.enabled = false;
        var containerAll = L.DomUtil.create('div', 'leaflet-control-reverse-route ra-reverse-toolbar-button-disabled');
        var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control', containerAll);
        this._createIcon(container);
        this._container = container;
        L.DomEvent.disableClickPropagation(containerAll);
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.on(this.link, 'click', this._reverse_routes, this);
        this._appendButtons(containerAll);
        this.holder.style.display = "none";
        return containerAll;
    },
    setRouteItems: function (itemsCollection) {
        this._itemsCollection = itemsCollection;
    },
    setStatus: function (status) {
        this.enabled = true;
        if (status == "off") {
            this.enabled = false;
        }
        if (status == "auto") {
            this.enabled = false;
            var self = this;
            this._itemsCollection.eachLayer(function (layer) {
                if (layer instanceof L.Polyline) {
                    self.enabled = true;
                }
            });
        }
        if (this.enabled) {
            L.DomUtil.removeClass(this._container, 'ra-reverse-toolbar-button-disabled');
            this.link.title = this.options.title;
        } else {
            L.DomUtil.addClass(this._container, 'ra-reverse-toolbar-button-disabled');
            this.link.title = this.options.titledisabled;
        }
    },
    _createIcon: function (container) {
        var icon = L.DomUtil.create('a', '', container);
        this.link = icon;
        this.link.id = "leaflet-reverse-route";
        this.link.title = this.options.titledisabled;
        return this.link;
    },
    _appendButtons: function (container) {
        this.holder = L.DomUtil.create('div', 'leaflet-gpx-options', container);
        this.status = L.DomUtil.create('div', 'status', this.holder);
        this.status.innerHTML = "Route reversed";
    },
    _reverse_routes: function (evt) {
        if (this.enabled) {
            this._itemsCollection.eachLayer(function (layer) {
                if (layer instanceof L.Polyline) {
                    var points = layer.getLatLngs();
                    var reversed = points.reverse();
                    layer.setLatLngs(reversed);
                    //  following line ensures the new points are noticed byLeaflet.Draw
                    layer.fire('revert-edited', {layer: layer});
                }
            });
            this._itemsCollection.fire('reverse:reversed');
            this.holder.style.display = "inline-block";
            var self = this;
            setTimeout(function () {
                self.holder.style.display = "none";
            }, 2000);
        }
    }
});

L.control.reverseroute = function (options) {
    return new L.Control.ReverseRoute(options);
};
