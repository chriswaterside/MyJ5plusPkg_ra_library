var L, ra, document;
L.Control.GpxDownload = L.Control.extend({
    options: {
        title: 'Download your walking routes as a GPX file',
        titledisabled: 'No routes or markers defined',
        position: 'bottomright'
    },
    onAdd: function (map) {
        this._map = map;
        this.enabled = false;
        this.supported = true;
        this._info = {
            name: "",
            desc: "",
            author: "",
            copyright: "",
            links: [],
            gpxTrack: false,
            date: ""
        };
        var containerAll = L.DomUtil.create('div', 'leaflet-control-gpx-download ra-download-toolbar-button-disabled');
        var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control', containerAll);
        this._createIcon(container);
        this._container = container;
        L.DomEvent.disableClickPropagation(containerAll);
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.on(this.link, 'click', this._downloadGpx, this);
        this._appendButtons(containerAll);
        this.holder.style.display = "none";
        this.popup = L.popup({minWidth: 550, maxWidth: 550}).setLatLng([0, 0]).setContent('dummy');
        return containerAll;
    },
    _appendButtons: function (container) {
        this.holder = L.DomUtil.create('div', 'leaflet-gpx-options', container);
        var element = L.DomUtil.create('div', 'details', this.holder);
        element.innerHTML = "Edit Details";
        L.DomEvent.addListener(element, 'click', this._details, this);
        element = L.DomUtil.create('div', 'save', this.holder);
        element.innerHTML = "Save";
        L.DomEvent.addListener(element, 'click', this._save, this);
        element = L.DomUtil.create('div', 'cancel', this.holder);
        element.innerHTML = "Cancel";
        L.DomEvent.addListener(element, 'click', this._cancel, this);
    },
    setRouteItems: function (itemsCollection) {
        this._itemsCollection = itemsCollection;
    },
    set_name: function (value) {
        this._info.name = value;
    },
    set_desc: function (value) {
        this._info.desc = value;
    },
    set_author: function (value) {
        this._info.author = value;
    },
    set_copyright: function (value) {
        this._info.copyright = value;
    },
    set_date: function (value) {
        this._info.date = value;
    },
    set_links: function (value) {
        this._info.links = value;
    },
    setStatus: function (status) {
        this.enabled = true;
        if (status === "off") {
            this.enabled = false;
        }
        if (status === "auto") {
            this.enabled = this._itemsCollection.getLayers().length !== 0;
        }
        if (this.supported === false) {
            this.enabled = false;
        }
        if (this.enabled) {
            L.DomUtil.removeClass(this._container, 'ra-download-toolbar-button-disabled');
            this.link.title = this.options.title;
        } else {
            L.DomUtil.addClass(this._container, 'ra-download-toolbar-button-disabled');
            this.link.title = this.options.titledisabled;
        }
    },
    _createIcon: function (container) {
        var icon = L.DomUtil.create('a', '', container);
        this.link = icon;
        this.link.id = "leaflet-gpx-download";
        this.link.title = this.options.title;
        return this.link;
    },
    _downloadGpx: function (e) {
        if (this.enabled) {
            this._map.fire('download:started');
            this.holder.style.display = "inline-block";
        }
    },
    _cancel: function (evt) {
        this._map.fire('download:cancelled');
        this.holder.style.display = "none";
    },
    _details: function (evt) {
        var _this = this;
        this._map.fire('download:cancelled');
        var contentTag = document.createElement('div');
        _this.modal = ra.modals.createModal(contentTag, false);
        this._addDetailsForm(contentTag);
    },
    _addDetailsForm: function (formtag) {
        var heading = document.createElement('h2');
        heading.textContent = 'GPX File Details';
        formtag.appendChild(heading);
        this._addText(formtag, 'ra-gpx-popup', 'File Name/Title', this._info, 'name');
        this.addTextArea(formtag, 'ra-gpx-popup', 'File Description', 3, this._info, 'desc');
        this._addText(formtag, 'ra-gpx-popup', 'Author/Leader', this._info, 'author');
        this._addDate(formtag, 'ra-gpx-popup', 'Date', this._info, 'date');
        this.linksDiv = document.createElement('div');
        formtag.appendChild(this.linksDiv);
        this._addDetailsFormLinks(this.linksDiv);
        this._addCheckbox(formtag, 'ra-gpx-popup', 'Save As GPX Track rather than GPX Route', this._info, 'gpxTrack');
    },
    _addDetailsFormLinks: function (tagname) {
        var _label = document.createElement('label');
        _label.textContent = 'Links';
        _label.style.fontWeight = 'bold';
        tagname.appendChild(_label);
        var i, len;
        for (i = 0, len = this._info.links.length; i < len; i++) {
            var link = this._info.links[i];
            this._addMetaLink(tagname, 'ra-gpx-popup', link, 'text', 'href', i === len - 1);
        }
        if (this._info.links.length === 0) {
            this._addMetaLink(tagname, 'ra-gpx-popup', null, null, null, true);
        }

    },
    _save: function (e) {
        if (this.enabled) {
            this.holder.style.display = "none";
            var drawnItems = this._itemsCollection;
            var hasItems = drawnItems.getLayers().length !== 0;
            if (!hasItems) {
                ra.showError('No routes or markers defined');
            } else {
                var data = this._createGPXdata();
                try {
                    var blob = new Blob([data], {type: "application/gpx+xml;charset=utf-8"});
                    var name = this._info.name;
                    name = name.replace(/&amp;/g, " "); // remove special chars
                    name = name.replace(/&quot;/g, " ");
                    name = name.replace(/&apos;/g, " ");
                    name = name.replace(/&lt;/g, " ");
                    name = name.replace(/&gt;/g, " ");
                    name = name.replace(/\s+/g, '_');
                    name = name.replace(/[^a-zA-Z0-9_]/g, "");
                    if (name === "") {
                        name = "route.gpx";
                    } else {
                        name = name + ".gpx";
                    }
                    saveAs(blob, name);
                } catch (e) {
                    ra.showMsg('Your web browser does not support his option!');
                }
            }
        }
    },
    _createGPXdata: function () {
        var gpxData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        gpxData += '<gpx' + "\n";
        gpxData += 'version="1.1"' + "\n";
        gpxData += 'creator="Ramblers-Webs - http://www.ramblers-webs.org.uk"' + "\n";
        gpxData += 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' + "\n";
        gpxData += 'xmlns="http://www.topografix.com/GPX/1/1"' + "\n";
        gpxData += 'xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">' + "\n";
        gpxData += this._addMetaData();
        var _this = this;
        this._itemsCollection.eachLayer(function (layer) {
            if (layer instanceof L.Marker) {
                gpxData += _this._addMarker(layer);
            }
            if (layer instanceof L.Polyline) {
                gpxData += _this._addPolyline(layer);
            }
        });
        gpxData += '</gpx>';
        return gpxData;
    },
    _escapeChars: function (text) {
        text = text.replace(/&/g, "&amp;"); // do this first so others are not changed
        text = text.replace(/"/g, "&quot;");
        text = text.replace(/'/g, "&apos;");
        text = text.replace(/</g, "&lt;");
        text = text.replace(/>/g, "&gt;");
        return text;
    },
    _addMetaData: function () {
        var out = "<metadata>";
        if (this._info.name !== "") {
            out += "<name>" + this._escapeChars(this._info.name) + "</name>";
        }
        if (this._info.desc !== "") {
            out += "<desc>" + this._escapeChars(this._info.desc) + "</desc>";
        }
        if (this._info.author !== "") {
            out += "<author><name>" + this._escapeChars(this._info.author) + "</name></author>";
        }
        if (this._info.date !== "") {
            out += "<time>" + this._info.date + "</time>";
        }
        var i, len;
        for (i = 0, len = this._info.links.length; i < len; i++) {
            var link = this._info.links[i];
            if (this._validURL(link.href)) {
                out += "<link href=\"" + link.href + "\">";
                out += "<text>" + this._escapeChars(link.text) + "</text></link>";
            }
        }
        out += "</metadata>\n";
        return out;
    },
    _validURL: function (str) {
        var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
                '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
                '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
                '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
                '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
                '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
        return !!pattern.test(str);
    },
    _addMarker: function (marker) {

        var data = '<wpt lat="' + marker._latlng.lat + '" lon="' + marker._latlng.lng + '">' + "\n";
        if (marker.hasOwnProperty('name')) {
            data += '<name>' + this._escapeChars(marker.name) + '</name>' + "\n";
        }
        if (marker.hasOwnProperty('desc')) {
            data += '<desc>' + this._escapeChars(marker.desc) + '</desc>' + "\n";
        }
        if (marker.hasOwnProperty('symbol')) {
            data += '<sym>' + this._escapeChars(marker.symbol) + '</sym>' + "\n";
        }
        data += '</wpt>\n';
        return data;
    },
    _addPolyline: function (polyline) {
        var latlngs;
        if (this._info.gpxTrack === true) {
            var data = '<trk>' + "\n";
            data += '<trkseg>' + "\n";
            latlngs = polyline.getLatLngs();
            data += this._listtrack(latlngs);
            data += '</trkseg>' + "\n";
            data += '</trk>' + "\n";
        } else {
            var data = '<rte>' + "\n";
            latlngs = polyline.getLatLngs();
            data += this._listroute(latlngs);
            data += '</rte>' + "\n";
        }
        return data;
    },
    _listtrack: function (latlngs) {
        var i, len;
        i = 0;
        var text = "";
        for (i = 0, len = latlngs.length; i < len; i++) {
            text += '<trkpt lat="' + latlngs[i].lat + '" lon="' + latlngs[i].lng + '">\n';
            if (latlngs[i].alt !== -999) {
                text += ' <ele>' + latlngs[i].alt + '</ele>\n';
            }
            text += '</trkpt>\n';
        }
        return text;
    },
    _listroute: function (latlngs) {
        var i, len;
        i = 0;
        var text = "";
        for (i = 0, len = latlngs.length; i < len; i++) {
            text += '<rtept lat="' + latlngs[i].lat + '" lon="' + latlngs[i].lng + '">\n';
            if (latlngs[i].alt !== -999) {
                text += ' <ele>' + latlngs[i].alt + '</ele>\n';
            }
            text += ' </rtept>\n';
        }
        return text;
    },
    _addText: function (tag, divClass, label, raobject, property) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var div = document.createElement('div');
        div.setAttribute('class', divClass);
        div.setAttribute('class', 'ra-inline');
        itemDiv.appendChild(div);
        var _label = document.createElement('label');
        _label.textContent = label;
        div.appendChild(_label);
        var inputTag = document.createElement('input');
        inputTag.setAttribute('type', 'text');
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        div.appendChild(inputTag);
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            //       inputTag.value = _this._unEscapeChars(raobject[property]);
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("input", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        return inputTag;
    },
    addTextArea: function (tag, divClass, label, rows, raobject, property) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.textContent = label;
        itemDiv.appendChild(_label);
        var inputTag = document.createElement('textarea');
        inputTag.setAttribute('class', 'ra-inline');
        inputTag.setAttribute('rows', rows);
        inputTag.style.width = '95%';
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        itemDiv.appendChild(inputTag);
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("change", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        return inputTag;
    },
    _addDate: function (tag, divClass, label, raobject, property) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var div = document.createElement('div');
        div.setAttribute('class', divClass);
        div.setAttribute('class', 'ra-inline');
        itemDiv.appendChild(div);
        var _label = document.createElement('label');
        _label.textContent = label;
        div.appendChild(_label);
        var inputTag = document.createElement('input');
        inputTag.setAttribute('type', "date");
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        div.appendChild(inputTag);
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property].substring(0, 10);
        }
        inputTag.addEventListener("change", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value + "T12:00:00Z";
        });
        return inputTag;
    },
    _addCheckbox: function (tag, divClass, label, raobject, property) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var div = document.createElement('div');
        div.setAttribute('class', divClass);
        div.setAttribute('class', 'ra-inline');
        itemDiv.appendChild(div);
        var _label = document.createElement('label');
        _label.textContent = label;
        div.appendChild(_label);
        var inputTag = document.createElement('input');
        inputTag.setAttribute('type', 'checkbox');
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        div.appendChild(inputTag);
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("click", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.checked;
        });
        return inputTag;
    },
    _addMetaLink: function (tag, divClass, raobject, property1, property2, last = false) {
        var _this = this;
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var div = document.createElement('div');
        div.setAttribute('class', divClass);
        itemDiv.appendChild(div);
        if (raobject !== null) {
            var inputName = document.createElement('input');
            inputName.setAttribute('type', 'text');
            inputName.setAttribute('placeholder', 'Name of link');
            inputName.raobject = raobject;
            inputName.raproperty1 = property1;
            div.appendChild(inputName);
            var inputLink = document.createElement('input');
            inputLink.setAttribute('type', 'text');
            inputLink.setAttribute('placeholder', 'Link URL');
            inputLink.raobject = raobject;
            inputLink.raproperty2 = property2;
            div.appendChild(inputLink);
            if (raobject.hasOwnProperty(property1)) {  // Initialise value
                inputName.value = raobject[property1];
            }
            inputName.addEventListener("input", function (e) {
                e.target.raobject[e.target.raproperty1] = e.target.value;
            });
            if (raobject.hasOwnProperty(property2)) {  // Initialise value
                inputLink.value = raobject[property2];
            }
            inputLink.addEventListener("input", function (e) {
                e.target.raobject[e.target.raproperty2] = e.target.value;
                if (_this._validURL(e.target.value)) {
                    e.target.style.color = "#000000";
                } else {
                    e.target.style.color = "#CC0000";
                }
            });
            var delButton = document.createElement('button');
            delButton.setAttribute('type', 'button');
            delButton.textContent = '-';
            delButton.raobject = raobject;
            div.appendChild(delButton);
            delButton.addEventListener("click", function (e) {
                var item = e.currentTarget.raobject;
                _this._deleteLink(item);
                _this._redisplayLinks();
            });
        }
        if (last) {
            var newButton = document.createElement('button');
            newButton.setAttribute('type', 'button');
            newButton.textContent = '+';
            div.appendChild(newButton);
            newButton.addEventListener("click", function (e) {
                var item = {href: '', text: ''};
                _this._info.links.push(item);
                _this._redisplayLinks();
            });
        }
        return;
    },
    _redisplayLinks: function () {
        var tag = this.linksDiv;
        tag.innerHTML = '';
        this._addDetailsFormLinks(tag);
    },
    _deleteLink: function (item) {
        var items = this._info.links;
        for (var i = 0; i < items.length; i++) {
            if (items[i] === item) {
                items.splice(i, 1);
            }
        }
    }
});
L.control.gpxdownload = function (options) {
    return new L.Control.GpxDownload(options);
};
