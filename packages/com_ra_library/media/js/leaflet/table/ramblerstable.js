var L, ra, OsGridRef;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.display) === "undefined") {
    ra.display = {};
}
ra.display.tableList = (function () {
    var tableList = {};

    tableList.display = function (options, data) {
        this.list = null;
        this.options = options;
        this.data = data;
        this.items = this.data.list.items;
        this.numberOfRows = this.items[0].values.length;
        this.recordDiv;
        this.masterdiv = document.getElementById(options.divId);
        this.iconpath = ra.baseDirectory() + "media/com_ra_library/gpxsymbols/";
        this.defaultIcon = L.icon({
            iconUrl: ra.baseDirectory() + "media/com_ra_library/images/marker-route.png",
            iconSize: [33, 50],
            iconAnchor: [16, 47],
            popupAnchor: [0, -44]
        });

        this.load = function ( ) {
            var _this = this;
            var mapping = this.canDisplayMap();
            var options = {tabClass: 'tableDisplay',
                tabs: {map: {title: 'Map', enabled: mapping, staticContainer: true},
                    list: {title: 'List', staticContainer: true}}};


            this.tabs = new ra.tabs(this.masterdiv, options);

            this.tabs.display();
            if (mapping) {
                var mapDiv = this.tabs.getStaticContainer('map');
                this._displayMap(mapDiv);
            }
            var listDiv = this.tabs.getStaticContainer('list');
            this._createTable(listDiv, this.items);
//            setTimeout(function () {
//                self.displayCsvData(); // lets the map/list tabs be displayed straight away
//            }, 0);
            this.masterdiv.addEventListener("displayTabContents", function (e) {
                if (e.tabDisplay.tab === 'map') {
                    _this.map.invalidateSize();
                }
            });
        };
        this.addTabs = function (mapping) {

            var options = {div: this.masterdiv,
                tabClass: 'tableDisplay',
                tabs: {map: {title: 'Map', enabled: mapping, staticContainer: true},
                    list: {title: 'List', staticContainer: true}}};


            this.tabs = new ra.tabs(options);
            this.tabs.display();
        };
        this._createTable = function (tag, items) {
            var tags = [
                {name: 'legend', parent: 'root', tag: 'div'},
                {name: 'content', parent: 'root', tag: 'div'}
            ];
            var elements = ra.html.generateTags(tag, tags);
            elements.legend.innerHTML = "<p class='noprint'>Click on item to display full details of walk</p>";

            var table = new ra.paginatedTable(elements.content);
            this._setTableHeading(table, items);
            this._addTableRows(table, items);
            table.tableEnd();
        };
        this._setTableHeading = function (table, items) {
            var format = [];
            items.forEach((item) => {
                if ('table' in item) {
                    var formatItem = null;
                    if (item.table) {
                        formatItem = {title: item.name,
                            options: {align: item.align},
                            field: {type: item.type,
                                filter: item.filter,
                                sort: item.sort}};
                        format.push(formatItem);
                        switch (item.type) {
                            case 'link':
                            case 'textlink':
                            case 'exturl':
                                formatItem.field.type = 'text';
                                break;
                            case 'datetime':
                                formatItem.field.type = 'date';
                        }
                    }
                    item.format = formatItem;
                }
            });
            table.tableHeading(format);
        };
        this._addTableRows = function (table, items) {
            var no, index;
            var item;
            for (no = 0; no < this.numberOfRows; ++no) {
                table.tableRowStart();
                for (index = 0; index < items.length; ++index) {
                    item = items[index];
                    if (item.table) {
                        var value = this.displayItem(null, item.type, item.values[no]);
                        var td = table.tableRowItem(value, item.format);
                        if (item.linkmarker) {
                            var self = this;
                            td.classList.add('pointer');
                            td.classList.add('link');
                            td.setAttribute('title', 'Click to view item on map');
                            td.setAttribute('data-marker-number', no);
                            td.addEventListener("click", function (event) {
                                self.selectMarker(event);
                            });
                        }
                    }
                }
                table.tableRowEnd();
            }
        };

        this._displayMap = function (tag) {
            var tags = [
                {name: 'map', parent: 'root', tag: 'div'},
                {name: 'table', parent: 'root', tag: 'div'}
            ];
            var elements = ra.html.generateTags(tag, tags);
            this.recordDiv = elements.table;
            this.lmap = new ra.leafletmap(elements.map, this.options);
            this.lmap.display();
            this.map = this.lmap.map();
            this.cluster = new ra.map.cluster(this.map);
            this.addMarkers();
            this.cluster.zoomAll({padding: [30, 30]});
        };
        this.canDisplayMap = function () {
            this.data.list.longitude = -1;
            this.data.list.latitude = -1;
            for (var index = 0; index < this.data.list.items.length; ++index) {
                var item = this.data.list.items[index];
                if (item.longitude) {
                    this.data.list.longitude = index;
                }
                if (item.latitude) {
                    this.data.list.latitude = index;
                }
            }
            if (this.data.list.longitude > -1 && this.data.list.latitude > -1) {
                return true;
            }
            // search for gridref
            this.data.list.gridref = -1;
            for (var index = 0; index < this.data.list.items.length; ++index) {
                var item = this.data.list.items[index];
                if (item.gridref) {
                    this.data.list.gridref = index;
                    this.calculateLatLng();
                    return true;
                }
            }
            return false;
        };
        this.calculateLatLng = function () {
            var items = this.data.list.items;
            var no = this.data.list.gridref;
            var grvalues = items[no].values;
            var lat = [];
            var long = [];
            var newitem;
            for (var index = 0; index < grvalues.length; ++index) {
                try {
                    var grid = OsGridRef.parse(grvalues[index]);
                    var latlng = OsGridRef.osGridToLatLon(grid);
                    lat.push(latlng.lat.toFixed(6));
                    long.push(latlng.lon.toFixed(6));
                } catch (err) {
                    lat.push(0);
                    long.push(0);
                }
            }
            newitem = this.addNewItem("Latitude", lat);
            newitem.latitude = true;
            newitem.table = true;
            newitem.align = 'right';
            newitem = this.addNewItem("Longitude", long);
            newitem.longitude = true;
            newitem.table = true;
            newitem.align = 'right';
            this.data.list.latitude = this.data.list.items.length - 2;
            this.data.list.longitude = this.data.list.items.length - 1;
            this.displayMap = true;
        };
        this.addNewItem = function (name, values) {
            var newitem = {name: "", sort: false, table: false, filter: false, popup: false, gridref: false};
            newitem.name = name;
            newitem.values = values;
            newitem.latitude = false;
            newitem.longitude = false;
            newitem.easting = false;
            newitem.northing = false;
            newitem.linkmarker = false;
            newitem.type = "text";
            this.data.list.items.push(newitem);

            return newitem;
        };
        this.addMarkers = function () {
            for (var index = 0; index < this.numberOfRows; ++index) {
                this.addMarker(index);
            }
            this.cluster.addClusterMarkers();
        };
        this.addMarker = function (no) {
            var $popup, $lat, $long, title = "";
            var $all = true;
            $popup = "<div style='font-size:120%'>";
            var items = this.data.list.items;
            for (var index = 0; index < items.length; ++index) {
                if (items[index].popup) {
                    if (items[index].values[no] !== "") {
                        if (items[index].values[no] !== "") {
                            $popup += this.displayItem(items[index].name, items[index].type, items[index].values[no]);
                        }
                    }
                } else {
                    $all = false;
                }
            }
            if (!$all) {
                $popup += "All information for location is below map";
            }
            $popup += "</div>";
            for (var index = 0; index < items.length; ++index) {
                if (title === "") {
                    if (items[index].linkmarker) {
                        title = items[index].values[no];
                    }
                }
            }
            $lat = parseFloat(items[this.data.list.latitude].values[no]);
            $long = parseFloat(items[this.data.list.longitude].values[no]);
            if (ra.isNumber($lat) && ra.isNumber($long)) {
                var icon = this.getMarkerIcon(no);
                var marker = L.marker([$lat, $long], {icon: icon, riseOnHover: true, title: title});
                var $pop = $popup.replace(/&quot;/g, '"'); // replace quots in popup text
                marker.bindPopup($pop);
                marker.ramblers_id = no;
                var self = this;
                marker.on('popupopen', function (popup) {
                    var id = popup.sourceTarget.ramblers_id;
                    self.displayRecord(id);
                });
                marker.on('popupclose', function (popup) {
                    self.recordDiv.innerHTML = "";
                });
                this.cluster.markerList.push(marker);
            }
        };
        this.getMarkerIcon = function (no) {
            var icon = this.defaultIcon;
            if (this.data.markerOptions === null) {
                return icon;
            }
            var icons = this.data.markerOptions.icons;
            if (icons) {
                if (icons.type && icons.column) {
                    var title = icons.column;
                    this.data.list.items.forEach(item => {
                        if (item.name === icons.column) {
                            title = item.values[no];
                        }
                    });
                    switch (icons.type) {
                        case "text":
                            xclass = icons.class;
                            var xclass = "ra-table-text-marker";
                            if (icons.class) {
                                xclass = icons.class;
                            }
                            icon = L.divIcon({className: xclass, iconSize: null, html: title, popupAnchor: [10, 30]});
                        case "icon":
                            xclass = icons.class;
                            var xclass = "ra-table-icon-marker";
                            if (icons.class) {
                                xclass = icons.class;
                            }
                            if (icons.values) {
                                if (icons.values[title]) {
                                    icon = L.icon({
                                        iconUrl: this.iconpath + icons.values[title],
                                        iconSize: [33, 50],
                                        iconAnchor: [16, 47],
                                        popupAnchor: [0, -44]
                                    });
                                }
                            }
                    }
                }
            }
            return icon;
        };
        this.displayRecord = function (no) {
            var $details;
            $details = "<div style='font-size:120%'>";
            var items = this.data.list.items;
            for (var index = 0; index < items.length; ++index) {
                if (items[index].values[no] !== "") {
                    $details += this.displayItem(items[index].name, items[index].type, items[index].values[no]);
                }
            }
            $details += "</div>";
            this.recordDiv.innerHTML = $details;
        };
        this.selectMarker = function (event) {
            var no = parseInt(event.target.getAttribute('data-marker-number'));
            this.tabs.clickToTab('map');
            for (var index = 0; index < this.cluster.markerList.length; ++index) {
                var marker = this.cluster.markerList[index];
                if (marker.ramblers_id === no) {
                    this.cluster.markersCG.zoomToShowLayer(marker, this.openPopup(marker));
                    this.map.panTo(marker.getLatLng());
                    break;
                }
            }
        };
        this.openPopup = function (marker) {
            setTimeout(function () {
                marker.openPopup();
            }, 500);
        };
        this.displayItem = function (name, type, value) {
            var out = "";
            if (value === "") {
                return out;
            }
            if (name !== null)
                out = "<b>" + name + ": </b>";
            switch (type) {
                case 'link':
                    return out + '<a href="' + value + '" target="_blank">Link</a><br/>';
                case 'textlink':
                    return  out + '<a href="' + value + '" target="_blank">' + value + '</a><br/>';
                case 'exturl':
                    if (!value.includes("://")) {
                        return  out + '<a href="https://' + value + '" target="_blank">' + value + '</a><br/>';
                    }
                    return  out + '<a href="' + value + '" target="_blank">' + value + '</a><br/>';
                default:
                    return out + value + '<br/>';
            }
        };
    };
    return tableList;
}
()); 