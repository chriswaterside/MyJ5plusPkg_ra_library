var L, document;
L.Control.GpxUpload = L.Control.extend({
    options: {
        title: 'Up load a walking route from a GPX file',
        position: 'bottomright'
    },
    onAdd: function (map) {
        this._map = map;
        this.polylineStyle = {color: '#782327'};
        this.enabled = true;
        this.filename = "";
        var _this = this;
        this._info = {
            name: "",
            desc: "",
            author: "",
            copyright: "",
            date: "",
            links: []
        };
        var container = L.DomUtil.create('div', 'leaflet-control-gpx-upload leaflet-bar leaflet-control');
        this.input = this._createIcon(container);
        container.title = this.options.title;
        this._container = container;
        this.input.addEventListener('input', function (evt) {
            var files = evt.target.files; // FileList object
            var file = files[0];
            var reader = new FileReader();
            // Closure to capture the file information.
            reader.onload = (function (theFile) {
                return function (e) {
                    _this._gpxreader(reader.result, {async: true});
                    _this.input.value = ''; // fix for chrome browser
                };
            })(file);
            // Read in the image file as a data URL.
            reader.readAsText(file);
            _this.filename = file.name;
            _this.filename = _this.filename.replace(/.gpx$/i, '');
            return;
        });
        return container;
    },
    setRouteItems: function (itemsCollection) {
        this._itemsCollection = itemsCollection;
    },
    _createIcon: function (container) {
        var div = L.DomUtil.create('div', 'image-upload', container);
        container.appendChild(div);
        var label = document.createElement('label');
        label.setAttribute('for', "gpx-file-upload");
        div.appendChild(label);
        var icondiv = document.createElement('div');
        icondiv.setAttribute('id', "upload-icon");
        label.appendChild(icondiv);
        var input = document.createElement('input');
        input.setAttribute('id', "gpx-file-upload");
        input.style.display = 'none';
        input.setAttribute('type', "file");
        input.setAttribute('accept', ".gpx");
        div.appendChild(input);
        return input;
    },
    setStatus: function (status) {
        this.enabled = status !== "off";
        if (this.enabled) {
            this.input.disabled = false;
            L.DomUtil.removeClass(this._container, 'ra-upload-toolbar-button-disabled');
        } else {
            this.input.disabled = true;
            L.DomUtil.addClass(this._container, 'ra-upload-toolbar-button-disabled');
        }
    },
    set_polyline_style: function (style) {
        this.polylineStyle = style;
    },
    get_name: function () {
        return this._info.name;
    },
    get_desc: function () {
        return this._info.desc;
    },
    get_author: function () {
        return this._info.author;
    },
    get_copyright: function () {
        return this._info.copyright;
    },
    get_date: function () {
        return this._info.date;
    },
    get_links: function () {
        return this._info.links;
    },
    _gpxreader: function (gpx, options) {
        //    var _MAX_POINT_INTERVAL_MS = 15000;
        var _DEFAULT_MARKER_OPTS = {
            wptIconUrls: {
                '': 'media/com_ra_library/js/leaflet/gpx/images/pin-icon-wpt.png'
            },
            iconSize: [25, 41],
            shadowSize: [41, 41],
            iconAnchor: [12, 41],
            shadowAnchor: [25, 41],
            clickable: false
        };
        var _DEFAULT_GPX_OPTS = {
            parseElements: ['track', 'route', 'waypoint']
        };
        //   options.max_point_interval = options.max_point_interval || _MAX_POINT_INTERVAL_MS;
        options.marker_options = this._ra_gpx_merge_objs(
                _DEFAULT_MARKER_OPTS,
                options.marker_options || {});
        options.gpx_options = this._ra_gpx_merge_objs(
                _DEFAULT_GPX_OPTS,
                options.gpx_options || {});
        L.Util.setOptions(this, options);
        // Base icon class for track pins.
        L.GPXTrackIcon = L.Icon.extend({options: options.marker_options});
        //this._gpx = gpx;

        this._ra_gpx_parse(gpx, options, this.options.async);
    },
    _ra_gpx_merge_objs: function (a, b) {
        var _ = {};
        for (var attr in a) {
            _[attr] = a[attr];
        }
        for (var attr in b) {
            _[attr] = b[attr];
        }
        return _;
    },
    _ra_gpx_parse: function (input, options, async) {
        var _this = this;
        var cb = function (input, options) {
            var gpx = _this.BuildXMLFromString(input);
            _this._ra_gpx_parse_gpx_data(gpx, options);
            _this._itemsCollection.fire('upload:loaded');
        };
        if (input.substr(0, 1) === '<') { // direct XML has to start with a <
            if (async) {
                setTimeout(function () {
                    cb(input, options);
                });
            } else {
                cb(input, options);
            }
        } else {
            ra.showError("File does not appear to be a GPX file");
        }
    },
    _ra_gpx_parse_gpx_data: function (xml, options) {
        var j, i, el;
        var tags = [];
        var that = this;
        var parseElements = options.gpx_options.parseElements;
        if (parseElements.indexOf('route') > -1) {
            tags.push(['rte', 'rtept']);
        }
        if (parseElements.indexOf('track') > -1) {
            tags.push(['trkseg', 'trkpt']);
        }
        this._info.name = '';
        this._info.desc = '';
        this._info.author = '';
        this._info.date = '';
        this._info.links = [];
        var meta = xml.getElementsByTagName('metadata');
        //  var test = this._ra_get_child(meta[0], 'time');
        if (typeof meta !== 'undefined') {
            if (meta.length > 0) {
                this._info.name = this._ra_get_child_text(meta[0], 'name');
                this._info.desc = this._ra_get_child_text(meta[0], 'desc');
                this._info.author = this._ra_get_children_text(meta[0], 'author', 'name');
                this._info.date = this._ra_get_child_text(meta[0], 'time');
                this._info.links = this._ra_get_child_links(meta[0], 'link');
            }
        }
        if (that._info.name === "") {
            this._info.name = this.filename;
        }


        for (j = 0; j < tags.length; j++) {
            el = xml.getElementsByTagName(tags[j][0]);
            for (i = 0; i < el.length; i++) {
                var coords = this._ra_gpx_parse_trkseg(el[i], tags[j][1]);
                if (coords.length === 0)
                    continue;
                // add track
                var latlngs = ra.map.removeShortSegments(coords);
                var l = new L.Polyline(latlngs, this.polylineStyle);
                this._itemsCollection.addLayer(l);
                this._itemsCollection.fire('upload:addline', {line: l});
            }
        }

        // parse waypoints and add markers for each of them
        if (parseElements.indexOf('waypoint') > -1) {
            el = xml.getElementsByTagName('wpt');
            for (i = 0; i < el.length; i++) {
                var ll = new L.LatLng(
                        el[i].getAttribute('lat'),
                        el[i].getAttribute('lon'));
                var name = this._ra_get_text(el[i], 'name');
                var desc = this._ra_get_text(el[i], 'desc');
                var symKey = this._ra_get_text(el[i], 'sym');
                // add WayPointMarker, based on "sym" element if avail and icon is configured
                var symIcon;
                if (options.marker_options.wptIcons && options.marker_options.wptIcons[symKey]) {
                    symIcon = options.marker_options.wptIcons[symKey];
                } else if (options.marker_options.wptIconUrls && options.marker_options.wptIconUrls[symKey]) {
                    symIcon = new L.GPXTrackIcon({iconUrl: options.marker_options.wptIconUrls[symKey]});
                } else {
                    //   console.log('No icon or icon URL configured for symbol type "' + symKey
                    //           + '"; ignoring waypoint.');
                    symIcon = new L.GPXTrackIcon({iconUrl: options.marker_options.wptIconUrls['']});
                }

                var marker = new L.Marker(ll, {
                    clickable: true,
                    title: name,
                    icon: symIcon
                });
                marker.name = this.escapeHtml(name);
                marker.desc = this.escapeHtml(desc);
                marker.symbol = this.escapeHtml(symKey);
                marker.bindPopup("<b>" + name + "</b>" + (desc.length > 0 ? '<br>' + desc : '')).openPopup();
                this._itemsCollection.fire('upload:addpoint', {point: marker, point_type: 'waypoint'});
                this._itemsCollection.addLayer(marker);
                //  layers.push(marker);
            }
        }


    },
    _ra_get_child_text: function (elem, name) {
        var children = elem.childNodes;
        var _this = this;
        this.result = "";
        this.findname = name;
        if (typeof children !== 'undefined') {

            children.forEach(
                    function (node, currentIndex, listObj) {
                        var find = _this.findname;
                        if (node.nodeName === find) {
                            _this.result = node.textContent;
                        }
                    },
                    "name"
                    );
        }
        return  this._unEscapeChars(this.result);
    },
    _ra_get_child_links: function (elem) {
        var children = elem.childNodes;

        var that = this;
        that.result = [];
        that.findname = "link";
        if (typeof children !== 'undefined') {

            children.forEach(
                    function (node, currentIndex, listObj) {
                        var find = that.findname;
                        if (node.nodeName === find) {
                            var href = node.getAttribute('href');
                            if (href !== null) {
                                var text = "";
                                var temp = node.firstElementChild;
                                if (temp !== null) {
                                    text = that._unEscapeChars(temp.textContent);
                                }
                                var item = {href: href, text: text};
                                that.result.push(item);
                            }
                        }
                    },
                    "name"
                    );
        }
        return  that.result;
    },
    _ra_get_children_text: function (elem, name1, name2) {
        var child = this._ra_get_child(elem, name1);
        if (typeof child !== 'undefined') {
            return  this._ra_get_child_text(child, name2);
        }
        return "";
    },
    _unEscapeChars: function (text) {
        text = text.replace("&quot;", '"');
        text = text.replace("&apos;", "'");
        text = text.replace("&lt;", '<');
        text = text.replace("&gt;", '>');
        text = text.replace("&amp;", '&'); // do this last so others are not changed

        return text;
    },
    escapeHtml: function (unsafe) {
        return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
    },
    _ra_get_child: function (elem, name) {
        var children = elem.childNodes;

        var that = this;
        this.result = undefined;
        this.findname = name;
        if (typeof children !== 'undefined') {

            children.forEach(
                    function (node, currentIndex, listObj) {
                        var find = that.findname;
                        // console.log(node + ', ' + currentIndex + ', ' + this);
                        if (node.nodeName === find) {
                            that.result = node;
                        }
                    },
                    "name"
                    );
        }
        return that.result;
    },
    _ra_get_text: function (elem, tag) {
        var textEl = elem.getElementsByTagName(tag);
        var text = '';
        if (textEl.length > 0) {
            text = textEl[0].textContent;
        }
        return this._unEscapeChars(text);
    }
    ,
    _ra_gpx_parse_trkseg: function (line, tag) {
        var el = line.getElementsByTagName(tag);
        if (!el.length)
            return [];
        var coords = [];
        var last = null;
        for (var i = 0; i < el.length; i++) {
            var tag;
            var ll = new L.LatLng(el[i].getAttribute('lat'), el[i].getAttribute('lon'), -999);
            last = ll;
            coords.push(ll);
        }
        return coords;
    },
    BuildXMLFromString: function (text) {

        var parser = new DOMParser();
        var xmlDoc = null;
        try {
            xmlDoc = parser.parseFromString(text, "text/xml");
        } catch (e) {
            ra.showError("XML parsing error.");
            return null;
        }
        var errorMsg = null;
        if (xmlDoc.parseError && xmlDoc.parseError.errorCode !== 0) {
            errorMsg = "XML Parsing Error: " + xmlDoc.parseError.reason
                    + " at line " + xmlDoc.parseError.line
                    + " at position " + xmlDoc.parseError.linepos;
        } else {
            if (xmlDoc.documentElement) {
                if (xmlDoc.documentElement.nodeName === "parsererror") {
                    errorMsg = xmlDoc.documentElement.childNodes[0].nodeValue;
                }
            } else {
                errorMsg = "XML Parsing Error!";
            }
        }

        if (errorMsg) {
            ra.showError(errorMsg);
            return null;
        }

        return xmlDoc;
    }
});
L.control.gpxupload = function (options) {
    return new L.Control.GpxUpload(options);
};