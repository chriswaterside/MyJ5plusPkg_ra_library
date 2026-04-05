var document, ra, FullCalendar;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}
if (typeof (ra.walkseditor.comp) === "undefined") {
    ra.walkseditor.comp = {};
}
ra.walkseditor.comp.viewAllPlaces = function (mapOptions, data) {
    this.data = data;
    this.newUrl = this.data.newUrl;
    this.fields = this.data.fields;
    this.places = new ra.walkseditor.comp.places();
    this.mapOptions = mapOptions;
    this.places.addItems(data.items);
    this.masterdiv = document.getElementById(this.mapOptions.divId);

    this.load = function () {

        var tags = [
            {name: 'heading', parent: 'root', tag: 'h2'},
            {name: 'buttons', parent: 'root', tag: 'div', attrs: {class: 'alignRight'}},
            {name: 'map', parent: 'root', tag: 'div'},
            {name: 'list', parent: 'root', tag: 'div'}
        ];
        var _this = this;
        this.elements = ra.html.generateTags(this.masterdiv, tags);
        this.places.displayMap(this.elements.map, this.mapOptions);

    };
};