var L;
L.Control.RAContainer = L.Control.extend({
    options: {
        id: null,
        position: 'bottomright'
    },
    onAdd: function (map) {
        this._map = map;

        var container = L.DomUtil.create('div', 'leaflet-control-ra-container');
        this.container = container;
        if (this.options.id !== null) {
            container.setAttribute('id', this.options.id);
        }
        return container;
    },
    getContainer: function () {
        return  this.container;
    },
    setText: function (text) {
        this.container.innerHTML = text;
    },
    setErrorText: function (text) {
        this.container.innerHTML = "<span class='ra error'>" + text + "</span>";
    }
});
L.control.racontainer = function (options) {
    return new L.Control.RAContainer(options);
};
