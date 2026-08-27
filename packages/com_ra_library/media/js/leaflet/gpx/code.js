var L, ra, OsGridRef;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.pathnetwork) === "undefined") {
    ra.pathnetwork = {};
}
ra.pathnetwork.authorityMap = function (options, data) {

    this.data = data;
    var masterdiv = document.getElementById(options.divId);
    this.lmap = new ra.leafletmap(masterdiv, options);
    this.lmap.display();
    this.load = function () {
        var _map = this.lmap.map();
        // _map is a leaflet map object
        // add items to _map using normal leaftet methods



    };


};
