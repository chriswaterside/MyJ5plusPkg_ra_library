var document, ra, FullCalendar;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}
ra.walkseditor.viewWalks = function (tag, mapOptions, programme, loggedOn = false) {
    this.masterdiv = tag;
    this.programme = programme;
    this.mapOptions = mapOptions;
    this.allowWMExport = false;
    this._loggedOn = loggedOn;
    this.filter = null;
    this.settings = {
        currentDisplay: "Table",
        singleCategory: true
    };
    this.tableColumns = [
        {title: 'Status'},
        {title: 'Date', field: {type: 'date', sort: true, filter: false, defaultSort: 'Desc'}},
        {title: 'Meeting', field: {type: 'text', sort: false, filter: false}},
        {title: 'Start', field: {type: 'text', sort: false, filter: false}},
        {title: 'Title', field: {type: 'text', sort: false, filter: true}},
        {title: 'Difficulty', field: {type: 'text', sort: false, filter: false}},
        {title: 'Contact', field: {type: 'text', sort: true, filter: false}}
    ];

    this.load = function () {
        var self = this;
        this.option = 'table';
//        var head = document.createElement('h3');
//        head.innerHTML = 'Walks editor: ';
//
//        tag.appendChild(head);
        var options = {
            tabClass: 'walksView',
            tabs: {table: {title: 'Table'},
                list: {title: 'List'},
                map: {title: 'Map'},
                calendar: {title: 'Calendar'}}};
        this.programme.setFilters(tag);
        this.tabs = new ra.tabs(tag, options);
        document.addEventListener("reDisplayWalks", function () {
            self.programme.setWalkDisplay();
            self.displayWalks(self.displayTag, self.option);
        });
        this.masterdiv.addEventListener("displayTabContents", function (e) {
            self.option = e.tabDisplay.tab;
            self.displayTag = e.tabDisplay.displayInElement;
            console.log('Walks level ' + self.option);
            self.displayWalks(self.displayTag, self.option);
        });

        this.tabs.display();
        this.programme.setFilterDefaults();
    };

    this.displayWalks = function (tag, option) {
        tag.innerHTML = '';
        switch (option) {
            case "table":
                this.displayTable(tag);
                break;
            case "list":
                this.displayList(tag);
                break;
            case "map":
                this.displayMap(tag);
                break;
            case "calendar":
                this.displayCalendar(tag);
                break;
        }
    };

    this.displayDiagnostics = function () {
        var tag = document.createElement('div');
        var heading = document.createElement('h3');
        tag.appendChild(heading);
        var div = document.createElement('div');
        tag.appendChild(div);
        div.innerHTML = "<pre>" + JSON.stringify(this.programme, undefined, 4) + "</pre>";
        ra.modals.createModal(tag, true);
    };

    this.displayTable = function (tag) {
        var walks = this.programme.getWalks();
        var tags = [
            {name: 'legend', parent: 'root', tag: 'div'},
            {name: 'content', parent: 'root', tag: 'div'}
        ];
        var elements = ra.html.generateTags(tag, tags);
        var comment = document.createElement('p');
        comment.innerHTML = "Click on walk to view details";
        elements.legend.appendChild(comment);
        var table = new ra.paginatedTable(elements.content);
        table.tableHeading(this.tableColumns);
        for (var walk of walks) {
            if (walk.displayWalk) {
                this.displayWalkRow(table, walk, '$class');
            }
        }
        table.tableEnd();
    };

    this.displayList = function (tag) {
        var walks = this.programme.getWalks();
        var tags = [
            {name: 'legend', parent: 'root', tag: 'div'},
            {name: 'content', parent: 'root', tag: 'div'}
        ];
        var elements = ra.html.generateTags(tag, tags);
        elements.legend.innerHTML = "<p>Click on item to display full details of walk</p>";
        var list = new ra.paginatedList(elements.content);
        for (var walk  of walks) {
            if (walk.displayWalk) {
                var walkDiv = document.createElement('div');
                var statusTag = document.createElement('span');
                statusTag.classList.add('ra-status');
                statusTag.innerHTML = walk.getStatusCategory(' ', this.settings.singleCategory);
                walk.addDisplayClasses(statusTag.classList);
                walkDiv.appendChild(statusTag);


                walkDiv.setAttribute('title', 'View walk details');
                walkDiv.ra = {walk: walk};
                walkDiv.addEventListener('click', function (e) {
                    e.currentTarget.ra.walk.previewWalk();
                });
                walkDiv.classList.add('pointer');
                var span = document.createElement('span');
                span.innerHTML = walk.getWalkDate('list') + ', ' +
                        walk.getWalkMeeting('list') + ', ' +
                        walk.getWalkStart('list') + ', ' +
                        walk.getWalkTitle() + ', ' +
                        walk.getWalkDifficulty('list') +
                        walk.getWalkContact('list');
                walkDiv.appendChild(span);
                list.listItem(walkDiv);
            }
        }
        list.listEnd();
    };

    this.displayMap = function (tag) {
        var tags = [
            {name: 'comments', parent: 'root', tag: 'div'},
            {name: 'mapped', parent: 'root', tag: 'div'}
        ];
        var mapTags = ra.html.generateTags(tag, tags);
        var comment = document.createElement('p');
        comment.innerHTML = "Walks without a start or a walking area are plotted in the North Sea";
        mapTags.comments.appendChild(comment);
        var lmap = new ra.leafletmap(mapTags.mapped, this.mapOptions);
        lmap.display();
        var map = lmap.map();
        var mycluster = new ra.map.cluster(map);
        var walks = this.programme.getWalks();
        for (var walk of walks) {
            if (walk.displayWalk) {
                if (walk.displayWalk) {
                    walk.getAsMarker(mycluster);
                }
            }
        }
        mycluster.addClusterMarkers();
        mycluster.zoomAll();
    };

    this.displayCalendar = function (tag) {
        var tags = [
            {name: 'comments', parent: 'root', tag: 'div'},
            {name: 'dates', parent: 'root', tag: 'div'},
            {name: 'calendar', parent: 'dates', tag: 'div', attrs: {class: 'ra-tab'}}
        ];
        var mapTags = ra.html.generateTags(tag, tags);
        var comment = document.createElement('p');
        comment.innerHTML = "Walks without a date are displayed 'Today'";
        mapTags.comments.appendChild(comment);
        var comment2 = document.createElement('p');
        comment2.innerHTML = "Click on walk to edit details";
        mapTags.comments.appendChild(comment2);
        var comment3 = document.createElement('p');
        comment3.innerHTML = "Click on date to add walk for that date";
        mapTags.comments.appendChild(comment3);
        var events = this.getEvents();
        var _this = this;
        var calendar = new FullCalendar.Calendar(mapTags.calendar, {
            height: 'auto',
            selectable: true,
            displayEventTime: false,
            eventTextColor: '#000000',
            headerToolbar: {center: 'dayGridMonth,listMonth'}, // buttons for switching between views
            events: events,
            views: {
                dayGrid: {
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        meridiem: false
                    }
                },
                timeGrid: {
                    // options apply to timeGridWeek and timeGridDay views
                },
                week: {
                    // options apply to dayGridWeek and timeGridWeek views
                },
                day: {
                    // options apply to dayGridDay and timeGridDay views
                }
            },
            eventClick: function (info) {
                var walks = _this.programme.getWalks();
                var i, clen;
                for (i = 0, clen = walks.length; i < clen; ++i) {
                    var walk = walks[i];
                    if (walk._eventid === info.event.id) {
                        walk.previewWalk();
                    }
                }
            },
            select: function (info) {
                if (_this._loggedOn !== null) {
                    var walkdate = info.startStr;
                    var today = new Date();
                    var now = ra.date.YYYYMMDD(today);
                    if (walkdate > now) {
                        //     var url = _this._newUrl + option + "date=" + walkdate.replaceAll("-", "%20");
                        //    window.location.replace(url);
                        let event = new Event("preview-walk-newdate");
                        event.ra = {};
                        event.ra.date = walkdate;
                        document.dispatchEvent(event);
                    } else {
                        ra.showMsg('Walk MUST be in the future');
                    }
                } else {
                    ra.showMsg('You must log on to be able to add walks');
                }
            }
        });
        calendar.render();
    };

    this.getEvents = function () {
        var events = [];
        var walks = this.programme.getWalks();
        var i, clen;
        for (i = 0, clen = walks.length; i < clen; ++i) {
            var walk = walks[i];
            if (walk.displayWalk) {
                var event = walk.getAsEvent();
                walk._eventid = i.toString();
                event.id = walk._eventid;
                events.push(event);
            }
        }
        return events;
    };



    this.displayWalkRow = function (table, walk, $class) {
        table.tableRowStart();
        for (var col of this.tableColumns) {
            var value = this.tableValue(walk, col.title);
            var sortValue = this.tableSortValue(walk, col.title);
            var td = table.tableRowItem(value, col, sortValue);
            if (col.title === "Status") {
                walk.addDisplayClasses(td.classList);
            }
            td.classList.add('pointer');
            td.addEventListener('click', function () {
                walk.previewWalk();
            });
        }
        table.tableRowEnd();
    };

    this.tableValue = function (walk, name) {
        switch (name) {
            case "Options":
                return this.getOptions(walk);
            case "State":
                return walk.getStatus();
            case "Category":
                return walk.getCategory();
            case "Date":
                return walk.getWalkDate('table');
            case "Meeting":
                return walk.getWalkMeeting('table');
            case "Start":
                return walk.getWalkStart('table');
            case "Title":
                return walk.getWalkTitle();
            case "Difficulty":
                return walk.getWalkDifficulty('table');
            case "Issues":
                return walk.getNoWalkIssues();
            case "Messages":
                return walk.getWalkMessages('summary');
            case "Contact":
                return  walk.getWalkContact('table');
            case "Notes":
                return  walk.getWalkNotes('table');
            case "Status":
                return  walk.getStatusCategory('<br/>', this.settings.singleCategory);
        }
        return 'unknown';
    };
    this.tableSortValue = function (walk, name) {
        switch (name) {
            case "Date":
                return walk.getWalkSortDate();
            default:
                return null;
        }
        return null;
    };
    this.getOptions = function (walk) {
        return this.getOptionEdit(walk) + this.getOptionDelete(walk);
    };
    this.getOptionEdit = function (walk) {
        return '<a href="javascript:ra.walkseditor.comp.editWalk(\'' + walk.id + '\')" class="btn btn-mini" type="button">' +
                '<i class="icon-edit" title="Edit walk"></i></a>';
    };
    this.getOptionDelete = function (walk) {
        return '<a href="javascript:ra.walkseditor.comp.deleteWalk(\'' + walk.id + '\')" class="btn btn-mini delete-button" type="button">' +
                ' <i class="icon-trash" title="Delete walk"></i></a>';
    };
};