var document, ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}
if (typeof (ra.walkseditor.comp) === "undefined") {
    ra.walkseditor.comp = {};
}
ra.walkseditor.comp.places = function () {
    this._places = [];

    this.addItems = function (items) {
        items.forEach(item => {
            var newPlaces = new ra.walkseditor.comp.place(item);
            this.addPlace(newPlaces);
        });
    };

    this.addPlace = function (place) {
        this._places.push(place);
    };

    this.displayMap = function (tag, options) {
        this.lmap = new ra.leafletmap(tag, options);
        this.lmap.display();;
        this.cluster = new ra.map.cluster(this.lmap.map());
        this._places.forEach(place => {
            place.addMapMarker(this.cluster);
        });
        this.cluster.addClusterMarkers();
        this.cluster.zoomAll();
    };

};
ra.walkseditor.comp.place = function (item) {
    this.abbr = item.abbr;
    this.deleteUrl = item.deleteUrl;
    this.duplicateUrl = item.duplicateUrl;
    this.editUrl = item.editUrl;
    this.gridreference = item.gridreference;
    this.id = item.id;
    this.latitude = item.latitude;
    this.longitude = item.longitude;
    this.name = item.name;
    this.postcode = item.postcode;
    this.viewUrl = item.viewUrl;
    this.what3words = item.what3words;

    this.addMapMarker = function (cluster) {
        var icon = ra.map.icon.markerStart();
        var lat = this.latitude;
        var long = this.longitude;
        var title = this.name;
        var popup = "Name: " + this.name + "<br/>Postcode: " + this.postcode + "<br/>W3W:" + this.what3words;
        cluster.addMarker(popup, lat, long, {icon: icon, title: title, riseOnHover: true});
    };

};
