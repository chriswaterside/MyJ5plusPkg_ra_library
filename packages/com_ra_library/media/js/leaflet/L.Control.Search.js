var L, ra;
L.Control.Search = L.Control.extend({
    options: {
        title: 'Search for a location',
        position: 'topleft'
    },
    _userOptions: null,
    searchLayer: null,
    modal: null,
    
    onAdd: function (map) {
        this._map = map;
        var _this = this;
        this.searchLayer = L.featureGroup([]);
        this.searchLayer.addTo(this._map);
        this.container = L.DomUtil.create('div', 'leaflet-control-search leaflet-bar leaflet-control');
        this.container.title = 'Search for a location, place, GR, Postcode, W3W';
        L.DomEvent.disableClickPropagation(this.container);
        L.DomEvent.on(this.container, 'click', function (ev) {
            L.DomEvent.stopPropagation(ev);
            _this.searchLayer.clearLayers();
            var searchDiv = document.createElement('div');
            searchDiv.setAttribute('class', 'search');
            _this.modal = ra.modals.createModal(searchDiv, false, true);
            _this._addSearch(searchDiv);
        });
        return this.container;
    },
    click:function(){
        this.container.click();
    },

    userOptions: function (value) {
        this._userOptions = value;
    },

    _addSearch: function (tag) {
        var _this = this;
        var feed = new ra.feedhandler();
        feed.getSearchTags(tag, tag);
        tag.addEventListener("locationfound", function (e) {
            _this.modal.close();
            var raData = e.raData;
            var result = raData.item;
            _this.searchLayer.clearLayers();
            result.center = new L.LatLng(result.lat, result.lon);
            new L.Marker(result.center, {icon: ra.map.icon.redmarker()})
                    .bindPopup("<b>" + result.class + ": " + result.type + "</b><br/>" + result.display_name)
                    .addTo(_this.searchLayer)
                    .openPopup();
            _this._map.setView(result.center, 16);
        });
    }

});

L.control.search = function (options) {
    return new L.Control.Search(options);
};
