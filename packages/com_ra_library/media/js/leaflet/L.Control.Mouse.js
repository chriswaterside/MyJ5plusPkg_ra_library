var L, ra, OsGridRef;
// this file contains three controls
// Mouse to display grid reference
// OSInfo to display oS Map outlines
// Rightclick to define mouse right click actions

L.Control.Mouse = L.Control.extend({
    options: {
        position: 'bottomleft',
        separator: ', ',
        lngFirst: false,
        numDigits: 5
    },
    _userOptions: {
        OSgridOption: 'mouse',
        displayGridRef: true,
        displayLatLong: true,
        displayLatLongDecimal: true,
        displayZoom: false,
        displayMouseMinorGrid: false,
        displayFullMinorGrid: false,
        ignore: true,
        osMouseGridStyle: {
            color: '#ff7800', // #8CCBF7
            weight: 1.5,
            opacity: .8,
            interactive: false,
            bubblingMouseEvents: true,
            fill: true,
            fillOpacity: 0.075,
            ignore: true},
// ignore to stop zoomAll picking up on item
        osFullGridStyle: {
            color: '#5DB8F9', // #8CCBF7
            weight: 3,
            opacity: 0.5,
            ignore: true}
    },
    _mapState: {
        zoom: 0,
        latlng: L.latLng(0, 0),
        grid: null,
        gridRef0: '',
        gridRef2: '',
        gridRef4: '',
        gridRef6: '',
        gridRef8: ''
    },
    _gsValues: {
        0: 100000,
        2: 10000,
        4: 1000,
        6: 100,
        8: 10
    },
    onAdd: function (map) {
        var self = this;
        this._map = map;
        this.search = null;
        this._controls = {};
        this._container = L.DomUtil.create('div', 'leaflet-control-mouseposition');
        this._containerCont = L.DomUtil.create('div', 'racontainer', this._container);
        this._containerSearch = L.DomUtil.create('div', 'icon search', this._containerCont);
        this._containerIcon = L.DomUtil.create('div', 'icon grid mouse', this._containerCont);
        this._containerGR = L.DomUtil.create('div', 'gr', this._containerCont);
        this._containerCont = L.DomUtil.create('div', 'clear', this._container);
        this._containerLL = L.DomUtil.create('div', 'latlng', this._container);
        L.DomEvent.disableClickPropagation(this._container);
        this._container.style.display = 'none';
        this._containerIcon.title = 'Change display of UK OS grid';
        this._containerSearch.title = 'Search for a specific loacation';
        this._containerSearch.style.display = 'none';
        map.on('mousemove', this._updateMouseMove, this);
        map.on('zoomend', this._updateZoom, this);
        map.on('moveend', this._moveEnd, this);
        this.OSGridSquareLayer = L.featureGroup([], {interactive: false, ignore: true}).addTo(map);
        if (L.Browser.mobile) {
            this._container.style.display = 'none';
        }
        this._readSettings();
        map.on('mouseover', function () {
            self._container.style.display = 'inherit';
        });
        map.on('mouseout', function () {
            self._container.style.display = 'none';
            switch (self._userOptions.OSgridOption) {
                case 'mouse':
                    self.OSGridSquareLayer.clearLayers();
                    break;
                case 'none':
                case 'full':
                    break;
            }

        });
        this._containerIcon.addEventListener('click', (e) => {
            switch (self._userOptions.OSgridOption) {
                case 'mouse':
                    self._userOptions.OSgridOption = 'none';
                    break;
                case 'none':
                    self._userOptions.OSgridOption = 'full';
                    break;
                case 'full':
                    self._userOptions.OSgridOption = 'mouse';
                    break;
            }
            self._setFormAttributes();
            self._updateZoom();
        });
        this._containerSearch.addEventListener('click', (e) => {
            if (this.search !== null) {
                this.search.click();
            }
        });
        return this._container;
    },
    addSearch: function (search) {
        this.search = search;
        this._containerSearch.style.display = 'inherit';
    },
    changeDisplay: function (display) {
        if (!L.Browser.mobile) {
            this._container.style.display = display;
        }
    },
    onRemove: function (map) {
        map.off('mousemove', this._updateMouseMove);
    },
    gridSquarePause: function () {
        this.SAVEOSgridOption = this._userOptions.OSgridOption;
        this._userOptions.OSgridOption = 'none';
    },
    gridSquareResume: function () {
        this._userOptions.OSgridOption = this.SAVEOSgridOption;
    },
    _updateZoom: function () {
        this.OSGridSquareLayer.clearLayers();
        this._updateMapStateZoom();
        this._updateLocationPanel();
        switch (this._userOptions.OSgridOption) {
            case "full":
                this.OSGridSquareLayer.clearLayers();
                this.displayOSGrid();
                break;
            case "mouse":
                this._displayMouseGrid();
                break;
            case "none":
                break;
        }
    },
    _moveEnd: function () {
        switch (this._userOptions.OSgridOption) {
            case "full":
                this.OSGridSquareLayer.clearLayers();
                this.displayOSGrid();
                break;
            case "mouse":
                break;
            case "none":
                this.OSGridSquareLayer.clearLayers();
                break;
        }
    },
    _updateMouseMove: function (e) {
        this._updateMapState(e.latlng);
        this._updateLocationPanel();
        switch (this._userOptions.OSgridOption) {
            case "full":
                break;
            case "mouse":
                this.OSGridSquareLayer.clearLayers();
                this._displayMouseGrid();
                break;
            case "none":
                this.OSGridSquareLayer.clearLayers();
                break;
        }
        return;
    },
    _updateMapState: function (latlng) {
        this._updateMapStateZoom();
        this._mapState.latlng = latlng;
        var p = new LatLon(latlng.lat, latlng.lng);
        this._mapState.grid = OsGridRef.latLonToOsGrid(p);
        this._mapState.gridRef0 = this._mapState.grid.toString(2).substr(0, 2);
        this._mapState.gridRef2 = this._mapState.grid.toString(2);
        this._mapState.gridRef4 = this._mapState.grid.toString(4);
        this._mapState.gridRef6 = this._mapState.grid.toString(6);
        this._mapState.gridRef8 = this._mapState.grid.toString(8);
        if (this._userOptions.displayLatLongDecimal) {
            this._mapState.latlongtext = latlng.lat.toFixed(5) + ", " + latlng.lng.toFixed(5);
        } else {
            this._mapState.latlongtext = ra.latlongDecToDms(latlng.lat, latlng.lng);
        }
    },
    _updateMapStateZoom: function () {
        this._mapState.zoom = this._map.getZoom();
        this._mapState.majorGridLevel = ra.map.os.gridLevel(this._map, 50);
        this._mapState.minorGridLevel = ra.map.os.gridLevel(this._map, 10);
    },
    _updateLocationPanel: function () {
        var gr, size;
        var out = '';
        var minor = this._mapState.minorGridLevel > this._mapState.majorGridLevel;
        switch (this._userOptions.OSgridOption) {
            case 'none':
                minor = false;
                break;
            case 'mouse':
                if (!this._userOptions.displayMouseMinorGrid) {
                    minor = false;
                }
                break;
            case 'full':
                if (!this._userOptions.displayFullMinorGrid) {
                    minor = false;
                }
                break;

        }

        if (this._mapState.gridRef0 === "") {
            out = "Outside UK OS Grid";
            this._containerIcon.style.display = 'none';
        } else {
            this._containerIcon.style.display = 'inherit';
            switch (this._mapState.majorGridLevel) {
                case 0:
                    gr = this._mapState.gridRef0;
                    size = '100km';
                    if (minor) {
                        gr += " / " + this._mapState.gridRef2;
                        size = '100km/10km';
                    }
                    break;
                case 2:
                    gr = this._mapState.gridRef2;
                    size = '10km';
                    if (minor) {
                        gr += " / " + this._mapState.gridRef4;
                        size = '10km/1km';
                    }
                    break;
                case 4:
                    gr = this._mapState.gridRef4;
                    size = '1km';
                    if (minor) {
                        gr += " / " + this._mapState.gridRef6;
                        size = '1km/100m';
                    }
                    break;
                case 6:
                    gr = this._mapState.gridRef6;
                    size = '100m';
                    if (minor) {
                        gr += " / " + this._mapState.gridRef8;
                        size = '100m/10m';
                    }
                    break;
                case 8:
                    gr = this._mapState.gridRef8;
                    size = '10m';
                    break;
                default:
                    gr = '';
                    size = '';
            }
            if (this._userOptions.OSgridOption === 'none') {
                out = '<span class="osgridref">' + this._mapState.gridRef6 + "</span>";
            } else {
                out = 'Size ' + size + '<br/><span class="osgridref">' + gr + "</span>";
            }
        }

        if (this._userOptions.displayZoom) {
            out += "   Zoom[" + this._mapState.zoom.toFixed(2) + "]";
        }
        this._containerGR.innerHTML = out;
        this._containerLL.innerHTML = '';
        if (this._userOptions.displayLatLong && this._map.getSize().y > 300) {
            if (this._userOptions.displayLatLongDecimal) {
                this._containerLL.innerHTML = "Lat/long: " + this._mapState.latlng.lat.toFixed(5) + ", " + this._mapState.latlng.lng.toFixed(5); //+" z"+ zoom;
            } else {
                this._containerLL.innerHTML = "Lat/long: " + ra.latlongDecToDms(this._mapState.latlng.lat, this._mapState.latlng.lng);
            }
        }
    },
    _displayMouseGrid: function () {

        if (this._userOptions.OSgridOption !== 'mouse') {
            return;
        }

        if (this._mapState.gridRef4 === "") {
            return;
        }

        var grid = this._mapState.grid;
        var minor = this._mapState.minorGridLevel > this._mapState.majorGridLevel;
        var gs = this._gsValues[this._mapState.majorGridLevel];
        this._displayGridRefSquare(grid, gs);
        if (minor && this._userOptions.displayMouseMinorGrid) {
            this._displayGridRefSquare(grid, gs / 10);
        }
    },
    _displayGridRefSquare: function (grid, metres) {
        var bounds = this._osGridToLatLongSquare(grid, metres);
        var gridsquare = L.polygon(bounds, this._userOptions.osMouseGridStyle);
        this.OSGridSquareLayer.addLayer(gridsquare); // change rectangle
    },
    _osGridToLatLongSquare: function (gridref, size) {
        var E1 = Math.floor(gridref.easting / size) * size;
        var N1 = Math.floor(gridref.northing / size) * size;
        var E2 = E1 + size;
        var N2 = N1 + size;
        var bounds = [];
        for (let i = 0; i < 6; i++) {
            var east = E1 + size / 5 * i;
            var gr = new OsGridRef(east, N2);
            var ll = OsGridRef.osGridToLatLon(gr);
            var latlong = [ll.lat, ll.lon];
            bounds.push(latlong);
        }
        for (let i = 0; i < 6; i++) {
            var east = E2 - size / 5 * i;
            var gr = new OsGridRef(east, N1);
            var ll = OsGridRef.osGridToLatLon(gr);
            var latlong = [ll.lat, ll.lon];
            bounds.push(latlong);
        }
        return bounds;
    },
    displayOSGrid: function () {
        if (this._userOptions.OSgridOption !== 'full') {
            return;
        }
        var gs = this._gsValues[this._mapState.majorGridLevel];
        var bounds = this._map.getBounds();
        var pNE = new LatLon(bounds._northEast.lat, bounds._northEast.lng);
        var pSW = new LatLon(bounds._southWest.lat, bounds._southWest.lng);
        var ne = OsGridRef.latLonToOsGrid(pNE);
        var sw = OsGridRef.latLonToOsGrid(pSW);
        var grne = ne.toString(6);
        var grsw = sw.toString(6);
        if (grne === "" & grne === "") {
            gs = 100000;
        }
        ne.easting = Math.floor(ne.easting / gs + 2) * gs;
        ne.northing = Math.floor(ne.northing / gs + 2) * gs;
        sw.easting = Math.floor(sw.easting / gs) * gs;
        sw.northing = Math.floor(sw.northing / gs) * gs;
        if (grsw === "") {
            sw.easting = 0;
            sw.northing = 0;
        }
        if (grne === "") {
            ne.easting = 700000;
            ne.northing = 1400000;
        }

        if (gs === 100000) {
            sw.easting = 0;
            sw.northing = 0;
            ne.easting = 700000;
            ne.northing = 1400000;
        }
        var weight = this._userOptions.osFullGridStyle.weight;
        if (this._mapState.majorGridLevel !== this._mapState.minorGridLevel && this._userOptions.displayFullMinorGrid) {
            this.drawOSMapGrid(ne, sw, gs / 10, this.OSGridSquareLayer, weight / 2);
        }
        this.drawOSMapGrid(ne, sw, gs, this.OSGridSquareLayer, weight);
    },
    drawOSMapGrid: function (ne, sw, gs, layer, weight) {
        var style;
        var color = this._userOptions.osFullGridStyle.color;
        //var weight = this._userOptions.osFullGridStyle.weight;
        var opacity = this._userOptions.osFullGridStyle.opacity;
        style = {color: color, weight: weight, opacity: opacity, ignore: true};
        var lines;
        for (east = sw.easting; east < ne.easting + 1000; east += gs) {
            lines = [];
            i = 0;
            for (north = sw.northing; north < ne.northing; north += gs) {
                var gr = new OsGridRef(east, north);
                var latlong = OsGridRef.osGridToLatLon(gr);
                lines[i] = new L.latLng(latlong.lat, latlong.lon);
                i++;
            }
            layer.addLayer(L.polyline(lines, style));
        }
        for (var north = sw.northing; north < ne.northing; north += gs) {
            lines = [];
            i = 0;
            for (var east = sw.easting; east < ne.easting + 1000; east += gs / 3) {
                gr = new OsGridRef(east, north);
                latlong = OsGridRef.osGridToLatLon(gr);
                lines[i] = new L.latLng(latlong.lat, latlong.lon);
                i++;
            }
            layer.addLayer(L.polyline(lines, style));
        }
    },
    _setFormAttributes: function () {
        this._containerIcon.classList.remove('none');
        this._containerIcon.classList.remove('mouse');
        this._containerIcon.classList.remove('full');
        this._containerIcon.classList.add(this._userOptions.OSgridOption);
        if (this._controls.icon) { // and hence rest of form
            this._controls.icon.classList.remove('none');
            this._controls.icon.classList.remove('mouse');
            this._controls.icon.classList.remove('full');
            this._controls.icon.classList.add(this._userOptions.OSgridOption);

            this._controls.OSgridOption.value = this._userOptions.OSgridOption;
            this._controls.osFullGridStyle.display = 'none';
            this._controls.divMouse.style.display = 'none';
            this._controls.divFull.style.display = 'none';
            switch (this._userOptions.OSgridOption) {
                case 'none':
                    break;
                case 'mouse':
                    this._controls.divMouse.style.display = 'inherit';
                    break;
                case 'full':
                    this._controls.divFull.style.display = 'inherit';
                    break;
            }

        }
    },
    settingsForm: function (tag) {
        var self = this;
        var title = document.createElement('h3');
        title.textContent = 'Bottom left display';
        tag.appendChild(title);
        this._controls.displayGridRef = ra.html.input.yesNo(tag, 'divClass', "Display Grid Reference", this._userOptions, 'displayGridRef');
        this._controls.displayLatLong = ra.html.input.yesNo(tag, 'divClass', "<b>Display</b> Latitude/Longitude", this._userOptions, 'displayLatLong');
        this._controls.displayLatLongDecimal = ra.html.input.yesNo(tag, 'divClass', "Latitude/Longitude format", this._userOptions, 'displayLatLongDecimal', ['Decimal', 'Degrees Minutes Seconds']);
        this._controls.displayZoom = ra.html.input.yesNo(tag, 'divClass', "Display Zoom level", this._userOptions, 'displayZoom');
        var title = document.createElement('h4');
        title.textContent = 'Ordnance Survey Grid Squares';
        tag.appendChild(title);
        var h = document.createElement('p');
        h.innerHTML = '<b>Ordnance Survey</b> maps provide a grid reference system to define locations.';
        tag.appendChild(h);
        var comment = document.createElement('p');
        comment.innerHTML = 'You can select how this grid reference system is displayed.';
        //    comment.innerHTML = '<label><input type="radio" name="color" value="red">Red <img src="icon.png" alt="Icon description" width="32" height="32"></label><label><input type="radio" name="color" value="blue">Blue</label><label><input type="radio" name="color" value="green">Green</label>';     
        tag.appendChild(comment);

        var options = [{name: 'Off - do not display UK OS grid', value: 'none', 'data-image': '/media/com_ra_library/js/leaflet/images/gridnone.png'},
            {name: 'Display Grid square of mouse position', value: 'mouse', 'data-image': '/media/com_ra_library/js/leaflet/images/gridmouse.png'},
            {name: 'Display full UK OS grid', value: 'full', 'data-image': '/media/com_ra_library/js/leaflet/images/gridfull.png'}];
        this._controls.OSgridOption = ra.html.input.combo(tag, 'ms-dropdown', "How to display Ordnance Survey Grid", this._userOptions, 'OSgridOption', options);
        this._controls.icon = document.createElement('span');
        this._controls.icon.classList.add('ra', 'setting', 'grid');
        tag.appendChild(this._controls.icon);
        this._controls.divMouse = document.createElement('div');
        tag.appendChild(this._controls.divMouse);
        this._controls.osMouseGridStyle = ra.html.input.lineStyle(this._controls.divMouse, '', 'OS Mouse grid options', this._userOptions.osMouseGridStyle);
        this._controls.displayMouseMinorGrid = ra.html.input.yesNo(this._controls.divMouse, 'divClass', "Display minor squares", this._userOptions, 'displayMouseMinorGrid');

        this._controls.divFull = document.createElement('div');
        tag.appendChild(this._controls.divFull);
        this._controls.osFullGridStyle = ra.html.input.lineStyle(this._controls.divFull, '', 'OS Full grid options', this._userOptions.osFullGridStyle);
        this._controls.displayFullMinorGrid = ra.html.input.yesNo(this._controls.divFull, 'divClass', "Display minor squares", this._userOptions, 'displayFullMinorGrid');

        this._controls.displayGridRef.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this._controls.displayLatLong.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this._controls.displayLatLongDecimal.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this._controls.displayZoom.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this._controls.OSgridOption.addEventListener("ra-input-change", function (e) {
            self._setFormAttributes();
            ra.settings.changed();
        });

        this._controls.displayMouseMinorGrid.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this._controls.osMouseGridStyle.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this._controls.displayFullMinorGrid.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this._controls.osFullGridStyle.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
            self.displayOSGrid();
        });
        tag.addEventListener('focus', function () {
            alert("mouse");
        });
        this._setFormAttributes();

    },
    resetSettings: function () {
        ra.html.input.yesNoReset(this._controls.displayGridRef, true);
        ra.html.input.yesNoReset(this._controls.displayLatLong, true);
        ra.html.input.yesNoReset(this._controls.displayLatLongDecimal, true);
        ra.html.input.yesNoReset(this._controls.displayZoom, false);
        ra.html.input.comboReset(this._controls.OSgridOption, 'mouse');
        ra.html.input.yesNoReset(this._controls.displayMouseMinorGrid, false);
        ra.html.input.lineStyleReset(this._controls.osMouseGridStyle, {
            color: '#ff7800', // #8CCBF7
            weight: 1.5,
            fill: true,
            fillOpacity: 0.075});
        ra.html.input.yesNoReset(this._controls.displayFullMinorGrid, false);
        ra.html.input.lineStyleReset(this._controls.osFullGridStyle, {
            color: '#5DB8F9',
            weight: 3,
            opacity: 0.5});
    },
    _readSettings: function () {
        ra.settings.read('__mouseossquare', this._userOptions);
    },
    saveSettings: function (save) {
        ra.settings.save(save, '__mouseossquare', this._userOptions);
    }
});
L.control.mouse = function (options) {
    return new L.Control.Mouse(options);
};
L.Control.OSInfo = L.Control.extend({
    options: {
        position: 'bottomleft'
    },
    onAdd: function (map) {
        self = this;
        this._map = map;
        this.osMapLayer = L.featureGroup([]).addTo(map);
        this.OSGrid = {};
        this.OSGrid.layer = L.layerGroup().addTo(map);
        this._containerAll = L.DomUtil.create('div', 'leaflet-control-osmaps leaflet-bar leaflet-control');
        // this._container = L.DomUtil.create('div', '', this._containerAll);
        this._containerAll.title = "Display the coverage of all OS Explore/Landranger maps";
        L.DomEvent.disableClickPropagation(this._containerAll);
        this._appendButtons(this._containerAll);
        this.holder.style.display = "none";
        L.DomEvent.addListener(this._containerAll, 'mouseover', function () {
            self.holder.style.display = "";
        }, this);
        L.DomEvent.addListener(this._containerAll, 'mouseout', function () {
            self.holder.style.display = "none";
        }, this);
        L.DomEvent.addListener(this._containerAll, 'mouseover', function () {
            if (this.osMapLayer.getLayers().length > 0) {
                self.removeDiv.style.display = "";
            } else {
                self.removeDiv.style.display = "none";
            }
        }, this);

        return this._containerAll;
    },
    _appendButtons: function (container) {
        this.holder = L.DomUtil.create('div', 'leaflet-os-options', container);
        var element = L.DomUtil.create('div', 'explorer', this.holder);
        element.title = "Display coverage of all Explorer maps";
        element.innerHTML = "Explorer 25K";
        L.DomEvent.addListener(element, 'click', this._disp25K, this);
        var element = L.DomUtil.create('div', 'landranger', this.holder);
        element.title = "Display coverage of all Landranger maps";
        element.innerHTML = "Landranger 50K";
        L.DomEvent.addListener(element, 'click', this._disp50K, this);
        this.removeDiv = L.DomUtil.create('div', 'remove', this.holder);
        this.removeDiv.title = "Remove coverage of maps";
        this.removeDiv.innerHTML = "Clear maps";
        L.DomEvent.addListener(this.removeDiv, 'click', this._clear, this);
        this.disclaimDiv = L.DomUtil.create('div', 'disclaim', this.holder);
        this.disclaimDiv.title = "Disclaimer about OS Map coverage";
        this.disclaimDiv.innerHTML = "Map disclaimer";
        L.DomEvent.addListener(this.disclaimDiv, 'click', this._disclaim, this);
    },
    onRemove: function (map) {

    },
    _disp25K: function () {
        this.saveBounds = this._map.getBounds();
        this.osMapLayer.clearLayers();
        this.displayOSMapOutlines('25K');
    },
    _disp50K: function () {
        this.saveBounds = this._map.getBounds();
        this.osMapLayer.clearLayers();
        this.displayOSMapOutlines('50K');
    },
    _clear: function () {
        this.osMapLayer.clearLayers();
        this._map.fitBounds(this.saveBounds);
    },
    _disclaim: function () {
        var container = document.createElement('div');
        var title = document.createElement('h4');
        title.textContent = 'Ordnance Survey Landranger and Explorer Maps';
        container.appendChild(title);
        var comment = document.createElement('div');
        comment.innerHTML = 'Please note that our information showing the area covered by OS maps is unofficial and may be incorrect.<br/>Please check before buying a map.';
        comment.innerHTML += '<br/>If you notice any errors then do contact us via the help option.';
        container.appendChild(comment);
        ra.modals.createModal(container, false, true);
    },
    displayOSMapOutlines: function (type) {
        // type should be '25K' or '50K'
        var self = this;
        var url = "https://osmaps.theramblers.org.uk/index.php?mapscale=" + type;
        ra.ajax.getJSON(url, function (err, items) {
            if (err !== null) {
                var msg = "Error: Something went wrong: " + err;
            } else {
                if (items.length !== 0) {
                    for (i = 0; i < items.length; i++) {
                        var item = items[i];
                        ra.map.os.display(item, self.osMapLayer);
                    }
                }
                self._map.fitBounds(self.osMapLayer.getBounds());
            }
        });
    }
});
L.control.osinfo = function (options) {
    return new L.Control.OSInfo(options);
};
L.Control.RightclickMarker = function (latlng, layer, actiontag) {
    this.p = new LatLon(latlng.lat, latlng.lng);
    this.actiontag = actiontag;
    this._layer = layer;
    layer.clearLayers();
    var icon = L.icon({
        iconUrl: ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/mouseloc.png',
        iconSize: [18, 18], // size of the icon
        iconAnchor: [9, 9], // point of the icon which will correspond to marker's location
        popupAnchor: [0, -9] // point from which the popup should open relative to the iconAnchor
    });
    this._marker = L.marker(this.p, {icon: icon}).bindPopup("Processing...");
    this._layer.addLayer(this._marker);
    this._marker.openPopup();
    setTimeout(function (marker) {
        marker.closePopup();
    }, 10000, this._marker);
    this.setContent = function (contentTag) {
        var tags = [
            {name: 'content', parent: 'root', tag: 'div'},
            {name: 'options', parent: 'root', tag: 'div'}
        ];
        var popup = document.createElement('div');
        var elements = ra.html.generateTags(popup, tags);
        if (typeof contentTag === 'string') {
            elements.content.innerHTML = contentTag;
        } else {
            elements.content.appendChild(contentTag);
        }
        this._marker.getPopup().setContent(popup);
        this._addOptionsLink(elements.options);
    };
    this._addOptionsLink = function (tag) {
        if (this.actiontag === null) {
            return;
        }
        var _this = this;
        var div = document.createElement('div');
        div.classList.add("ra-mouse-option");
        tag.appendChild(div);
        var opt = document.createElement('a');
        var img = document.createElement("img");
        img.setAttribute('src', ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/mouse-right-click-icon.png');
        img.setAttribute('height', '18px');
        img.setAttribute('width', '18px');
        img.classList.add('rotateimg90');
        opt.appendChild(img);
        var link = document.createTextNode("Right click options ");
        opt.appendChild(link);
        opt.title = "Change the way Mouse Right Click works";
        opt.classList.add("pointer");
        opt.addEventListener("click", function (e) {
            _this.actiontag.click();
        });
        div.appendChild(opt);
    };
};
L.Control.Rightclick = L.Control.extend({
    options: {
        position: 'bottomleft',
        separator: ', '
    },
    displaymap: null,
    _places: null,
    apiUrl2: "http://overpass-api.de/api/interpreter?data=",
    apiUrl: "https://overpass.kumi.systems/api/interpreter?data=",
    _placesEnabled: true,
    _userOptions: {
        postcodes: {
            number: 20,
            distance: 10
        },
        groups: {
            number: 60,
            distance: 25
        },
        starting: {
            number: 300,
            distance: 10
        },
        osm: {
            distance: 2
        }
    },
    controls: {
        postcodes: {
            number: null,
            distance: null
        },
        groups: {
            number: null,
            distance: null
        },
        starting: {
            number: null,
            distance: null
        },
        osm: {
            distance: null
        }
    },
    rightClickOptions: [
        {group: 'General Information',
            items: {
                details: 'Display map co-ordinates',
                postcode: 'Display Postcodes',
                osmaps: 'Ordnance Survey Maps'}},
        {group: "Ramblers Information",
            items: {groups: 'Display Ramblers Groups',
                starting: 'Display Starting Places'}},
        {group: 'Open Street Map information',
            items: {
                parking: 'Display Parking',
                bus_stops: 'Display Bus Stops',
                cafes: 'Display Cafes',
                pubs: 'Display Public Houses',
                toilets: 'Display Toilets'}}],
    //   alltags: 'What\'s here?'}}],
    rightClickOption: "details",
    onAdd: function (map) {
        this._map = map;
        this._readSettings();
        this._mouseLayer = L.featureGroup([]);
        this._mouseLayer.addTo(this._map);
        this._places = new L.Control.Places({cluster: false});
        this._places.addTo(map);
        this._map.on('contextmenu', this._onRightClick, this);
        this.enabled = true;
        this._containerAll = L.DomUtil.create('div', 'leaflet-control-rightclick leaflet-bar leaflet-control');
        this._containerAll.title = "Mouse right click/ Tap & hold setting";
        this._appendButtons(this._containerAll);
        var osmOptions = [];
        osmOptions["cafes"] = {tag: "amenity", type: "cafe", title: "Cafes", single: "Cafe"};
        osmOptions["parking"] = {tag: "amenity", type: "parking", title: "Car Parks", single: "Car Park"};
        osmOptions["pubs"] = {tag: "amenity", type: "pub", title: "Pubs", single: "Pub"};
        osmOptions["toilets"] = {tag: "amenity", type: "toilets", title: "Toilets", single: "Toilets"};
        osmOptions["bus_stops"] = {tag: "highway", type: "bus_stop", title: "Bus Stops", single: "Bus Stop"};
        osmOptions["alltags"] = {tag: "", type: "", title: "What's there", single: "What's here?"};
        this.osmOptions = osmOptions;
        L.DomEvent.disableClickPropagation(this._containerAll);
        return this._containerAll;
    },
    noLayers: function () {
        var no = 0;
        no += this._mouseLayer.getLayers().length;
        no += this._places.noLayers();
        return no;
    },
    onRemove: function () {
        this._map.off('contextmenu', this._onRightClick);
    },
    _appendButtons: function (container) {
        var holder = L.DomUtil.create('div', 'leaflet-control-rightclick-container', container);
        holder.style.display = "none";
        L.DomEvent.disableClickPropagation(holder);
        var _this = this;
        L.DomEvent.addListener(container, 'click', function (event) {
            holder.style.display = "";
            var tag = document.createElement('div');
            _this.optionsForm(tag);
            ra.modals.createModal(tag, false, true);
            event.stopPropagation();
        }, this);
        this.closeHolder = false;
        holder.addEventListener("click", function (ev) {
            ev.stopPropagation();
        });
    },
    Enabled: function (status) {
        this.enabled = status === true;
    },
    disablePlaces: function () {
        this._placesEnabled = false;
    },
    mapControl: function (value) {
        this._mapControl = value;
    },
    userOptions: function () {
        return this._userOptions;
    },
    _onRightClick: function (e) {
        this._mouseLayer.clearLayers();
        this._places.clearLayers();
        var option = this.rightClickOption;
        if (this.enabled) {
            switch (option) {
                case "details":
                    this._displayDetails(e);
                    break;
                case "postcode":
                    this._displayPostcodes(e);
                    break;
                case "osmaps":
                    this._displayOsMaps(e);
                    break;
                case "starting":
                    if (!this._placesEnabled) {
                        ra.showMsg('The current map is already displaying all meeting and starting places and hence this option is currently disabled');
                        break;
                    }
                    var options = {
                        location: e.latlng,
                        distance: this._userOptions.starting.distance,
                        limit: this._userOptions.starting.number,
                        mouseOptions: this._containerAll};
                    this._places.displayPlaces(options);
                    break;
                case "groups":
                    this._displayGroups(e);
                    break;
                case "cafes":
                    this._displayOSM(e, "cafes");
                    break;
                case "parking":
                    this._displayOSM(e, "parking");
                    break;
                case "pubs":
                    this._displayOSM(e, "pubs");
                    break;
                case "toilets":
                    this._displayOSM(e, "toilets");
                    break;
                case "bus_stops":
                    this._displayOSM(e, "bus_stops");
                    break;
                case "alltags":
                    this._displayOSM(e, "alltags");
                    //   this._displayAllTags(e);
                    break;
            }
        }
    },
    rightClickDisplayPlaces: function (options) {
        this._places.displayPlaces(options);
    },
    rightClickClearPlaces: function () {
        this._places.clearLayers();
    },
    _displayDetails: function (e) {
        var desc, links = '', gr, gr10, grid;
        var p = new LatLon(e.latlng.lat, e.latlng.lng);
        grid = OsGridRef.latLonToOsGrid(p);
        gr = grid.toString(6);
        gr10 = grid.toString(8);
        desc = "<b>Latitude: </b>" + e.latlng.lat.toFixed(5) + " ,  <b>Longitude: </b>" + e.latlng.lng.toFixed(5);
        if (gr !== "") {
            desc += "<br/><b>Grid Reference: </b>" + gr +
                    "<br/><b>Grid Reference: </b>" + gr10 + " (8 Figure)";
            links += '<a href="javascript:ra.link.photos(\'' + gr10 + '\')">[Photos]</a>';
           // links += '<a href="javascript:ra.link.streetmap(' + e.latlng.lat.toFixed(7) + ',' + e.latlng.lng.toFixed(7) + ')">[OS Map]</a>';
        } else {
            desc += "<br/>Outside UK OS Grid";
        }
        links += '<a href="javascript:ra.link.googlemap(' + e.latlng.lat.toFixed(7) + ',' + e.latlng.lng.toFixed(7) + ')">[Google Map]</a>';
        links += '<a href="javascript:ra.loc.directions(' + e.latlng.lat.toFixed(7) + ',' + e.latlng.lng.toFixed(7) + ')">[Directions]</a>';
        var marker = new L.Control.RightclickMarker(e.latlng, this._mouseLayer, this._containerAll);
        var tags = [
            {parent: 'root', tag: 'div', 'innerHTML': desc},
            {name: 'w3w', parent: 'root', tag: 'div'},
            {parent: 'root', tag: 'div', 'innerHTML': links},
            {name: 'options', parent: 'root', tag: 'div'}
        ];
        var pop = document.createElement('div');
        //   ele.innerHTML = desc;
        var elements = ra.html.generateTags(pop, tags);
        marker.setContent(pop);
        ra.w3w.get(e.latlng.lat, e.latlng.lng, elements.w3w, false);
    },
    _displayPostcodes: function (e) {
        var p = new LatLon(e.latlng.lat, e.latlng.lng);
        var grid = OsGridRef.latLonToOsGrid(p);
        var gr = grid.toString(6);
        var i;
        var marker;
        var desc = " ";
        var self = this;
        var point;
        var marker = new L.Control.RightclickMarker(e.latlng, this._mouseLayer, this._containerAll);
        if (gr !== "") {
            marker.setContent("<b>Searching for postcodes ...</b>");
            // get postcodes around this point       
            var east = Math.round(grid.easting);
            var north = Math.round(grid.northing);
            var options = "&dist=" + this._userOptions.postcodes.distance + "&maxpoints=" + this._userOptions.postcodes.number;
            var url = "https://postcodes.theramblers.org.uk/index.php?easting=" + east + "&northing=" + north + options;
            ra.ajax.getJSON(url, function (err, items) {
                if (err !== null) {
                    var msg = "Error: Something went wrong: " + err;
                    marker.setContent(msg);
                } else {
                    if (items.length === 0) {
                        var closest = "No postcodes found within " + self._userOptions.postcodes.distance + "km";
                        marker.setContent(closest);
                    } else {
                        for (i = 0; i < items.length; i++) {
                            var item = items[i];
                            var easting = item.Easting;
                            var northing = item.Northing;
                            var gr = new OsGridRef(easting, northing);
                            var latlong = OsGridRef.osGridToLatLon(gr);
                            var pt = new L.latLng(latlong.lat, latlong.lon);
                            var location = ra.geom.position(p.lat, p.lon, pt.lat, pt.lng);
                            var popup = item.Postcode + "<br />     Location " + self._kFormatter(Math.round(location.distance * 1000)) + "m " + location.direction.name + " from marker";
                            var style;
                            if (i === 0) {
                                pcmarker = L.marker(pt, {icon: ra.map.icon.postcodeClosest()}).bindPopup(popup);
                                style = {color: 'green', weight: 3, opacity: 0.2};
                            } else {
                                pcmarker = L.marker(pt, {icon: ra.map.icon.postcode()}).bindPopup(popup);
                                style = {color: 'blue', weight: 3, opacity: 0.2};
                            }

                            self._mouseLayer.addLayer(pcmarker);
                            self._mouseLayer.addLayer(L.polyline([pt, p], style));
                        }
                        marker.setContent("<b>" + items.length + " Postcodes found within " + self._userOptions.postcodes.distance + "km</b>");
                        var bounds = self._mouseLayer.getBounds();
                        self._map.fitBounds(bounds, {padding: [50, 50]});
                    }
                }
                setTimeout(function (point) {
                    point.closePopup();
                }, 3000, point);
            });
        } else {
            desc += "<br/>Outside UK OS Grid";
            point.getPopup().setContent(desc);
            point.openPopup();
        }

    },
    _kFormatter: function (num) {
        return num > 999 ? (num / 1000).toFixed(1) + 'K' : num;
    },
    _displayOsMaps: function (e) {
        var p = new LatLon(e.latlng.lat, e.latlng.lng);
        var grid = OsGridRef.latLonToOsGrid(p);
        var gr = grid.toString(6);
        var self = this;
        var marker = new L.Control.RightclickMarker(e.latlng, this._mouseLayer, this._containerAll);
        if (gr !== "") {
            marker.setContent("<b>Searching for Ordnance Survey maps ...</b>");
            //  point.openPopup();
            var east = Math.round(grid.easting);
            var north = Math.round(grid.northing);
            var url = "https://osmaps.theramblers.org.uk/index.php?easting=" + east + "&northing=" + north;
            ra.ajax.getJSON(url, function (err, items) {
                if (err !== null) {
                    marker.setContent("Error: Something went wrong: " + err);
                } else {
                    marker.setContent("No Ordnance Survey Maps found for this location");
                    if (items.length !== 0) {
                        content = "<b>" + items.length + " Ordnance Survey maps found</b>";
                        for (var i = 0; i < items.length; i++) {
                            var item = items[i];
                            content += "<h4>" + item.type + " " + item.number + "</h4>";
                            content += "<p>" + item.title + "</p>";
                            ra.map.os.display(item, self._mouseLayer);
                        }
                    }

                    marker.setContent(content);
                    var bounds = self._mouseLayer.getBounds();
                    self._map.fitBounds(bounds, {padding: [50, 50]});
                }
            });
        } else {
            marker.setContent("<br/>Outside UK OS Grid");
        }

    },
    _displayGroups: function (e) {
        var self = this;
        var marker = new L.Control.RightclickMarker(e.latlng, this._mouseLayer, this._containerAll);
        marker.setContent("<b>Searching for Ramblers Groups ...</b>");
        var $latitude = e.latlng.lat;
        var $longitude = e.latlng.lng;
        var options = "&dist=" + this._userOptions.groups.distance + "&maxpoints=" + this._userOptions.groups.number + "&v=2";
        var url = "https://groups.theramblers.org.uk/index.php?latitude=" + $latitude + "&longitude=" + $longitude + options;
        ra.ajax.getJSON(url, function (err, items) {
            if (err !== null) {
                marker.setContent("Error: Something went wrong: " + err);
            } else {
                var length = Object.keys(items).length;
                if (length === 0) {
                    var closest = "No Ramblers Groups found within " + self._userOptions.groups.distance + "km";
                    marker.setContent(closest);
                } else {
                    marker.setContent("<b>" + length + " Ramblers Groups found within " + self._userOptions.groups.distance + "km</b>");
                    //   point.openPopup();
                    for (var propt in items) {
                        var item = items[propt];
                        var type = "";
                        var popup = "<b>";
                        switch (item.scope) {
                            case "A":
                                popup += "Area: ";
                                type = "Area";
                                break;
                            case "G":
                                popup += "Group: ";
                                type = "Group";
                                break;
                        }
                        popup += item.name + "</b><br/>" + item.description;
                        popup += "<br/>" + type + " information on <a href='" + item.url + "' target='_blank'>Ramblers.org.uk</a>";
                        popup += "<br/>" + type + " site: <a href='http://" + item.website + "' target='_blank' >" + item.groupUrl + "</a>";
                        if (item.scope !== "A") {
                            popup += "<br/>Part of " + item.areaName;
                        }
                        var $iclass = "group-icon " + item.scope.toLowerCase();
                        var pt = new L.latLng(item.latitude, item.longitude);
                        var title = item.name;
                        var icon = L.divIcon({className: $iclass, iconSize: null, html: title});
                        var gmarker = L.marker(pt, {icon: icon}).bindPopup(popup);
                        self._mouseLayer.addLayer(gmarker);
                    }
                    var bounds = self._mouseLayer.getBounds();
                    self._map.fitBounds(bounds, {padding: [50, 50]});
                }
            }
        });
    },
    _displayOSM: function (e, option) {
        var self = this;
        var osmOption = this.osmOptions[option];
        var myicon = this.nodeIcon(option);
        var tag = osmOption.tag;
        var type = osmOption.type;
        var title = osmOption.title;
        var single = osmOption.single;
        var marker = new L.Control.RightclickMarker(e.latlng, this._mouseLayer, this._containerAll);
        marker.setContent("<b>Searching for " + type + " ...</b>");
        this._latlng = e.latlng;
        var queryTemplate = '[out:json]; (node["{tag}"="{type}"](around:{radius},{lat},{lng});way["{tag}"="{type}"](around:{radius},{lat},{lng});relation["{tag}"="{type}"](around:{radius},{lat},{lng}););out center;';
        var query = this.getQuery(queryTemplate, tag, type);
        //console.log("Query: " + query);
        var url = this.getUrl(query);
        //console.log("Query: " + url);
        ra.ajax.getJSON(url, function (err, items) {
            if (err !== null) {
                var msg = "Error: Something went wrong: " + err;
                marker.setContent(msg);
            } else {
                if (items.elements.length === 0) {
                    var closest = "No " + title + " found within " + self._userOptions.osm.distance + "km";
                    marker.setContent(closest);
                } else {
                    msg = "<b>" + items.elements.length + " " + title + " found within " + self._userOptions.osm.distance + "km</b>";
                    msg += "<p>" + items.osm3s.copyright + "</p>";
                    marker.setContent(msg);
                    //  point.openPopup();
                    for (var i = 0; i < items.elements.length; i++) {
                        var item = items.elements[i];
                        self.displayElement(item, single, myicon);
                    }
                    var bounds = self._mouseLayer.getBounds();
                    self._map.fitBounds(bounds, {padding: [50, 50]});
                }
            }

        });
    },
    _displayAllTags: function (e) {
        // ('This option not available yet');
        //  return;
        var self = this;
        var title = "All items";
        var single = "Item";
        var type = "Items";
        var p = new LatLon(e.latlng.lat, e.latlng.lng);
        var i;
        var msg = "<b>Searching for " + type + " ...</b>";
        var point = L.marker(p).bindPopup(msg);
        this._mouseLayer.clearLayers();
        this._mouseLayer.addLayer(point);
        //  point.getPopup().setContent(msg);
        point.openPopup();
        this._latlng = e.latlng;
        // var queryTemplate = '[out:json]; (node({{bbox}});way({{bbox}});relation({{bbox}});';
        var queryTemplate = '[out:json];(way(around:200,52.71017,-1.3480224););out body;';
        //   node({{bbox}})(if:count_tags() > 0);
        //  var query = this.getQuery(queryTemplate, null, null);


        var queryTemplate = '(node({{bbox}});<;);out meta;';
        var kwargs = {bbox: this.getBox()};
        var query = L.Util.template(queryTemplate, kwargs);
        //console.log("Query: " + query);
        var url = this.getUrl(query);
        //console.log("Query: " + url);
        ra.ajax.getJSON(url, function (err, items) {
            if (err !== null) {
                var msg = "Error: Something went wrong: " + err;
                point.getPopup().setContent(msg);
            } else {
                if (items.elements.length === 0) {
                    var closest = "No " + title + " found within " + self._userOptions.osm.distance + "km";
                    point.getPopup().setContent(closest);
                } else {
                    msg = "<b>" + items.elements.length + " " + title + " found within " + self._userOptions.osm.distance + "km</b>";
                    msg += "<p>" + items.osm3s.copyright + "</p>";
                    point.getPopup().setContent(msg);
                    point.openPopup();
                    for (i = 0; i < items.elements.length; i++) {
                        var item = items.elements[i];
                        self.displayElement(item, single, "");
                    }
                    var bounds = self._mouseLayer.getBounds();
                    self._map.fitBounds(bounds, {padding: [50, 50]});
                }
            }
            setTimeout(function (point) {
                self._map.removeLayer(point);
            }, 6000, point);
        });
    },
    displayElement: function (node, title, myicon) {
        switch (node.type) {
            case "node":
                this.displayNode(node, title, myicon);
                break;
            case "way":
                this.displayWay(node, title, myicon);
                break;
            case "relation":

                break;
        }
    },
    displayNode: function (node, title, myicon) {
        var tags = node.tags;
        var popup = "<b>" + title + "</b><br/>";
        if (typeof tags.name !== 'undefined') {
            popup = "<h4>" + tags.name + "</h4>";
        }
        this.deleteTags(node.tags, ['name', 'amenity', 'fhrs:id', 'source']);
        popup += this.listTags(tags, null);
        var pt = new L.latLng(node.lat, node.lon);
        var marker = L.marker(pt, {icon: myicon}).bindPopup(popup);
        this._mouseLayer.addLayer(marker);
    },
    nodeIcon: function (type) {
        var icon = L.icon({
            iconUrl: ra.baseDirectory() + 'media/com_ra_library/leaflet/images/redmarker.png',
            iconSize: [22, 22],
            iconAnchor: [11, 1],
            popupAnchor: [0, -1]
        });
        switch (type) {
            case "parking":
                icon.options.iconUrl = ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/parking.svg';
                break;
            case "cafes":
                icon.options.iconUrl = ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/cafe.svg';
                break;
            case "pubs":
                icon.options.iconUrl = ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/pub.svg';
                break;
            case "toilets":
                icon.options.iconUrl = ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/toilets.svg';
                break;
            case "bus_stops":
                icon.options.iconUrl = ra.baseDirectory() + 'media/com_ra_library/js/leaflet/images/bus_stop.svg';
                break;
            default:

        }

        return icon;
    },
    displayWay: function (node, title, myicon) {
        var tags = node.tags;
        var popup = "<b>" + title + "</b>";
        if (typeof tags.name !== 'undefined') {
            popup = "<h3>" + tags.name + "</h3>";
        }
        this.deleteTags(node.tags, ['name', 'amenity', 'fhrs:id', 'source']);
        popup += this.listTags(tags);
        var pt = new L.latLng(node.center.lat, node.center.lon);
        var marker = L.marker(pt, {icon: myicon}).bindPopup(popup);
        this._mouseLayer.addLayer(marker);
    },
    deleteTags: function (tags, excludeProperties) {
        var property;
        for (var i in excludeProperties) {
            property = excludeProperties[i];
            if (tags.hasOwnProperty(property))
            {
                delete tags[property];
            }
        }
    },
    listTags: function (tags) {
        var out = "<ul>";
        out += this.getAddr(tags);
        for (var property in tags) {
            if (tags.hasOwnProperty(property))
            {
                var value = tags[property];
                out += "<li>" + this.displayProperty(property) + ": " + this.displayPropertyValue(value) + "</li>";
            }
        }
        out += "</ul>";
        return out;
    },
    displayProperty: function (prop) {
        prop = prop.replace(/:/g, " ");
        prop = prop.replace(/_/g, " ");
        return ra.titleCase(prop);
    },
    displayPropertyValue: function (value) {
        var link = false;
        if (value.toLowerCase().startsWith("http:")) {
            link = true;
        }
        if (value.toLowerCase().startsWith("https:")) {
            link = true;
        }
        if (link) {
            return "<a href=" + value + " target='_blank'>" + value + "</a>";
        }
        value = value.replace(/_/g, " ");
        return value;
    },
    getAddr: function (tags) {
        var out = "";
        out += this.getProperty(tags, "addr:housenumber");
        out += this.getProperty(tags, "addr:housename");
        out += this.getProperty(tags, "addr:street");
        out += this.getProperty(tags, "addr:place");
        out += this.getProperty(tags, "addr:village");
        out += this.getProperty(tags, "addr:suburb");
        out += this.getProperty(tags, "addr:district");
        out += this.getProperty(tags, "addr:subdistrict");
        out += this.getProperty(tags, "addr:city");
        out += this.getProperty(tags, "addr:country");
        out += this.getProperty(tags, "addr:postcode");
        if (out === "") {
            return "";
        }
        return "<li>" + out + "</li>";
    },
    getProperty: function (tags, property) {
        var out = "";
        if (tags.hasOwnProperty(property)) {
            out = " " + tags[property];
            delete tags[property];
        }
        return out;
    },
    getUrl: function (query) {
        return this.apiUrl + encodeURIComponent(query);
    },
    getQuery: function (queryTemplate, tag, type) {
        var lat = this._latlng.lat;
        var lng = this._latlng.lng;
        var kwargs = {tag: tag, type: type, lat: lat, lng: lng, radius: this.getRadius(), bbox: this.getBox()};
        return L.Util.template(queryTemplate, kwargs);
    },
    getBox: function () {
        var bounds = this._map.getBounds();
        return bounds.getSouth().toPrecision(8) + "," + bounds.getWest().toPrecision(8) + "," + bounds.getNorth().toPrecision(8) + "," + bounds.getEast().toPrecision(8);
    },
    getRadius: function () {
        return this._userOptions.osm.distance * 1000;
    },
    optionsForm: function (tag) {
        var title2 = document.createElement('h3');
        title2.textContent = 'Mouse right click/tap hold';
        tag.appendChild(title2);
        var title3 = document.createElement('h2');
        title3.textContent = 'Select which information should be displayed';
        tag.appendChild(title3);
        var div = document.createElement('div');
         div.setAttribute('class', 'ra-mouse-options');
        tag.appendChild(div);
        var so = document.createElement('select');
       // so.setAttribute('class', 'ra-mouse-options');
        so.title = "Select which information is displayed";
        this.rightClickOptions.forEach((group) => {
            var gr = document.createElement("optgroup");
            gr.label = group.group;
            for (var prop in group.items) {
                var option = document.createElement("option");
                option.value = prop;
                option.text = group.items[prop];
                gr.appendChild(option);
            }
            so.appendChild(gr);
        });
        so.value = this.rightClickOption;
        div.appendChild(so);
        var _this = this;
        so.addEventListener("change", function (e) {
            _this.rightClickOption = so.value;
        });
        tag.appendChild(document.createElement('hr'));
        var clearDiv = document.createElement("button");
        clearDiv.innerHTML = "Clear displayed item(s)";
        clearDiv.setAttribute('class', 'rightClick clearItems');
        clearDiv.title = "Clear information currently displayed by mouse right click";
        if (this.noLayers() > 0) {
            clearDiv.classList.add("active");
        }
        var self = this;
        clearDiv.addEventListener('click', function () {
            self._mouseLayer.clearLayers();
            self._places.clearLayers();
            clearDiv.classList.remove('active');
        });
        tag.appendChild(clearDiv);
    },
    settingsForm: function (tag) {
        var comment;
        var title = document.createElement('h3');
        title.textContent = 'Mouse right click/tap and hold';
        tag.appendChild(title);
        comment = document.createElement('p');
        comment.innerHTML = 'This option is used to view location information. ';
        comment.innerHTML += "It can display postcodes, Ramblers' Areas and Groups, meeting/starting places and information from <a href='https://www.openstreetmap.org/about' target='_blank'>Open Street Map</a> ";
        tag.appendChild(comment);
        comment = document.createElement('p');
        comment.innerHTML += "<b>The settings below control how much information is displayed.</b>";
        tag.appendChild(comment);
        tag.appendChild(document.createElement('hr'));
        var hdg2 = document.createElement('h5');
        hdg2.textContent = 'Ramblers Area / Group Options';
        tag.appendChild(hdg2);
        comment = document.createElement('p');
        comment.setAttribute('class', 'smaller');
        comment.textContent = 'Find a Ramblers walking group in your area';
        tag.appendChild(comment);
        this.controls.groups.distance = ra.html.input.number(tag, '', 'Display groups/area within %n km', this._userOptions.groups, 'distance', 0.5, 500, 0.5);
        this.controls.groups.number = ra.html.input.number(tag, '', 'Display nearest %n groups/area.', this._userOptions.groups, 'number', 1, 500, 1);
        tag.appendChild(document.createElement('hr'));
        var hdg1 = document.createElement('h5');
        hdg1.textContent = 'Postcode Options';
        tag.appendChild(hdg1);
        comment = document.createElement('p');
        comment.setAttribute('class', 'smaller');
        comment.textContent = 'Useful for finding correct postcode for your satnav';
        tag.appendChild(comment);
        this.controls.postcodes.distance = ra.html.input.number(tag, '', 'Display postcodes within %n km', this._userOptions.postcodes, 'distance', 0.5, 20, 0.5);
        this.controls.postcodes.number = ra.html.input.number(tag, '', 'Display nearest %n postcodes', this._userOptions.postcodes, 'number', 1, 50, 1);
        tag.appendChild(document.createElement('hr'));
        var hdg3 = document.createElement('h5');
        hdg3.textContent = 'Meeting/Starting Locations Options';
        tag.appendChild(hdg3);
        comment = document.createElement('p');
        comment.setAttribute('class', 'smaller');
        comment.textContent = 'Find locations Ramblers Groups have used to meet or start a walk';
        tag.appendChild(comment);
        this.controls.starting.distance = ra.html.input.number(tag, '', 'Display locations within %n km', this._userOptions.starting, 'distance', 0.5, 20, 0.5);
        this.controls.starting.number = ra.html.input.number(tag, '', 'Display nearest %n locations', this._userOptions.starting, 'number', 5, 500, 5);
        tag.appendChild(document.createElement('hr'));
        var hdg4 = document.createElement('h5');
        hdg4.textContent = 'Open Street Map Options';
        tag.appendChild(hdg4);
        comment = document.createElement('p');
        comment.setAttribute('class', 'smaller');
        comment.textContent = 'This option affects the display of parking, bus stops, cafes, public houses and toilets.';
        tag.appendChild(comment);
        this.controls.osm.distance = ra.html.input.number(tag, '', 'Display items within %n km', this._userOptions.osm, 'distance', 0.5, 5, 0.5);
        this.controls.groups.distance.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this.controls.groups.number.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this.controls.postcodes.distance.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this.controls.postcodes.number.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this.controls.starting.distance.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this.controls.starting.number.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
        this.controls.osm.distance.addEventListener("ra-input-change", function (e) {
            ra.settings.changed();
        });
    },
    resetSettings: function () {
        ra.html.input.numberReset(this.controls.groups.number, 60);
        ra.html.input.numberReset(this.controls.groups.distance, 25);
        ra.html.input.numberReset(this.controls.postcodes.number, 20);
        ra.html.input.numberReset(this.controls.postcodes.distance, 10);
        ra.html.input.numberReset(this.controls.starting.number, 300);
        ra.html.input.numberReset(this.controls.starting.distance, 10);
        ra.html.input.numberReset(this.controls.osm.distance, 2);
    },
    _readSettings: function () {
        ra.settings.read('__rightclick', this._userOptions);
    },
    saveSettings: function (save) {
        ra.settings.save(save, '__rightclick', this._userOptions);
    }
});
L.control.rightclick = function (options) {
    return new L.Control.Rightclick(options);
};