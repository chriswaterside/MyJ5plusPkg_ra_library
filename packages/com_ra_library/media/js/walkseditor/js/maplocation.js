var OsGridRef, ra;

//function mapLocationInput(tag, raobject, location) { // constructor function

if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}
ra.walkseditor.mapLocationInput = function (tag, raobject, location) {

    this.input = new ra.walkseditor.inputFields;
    this.tag = tag;
    this.raobject = raobject;
    this.location = location;
    this.placesDisplayed = false;
    this.fields = {};
    this._defaultMapView = {lat: 53.233,
        long: -1.722,
        zoom: 5
    };
    this.setDefaultMapView = function (lat, long, zoom) {
        this._defaultMapView.lat = lat;
        this._defaultMapView.long = long;
        this._defaultMapView.zoom = zoom;
    };

    this.addLocation = function () {
        this.raobject.isLatLongSet = false;
        if (this.raobject.hasOwnProperty('latitude')) {
            if (this.raobject.hasOwnProperty('longitude')) {
                if (this.raobject.latitude === 0 && this.raobject.longitude === 0) {
                    this.raobject.isLatLongSet = false;
                } else {
                    this.raobject.isLatLongSet = true;
                }
            }
        }
        switch (this.location) {
            case ra.walkseditor.mapLocationInput.AREA :
                this.fields.name = this.input.addText(this.tag, 'location', "Name of General Area:", this.raobject, 'name', 'Name of general area for the walk', ra.walkseditor.help.startArea);
                break;
            case ra.walkseditor.mapLocationInput.START :
                this.fields.name = this.input.addText(this.tag, 'location', "Name of start place:", this.raobject, 'name', 'A name of the location so people can find it', ra.walkseditor.help.startName);
                break;
            case ra.walkseditor.mapLocationInput.MEETING :
                this.fields.name = this.input.addText(this.tag, 'location', "Name of meeting place:", this.raobject, 'name', 'A name of the location so people can find it', ra.walkseditor.help.meetName);
                break;
            case ra.walkseditor.mapLocationInput.FINISH :
                this.fields.name = this.input.addText(this.tag, 'location', "Name of finish:", this.raobject, 'name', 'A name of the location so people can find it', ra.walkseditor.help.finishName);
                break;
            default:
                ra.showError("Program error: Invalid place type");
        }
        var _locationdetails = document.createElement('div');
        _locationdetails.setAttribute('class', 'ra vertical-align');

        tag.appendChild(_locationdetails);
        var _label = document.createElement('label');
        _label.setAttribute('class', 'we-label gwem');
        _label.textContent = 'Location';
        _locationdetails.appendChild(_label);
        this.locationDetails = document.createElement('span');
        this.locationDetails.setAttribute('class', 'ra location details');
        this.locationDetails.style.display = "inline-block";

        _locationdetails.appendChild(this.locationDetails);
        this.details = document.createElement('details');
        this.details.classList.add("subsection");
        tag.appendChild(this.details);
        this.summary = document.createElement('summary');
        this.summary.innerHTML = 'Open/Close location/mapping options';
        this.summary.title = 'Click to open or close section';
        this.details.appendChild(this.summary);
        this.addLocationEditor(this.details);
        if (this.location !== ra.walkseditor.mapLocationInput.FINISH) {
            // bug where map does not display correctly if not displayed
            this.details.open = !this.raobject.isLatLongSet;
        }
        var _this = this;
        this.details.addEventListener("toggle", function () {
            var open = _this.details.open;
            if (open) {
                if (_this.raobject.isLatLongSet) {
                    _this.zoomMap();
                }
            }
        });

    };
    this.zoomMap = function () {
        var lat = this.raobject.latitude;
        var long = this.raobject.longitude;
        this.map.setView([lat, long], 12);
    };
    this.addLocationEditor = function (tag) {
        var editorDiv = document.createElement('div');
        editorDiv.setAttribute('class', 'location-editor');
        tag.appendChild(editorDiv);
        var tags = [
            {name: 'drag', parent: 'root', tag: 'p'},
            {name: 'map', parent: 'root', tag: 'div', attrs: {class: 'ra walksweditor mapcontainer'}}
        ];
        this.elements = ra.html.generateTags(editorDiv, tags);
        this.elements.drag.innerHTML = "To change location <b>drag marker</b> and/or use <b>Move marker to location</b>";
        switch (this.location) {
            case ra.walkseditor.mapLocationInput.MEETING :
                new ra.help(this.elements.drag, ra.walkseditor.help.locationMeet).add();
                break;
            case ra.walkseditor.mapLocationInput.START :
                new ra.help(this.elements.drag, ra.walkseditor.help.locationStart).add();
                break;
            case ra.walkseditor.mapLocationInput.AREA :
                new ra.help(this.elements.drag, ra.walkseditor.help.locationArea).add();
                break;
        }

        var mapoptions = Object.assign({}, ra.defaultMapOptions);
        mapoptions.mouseposition = true;
        mapoptions.maptools = true;
        mapoptions.mapHeight = "400px";
        this.lmap = new ra.leafletmap(this.elements.map, mapoptions);
        this.lmap.display();
        this.map = this.lmap.map();
        this.layer = L.featureGroup().addTo(this.map);
        this.postcodeLayer = L.featureGroup().addTo(this.map);
        this._rightclick = this.lmap.rightclickControl();

        this.searchControl = new L.Control.RAContainer({position: 'topleft'}).addTo(this.map);
        this.findButton = this.addMapFindLocationButton(this.searchControl.container);

        switch (this.location) {
            case ra.walkseditor.mapLocationInput.MEETING :
            case ra.walkseditor.mapLocationInput.START :
                this.placesControl = new L.Control.RAContainer({position: 'topleft'}).addTo(this.map);
                this.placeButton = this._createControlsButtons(this.placesControl.container, "Previous Meeting/Starting places", "Places groups have used before", ra.walkseditor.help.locationPlaces);
                this.postcodeControl = new L.Control.RAContainer({position: 'topleft'}).addTo(this.map);
                this.postcodeButton = this._createControlsButtons(this.postcodeControl.container, "Add Postcode", "Add or remove satnav postcode", ra.walkseditor.help.locationPostcode);

                var _this = this;
                this.postcodeButton.addEventListener("click", function (e) {
                    if (_this.raobject.hasOwnProperty('postcode')) {
                        var ok = ra.showConfirm("Do you wish to remove the postcode");
                        if (ok) {
                            _this.postcodeLayer.clearLayers();
                            delete _this.raobject.postcode;
                        }
                        _this.updateStatus();
                    } else {
                        if (_this.raobject.isLatLongSet) {
                            _this.displayPostcodes();
                        } else {
                            ra.showMsg("You must position marker before adding postcode");
                        }
                    }

                });
                document.addEventListener("postcodes-loaded", function (e) {
                    var bounds = _this.postcodeLayer.getBounds();
                    _this.map.fitBounds(bounds, {padding: [20, 20]});
                });
                this._placesButton(this.placeButton);
                break;
        }

        if (this.raobject.hasOwnProperty('postcode')) {
            var pc = this.raobject.postcode.value;
            var pcLat = this.raobject.postcode.latitude;
            var pcLng = this.raobject.postcode.longitude;
            ra.map.addPostcodeIcon(pc, [pcLat, pcLng], this.postcodeLayer);
        }
        this.updateMapMarker();
        tag.addEventListener('toggle', function () {
            _this.map.invalidateSize();
        });
        var _this = this;
        this.tag.addEventListener('what3wordsfound', function (e) {
            var data = e.raData;
            if (data.err === null) {
                data.dataObject.w3w = data.words;
                _this.updateStatus();
            }
        });
        this.tag.addEventListener('osmaps-found', function (e) {
            var data = e.ra.maps;
            _this.raobject.osmaps = data;
            _this.updateStatus();
        });
        this.tag.addEventListener('marker-moved', function (e) {
            var zoom = !_this.raobject.isLatLongSet;
            var latlng = e.ra.latlng;
            _this.raobject.latitude = latlng.lat;
            _this.raobject.longitude = latlng.lng;
            _this.raobject.isLatLongSet = true;
            var p = new LatLon(_this.raobject.latitude, _this.raobject.longitude);
            var grid = OsGridRef.latLonToOsGrid(p);
            _this.raobject.gridref8 = grid.toString(6);
            _this.raobject.gridref10 = grid.toString(8);
            if (_this.raobject.hasOwnProperty('postcode')) {
                var ok = ra.showConfirm("Do you wish to remove the postcode");
                if (ok) {
                    _this.postcodeLayer.clearLayers();
                    delete _this.raobject.postcode;
                }
            }
            if (_this.raobject.hasOwnProperty('w3w')) {
                delete _this.raobject.w3w;
            }
            if (_this.raobject.hasOwnProperty('osmaps')) {
                delete _this.raobject.osmaps;
            }
            ra.w3w.fetch(_this.tag, _this.raobject, latlng.lat, latlng.lng);
            _this.fetchMaps(latlng.lat, latlng.lng);
            _this.updateMapMarker();
            _this.updateStatus();
            if (zoom) {
                _this.zoomMap();
            }

        });

        this.findButton.addEventListener("locationfound", function (e) {
            let event = new Event("marker-moved", {bubbles: true});
            var item = e.raData.item;
            event.ra = {};
            event.ra.latlng = L.latLng(parseFloat(item.lat), parseFloat(item.lon));
            _this.tag.dispatchEvent(event);
        });
        this.updateStatus();
    };
    this._createControlsButtons = function (tag, title, tooltip, help) {
        var button = document.createElement('button');
        button.classList.add("locatemapbutton");
        button.textContent = title;
        button.title = tooltip;
        tag.appendChild(button);
        new ra.help(tag, help).add();
        return button;
    };
    this.updateStatus = function () {
        var out = '';
        if (this.raobject.isLatLongSet) {
            var latlgn = 'Lat ' + this.raobject.latitude.toFixed(4) + '/ Long ' + this.raobject.longitude.toFixed(4);
            if (this.raobject.gridref8 === "") {
                out += 'Location: Outside UK: ' + latlgn + "";
            } else {
                out += 'Location: ' + latlgn + '<br/>Grid Ref: ' + this.raobject.gridref8 + " [" + this.raobject.gridref10 + "]";
            }
            switch (this.location) {
                case ra.walkseditor.mapLocationInput.AREA :
                    break;
                default:
                    if (typeof this.raobject.postcode !== "undefined") {
                        out += '<br/>Postcode: ' + this.raobject.postcode.value + "";
                    }
                    out += "<span class='minor'>";
                    if (typeof this.raobject.w3w !== "undefined") {
                        out += 'What 3 words: ' + this.raobject.w3w;
                    }
                    if (typeof this.raobject.osmaps !== "undefined") {
                        var i;
                        var items = this.raobject.osmaps;
                        var content;
                        for (i = 0; i < items.length; i++) {
                            var item = items[i];
                            content = "<br/>" + item.type + " " + item.number + ": " + item.title;
                            out += content;
                        }
                    }
                    out += "</span>";

            }
            this.locationDetails.innerHTML = out;
        } else {
            this.locationDetails.innerHTML = '<span style="color:red">Location not defined:  <i>Use mapping options to define location</i></span>';
        }
        if (this.hasOwnProperty('postcodeButton')) {
            if (this.raobject.hasOwnProperty('postcode')) {
                this.postcodeButton.classList.add("active");
                this.postcodeButton.innerText = "Remove Postcode";
            } else {
                this.postcodeButton.classList.remove("active");
                this.postcodeButton.innerText = "Add Postcode";
            }
        }
    };
    this.fetchMaps = function (lat, lng) {
        var p = new LatLon(lat, lng);
        var grid = OsGridRef.latLonToOsGrid(p);
        var gr = grid.toString(6);
        var _this = this;
        if (gr !== "") {
            var east = Math.round(grid.easting);
            var north = Math.round(grid.northing);
            var url = "https://osmaps.theramblers.org.uk/index.php?easting=" + east + "&northing=" + north;
            ra.ajax.getJSON(url, function (err, items) {
                if (err !== null) {
                } else {
                    if (items.length !== 0) {
                        let event = new Event("osmaps-found"); // (2)
                        event.ra = {};
                        event.ra.maps = items;
                        _this.tag.dispatchEvent(event);
                    }
                }
            });
        }
    };
    this.updateMapMarker = function () {
        this.layer.clearLayers();
        var img, icon;
        switch (this.location) {
            case ra.walkseditor.mapLocationInput.AREA :
                img = ra.baseDirectory() + "media/com_ra_library/images/marker-area.png";
                icon = L.icon({iconUrl: img, iconSize: [35, 40], iconAnchor: [17, 20], popupAnchor: [0, -30]});
                break;
            case ra.walkseditor.mapLocationInput.START :
                img = ra.baseDirectory() + "media/com_ra_library/images/marker-start.png";
                icon = L.icon({iconUrl: img, iconSize: [35, 40], iconAnchor: [17, 20], popupAnchor: [0, -30]});
                break;
            case ra.walkseditor.mapLocationInput.FINISH :
                img = ra.baseDirectory() + "media/com_ra_library/images/marker-finish.png";
                icon = L.icon({iconUrl: img, iconSize: [35, 40], iconAnchor: [5, 37], popupAnchor: [0, -30]});
                break;
            default:
                img = ra.baseDirectory() + "media/com_ra_library/images/marker-route.png";
                icon = L.icon({iconUrl: img, iconSize: [33, 50], iconAnchor: [17, 45], popupAnchor: [0, -30]});
        }

        var lat, long;
        var popup;
        if (this.raobject.isLatLongSet) {
            lat = this.raobject.latitude;
            long = this.raobject.longitude;
            var p = new LatLon(lat, long);
            var grid = OsGridRef.latLonToOsGrid(p);
            var grStr = grid.toString(6);
            if (grStr === "") {
                grStr = "Outside OS Grid";
            }
            popup = "Grid Ref: " + grStr + "<br/>Lat: " + lat.toFixed(5) + "<br/>Long: " + long.toFixed(5);

        } else {
            lat = this._defaultMapView.lat;
            long = this._defaultMapView.long;
            popup = "Move marker to the correct location either by<ul><li>dragging it with the mouse</li><li>and/or using the <b>Move marker to location</b></li></ul>";

        }
        var _this = this;
        var marker = L.marker([lat, long], {draggable: true, icon: icon}).addTo(this.layer);
        marker.bindPopup(popup);
        this.map.panTo([lat, long]);
        marker.addEventListener('dragend', function (e) {
            let event = new Event("marker-moved", {bubbles: true}); // (2)
            event.ra = {};
            event.ra.latlng = e.target.getLatLng();
            _this.tag.dispatchEvent(event);
        });
        this.map.on("mouseover", function (event) {
            if (!_this.raobject.isLatLongSet) {
                marker.openPopup();
            }
        });
    };

    this.addMapFindLocationButton = function (tag) {
        var findButton = document.createElement('button');
        findButton.setAttribute('type', 'button');
        findButton.setAttribute('class', 'locatemapbutton');
        findButton.textContent = "Move marker to location";
        findButton.title = "Search for a place and move marker to there";
        tag.appendChild(findButton);
        new ra.help(tag, ra.walkseditor.help.locationSearch).add();
        var _this = this;
        findButton.addEventListener("click", function (e) {
            var feed = new ra.feedhandler();
            feed.modalSearchForm(findButton);
        });
        findButton.addEventListener("locationfound", function (e) {
            var item = e.raData.item;
            item.latitude = parseFloat(item.lat);
            item.longitude = parseFloat(item.lon);
            item.name = null;
            _this.map.setView([item.latitude, item.longitude], 15);
            _this.updateDetails(item);

        });
        return findButton;
    };
    this.updateDetails = function (item) {
        if (item.name !== null) {
            this.raobject.name = item.name;
            this.fields.name.value = item.name;
        }
        this.raobject.isLatLongSet = true;
        this.raobject.latitude = item.latitude;
        this.raobject.longitude = item.longitude;
        this.updateMapMarker();
        this.updateStatus();
        // this.getClosestPostcode();
        let event = new Event("marker-moved", {bubbles: true}); // (2)
        event.ra = {};
        event.ra.latlng = {};
        event.ra.latlng.lat = item.latitude;
        event.ra.latlng.lng = item.longitude;
        this.tag.dispatchEvent(event);
    };

    this.displayPostcodes = function () {
        var p = new LatLon(this.raobject.latitude, this.raobject.longitude);
        var grid = OsGridRef.latLonToOsGrid(p);
        this.postcodeLayer.clearLayers();
        if (this.raobject.hasOwnProperty('postcode')) {
            delete this.raobject.postcode;
        }
        var east = Math.round(grid.easting);
        var north = Math.round(grid.northing);
        var _this = this;
        // ("The nearest postcodes will be displayed. \nYou can then select the appropriate one for SatNav. \nJust click the correct postcode");
        var url = "https://postcodes.theramblers.org.uk/index.php?easting=" + east + "&northing=" + north + "&dist=10&maxpoints=20";
        ra.ajax.getJSON(url, function (err, pcs) {
            if (err !== null) {
                ra.showError('Sorry something went wrong fetching the postcode, error:' + err);
            } else {
                if (pcs.length !== 0) {
                    pcs.forEach(function (pc) {
                        var gr = new OsGridRef(pc.Easting, pc.Northing);
                        var latlong = OsGridRef.osGridToLatLon(gr);
                        var marker = ra.map.addPostcodeIcon(pc.Postcode, [latlong.lat, latlong.lon], _this.postcodeLayer);
                        marker.ra = {};
                        marker.ra.postcode = pc.Postcode.replace(/  /g, " ");
                        marker.ra.latlong = latlong;
                        marker.addEventListener('click', function (e) {
                            var marker = e.target;
                            _this.raobject.postcode = {
                                value: marker.ra.postcode,
                                latitude: marker.ra.latlong.lat,
                                longitude: marker.ra.latlong.lon
                            };
                            _this.postcodeLayer.eachLayer(function (layer) {
                                if (layer !== marker) {
                                    _this.postcodeLayer.removeLayer(layer);
                                }
                            });
                            _this.updateStatus();

                        });
                    }
                    );
                    let event = new Event("postcodes-loaded", {bubbles: true}); // 
                    event.raData = {};
                    event.raData.layer = _this.postcodeLayer;
                    document.dispatchEvent(event);
                } else {
                    ra.showMsg('No postcodes found within 10Km');
                }
            }
        });
    };
    this._placesButton = function (button) {

        var _this = this;
        button.addEventListener("click", function (e) {
            if (_this.placesDisplayed) {
                _this._rightclick.rightClickClearPlaces();
                _this.placesDisplayed = false;
                button.classList.remove("active");
            } else {
                if (_this.raobject.isLatLongSet) {
                    var options = {
                        location: {lat: _this.raobject.latitude, lng: _this.raobject.longitude},
                        distance: 20,
                        limit: 30,
                        cluster: true};
                    _this._rightclick.rightClickDisplayPlaces(options);
                    _this.placesDisplayed = true;
                    button.classList.add("active");
                } else {
                    ra.showMsg("Marker position not set");
                }
            }

        });
        return button;
    };

};


// Instance method will be available to all instances but only load once in memory 
ra.walkseditor.mapLocationInput.prototype.publicMethod = function () {
    ra.showError(this.publicVariable);
};

// Static variable shared by all instances
ra.walkseditor.mapLocationInput.AREA = "Area";
ra.walkseditor.mapLocationInput.POINT = "Point";
ra.walkseditor.mapLocationInput.MEETING = "Meet";
ra.walkseditor.mapLocationInput.FINISH = "Finish";