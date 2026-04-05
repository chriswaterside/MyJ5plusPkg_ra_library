var document, ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}
ra.walkseditor.placeEditor = function () {

    this.addPlace = function (tag, data) {
        var itemsDiv = document.createElement('div');
        itemsDiv.setAttribute('class', 'location-group');
        tag.appendChild(itemsDiv);
        //     var input = new ra.walkseditor.inputFields;
        //    input.addLocation(itemsDiv, ramblers.record, 'place');
        var location = new ra.walkseditor.mapLocationInput(itemsDiv, data.record, ra.walkseditor.mapLocationInput.MEETING);
        this._place = location.addLocation();
    };
}
;
placecontroller = function (tagContainer, data) {
    this.tagContainer = tagContainer;
    this.data = data;

    this.placeEditor = function () {
        var placeDiv = document.createElement('div');
        placeDiv.setAttribute('id', 'js-place-editor');
        this.tagContainer.appendChild(placeDiv);
        var place = new ra.walkseditor.placeEditor();

        place.addPlace(placeDiv, this.data);

        document.addEventListener("postcodeupdate", function (e) {
            var tag = document.getElementById('js-place-editor');
            tag.innerHTML = "";
            var place = new ra.walkseditor.placeEditor();
            place.addPlace(tag);
        });
    };
//    this.clickMapButton = function () {
//        var display = new mapdisplay();
//        display.redisplay();
//        display.displayLocationOnMap();
//        var elmnt = document.getElementById("leafletmap");
//        elmnt.scrollIntoView();
//    };
//    this.displayMarkersOnMap = function () {
//        var display = new mapdisplay();
//        display.displayLocationOnMap();
//    };
    this.setSubmitButton = function () {
        var _this = this;
        var submitButton = document.getElementById(this.data.fields.submit);
        submitButton.addEventListener("mouseover", function () {
            _this.setInputValue('abbr', 'abbr');
            _this.setInputValue('name', 'name');
            _this.setInputValue('latitude', 'latitude');
            _this.setInputValue('longitude', 'longitude');
            _this.setInputValue('gridref10', 'gridref10');
            _this.setInputValue('postcode', 'postcode');
            //       ramblers.controller.setInputValue('what3words', 'what3words_words');
        });
    };
    this.setInputValue = function (field, name) {
        var fieldtag = document.getElementById(this.data.fields[field]);
        if (fieldtag === null) {
            ra.showError("Program error setting field: " + field);
            return "";
        }
        if (this.data.record.hasOwnProperty(name)) {  // Initialise value
            fieldtag.value = this.data.record[name];
        } else {
            fieldtag.value = "";
        }
    };
};
