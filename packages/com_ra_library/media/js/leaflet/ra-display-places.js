var L, ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.display) === "undefined") {
    ra.display = {};
}
ra.display.places = function (options, data) {

    this.options = options;  //public
    this.masterdiv = document.getElementById(options.divId);
    var lmap = new ra.leafletmap(this.masterdiv, options);
    lmap.display();
    this._map = lmap.map();
    lmap.rightclickControl().disablePlaces();

    this.load = function () {
        this._places = new L.Control.Places({cluster: true});
        this._places.addTo(this._map);
        this._places.displayAllPlaces();
    };
};