var document, ra, FullCalendar;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}
ra.walkseditor.DATETYPE = {
    NoDate: 1,
    Past: 2,
    Future: 3
};
ra.walkseditor.preview = {};
ra.walkseditor.preview.editButton = false;
ra.walkseditor.preview.deleteButton = false;
ra.walkseditor.preview.duplicateButton = false;
ra.walkseditor.walks = function () {
    this._walks = [];
    this.getWalks = function () {
        return this._walks;
    };
    this.getWalksJson = function () {
        var walksdata = [];
        this._walks.forEach(function (_walk, index) {
            walksdata.push(_walk.data);
        });
        return JSON.stringify(walksdata, null, 5);
    };
    this.getWalkById = function (value) {
        var id = parseInt(value);
        for (var i = 0; i < this._walks.length; i++) {
            var walk = this._walks[i];
            if (walk.id === id) {
                return walk;
            }
        }
        return null;
    };
    this.addWalk = function (walk) {
        this._walks.push(walk);
        this._sortWalks();
    };
    this.duplicateWalk = function (walk) {
        var newJson = JSON.stringify(walk.data);
        var newWalk = new ra.walkseditor.walk();
        newWalk.createFromJson(newJson);
        this.addWalk(newWalk);
    };
    this.deleteWalk = function (walk) {
        for (var i = 0; i < this._walks.length; i++) {
            if (this._walks[i] === walk) {
                this._walks.splice(i, 1);
            }
        }
    };
    this.clearWalks = function () {
        this._walks = [];
    };
    this.forEachAll = function (fcn) {
        this._walks.forEach(event => {
            fcn(event);
        });
    };
    this.forEachFiltered = function (fcn) {
        this._walks.forEach(event => {
            if (event.displayWalk) {
                fcn(event);
            }
        });
    };
    this.setAllWalks = function () {
        this._walks.forEach(event => {
            event.displayWalk = true;
        });
    };
    this.sort = function () {
        this._sortWalks();
    };
    this._sortWalks = function () {
        this._walks.sort(function (a, b) {
            var da = a.data.basics.date;
            var db = b.data.basics.date;
            if (!ra.date.isValidString(da)) {
                da = '';
            }
            if (!ra.date.isValidString(db)) {
                db = '';
            }
            if (da < db) {
                return -1;
            }
            if (da > db) {
                return 1;
            }
            return 0;
        });
    };
    this.setWalkDisplay = function () {
        this._walks.forEach(walk => {
            walk.setDisplayFilter(this.filter);
        });
    };
    this.setFilters = function (tag) {
        var filter = new ra.filter(document, "reDisplayWalks");
        this.filter = filter;
        var gradesOptions = {displaySingle: true,
            order: ['Not defined',
                'Event',
                'Easy Access',
                'Easy',
                'Leisurely',
                'Moderate',
                'Strenuous',
                'Technical']};
        var dowOptions = {displaySingle: true,
            order: ['Not defined',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday']};
        var whenOptions = {displaySingle: true,
            order: ['Not defined',
                'Past',
                'Future']};
        var distanceOptions = {displaySingle: true,
            order: ['Not defined',
                'Up to 3 miles (5 km)',
                '3+ to 5 miles (5-8 km)',
                '5+ to 8 miles (8-13 km)',
                '8+ to 10 miles (13-16 km)',
                '10+ to 13 miles (16-21 km)',
                '13+ to 15 miles (21-24 km)',
                '15+ miles (24 km)']};
        var updateOptions = {displaySingle: true,
            order: [{title: 'All walks', limit: 0},
                {title: 'In last 3 months', limit: 93},
                {title: 'In last month', limit: 31},
                {title: 'In last 2 weeks', limit: 14},
                {title: 'In last week', limit: 7}
            ]};
        var categoryOptions = {displaySingle: false};
        var statusOptions = {displaySingle: false};
        var issuesOptions = {displaySingle: true};
        var notesOptions = {displaySingle: false};
        var shapeOptions = {displaySingle: false};
        var contactsOptions = {displaySingle: true, sort: true};
        filter.addGroup(new ra.filter.groupText("idStatus", "Status", statusOptions));
        filter.addGroup(new ra.filter.groupText("idCategory", "Category", categoryOptions));
        filter.addGroup(new ra.filter.groupText("idIssues", "Issues", issuesOptions));
        filter.addGroup(new ra.filter.groupText("idNotes", "Notes", notesOptions));
        filter.addGroup(new ra.filter.groupText("idDOW", "Day of the week", dowOptions));
        filter.addGroup(new ra.filter.groupDate("idDate", "Dates"));
        filter.addGroup(new ra.filter.groupText("idWhen", "No date/Past/Future", whenOptions));
        filter.addGroup(new ra.filter.groupText("idDistance", "Distance", distanceOptions));
        filter.addGroup(new ra.filter.groupText("idGrade", "Grade", gradesOptions));
        filter.addGroup(new ra.filter.groupText("idShape", "Shape", shapeOptions));
        filter.addGroup(new ra.filter.groupText("idContacts", "Contact", contactsOptions));
        this._walks.forEach(walk => {
            walk.initialiseFilter(filter);
        });
        filter.display(tag);

    };
    this.setFilterDefaults = function () {
        this.filter.activateFilterItem("idWhen", "Future");
        this.filter.activateFilterItem("idWhen", "Not defined");
    };
};
ra.walkseditor.walk = function () {

    this.notifications = new ra.walkseditor.notifications();
    this.privacy = false;
    this.displayWalk = true;
    this.data = {
        admin: {version: '1.0',
            created: new Date(),
            updated: null,
            category: 'None',
            status: 'Draft',
            cancelledReason: ''},
        basics: {},
        meeting: {type: "undefined",
            locations: []
        },
        start: {
            type: "undefined"
        },
        walks: [{}],
        contact: {},
        notes: {
            comments: ''
        }

    };
    this.editUrl = null; // used by component
    this.deleteUrl = null;
    this.duplicateUrl = null;
    this.init = function (status, category, privacy) {
        this.setStatus(status);
        this.setCategory(category);
        this.privacy = privacy;
    };
    this.createWithDate = function (date) {
        this.date = date;
    };
//    this.setButtons = function (value) {
//        this.buttons = value;
//    };
    this.createFromJson = function (json) {
        try {
            var data = JSON.parse(json);
            this.createFromObject(data);
        } catch (err) {
            ra.showError('Error processing walk (json=' + json + ' )');
            var data = {};
            this.createFromObject(data);
        }
    };
    this.initialiseFilter = function (filter) {
        var values = this.getFilterValues();
        filter.initialiseFilter(values);
    };
    this.getFilterValues = function () {

        var valueSet = new ra.filter.valueSet();
        var walkDate = ra.getObjProperty(this.data, "basics.date", "");
        if (walkDate !== "") {
            valueSet.add("idDOW", ra.date.dow(walkDate));
            valueSet.add("idDate", ra.date.getDateTime(walkDate));
        } else {
            valueSet.add("idDOW", "Not defined");
        }

        if (this.getNoWalkIssues() > 0) {
            valueSet.add("idIssues", "Has issues");
        } else {
            valueSet.add("idIssues", "No issues");
        }

        valueSet.add("idStatus", ra.getObjProperty(this.data, "admin.status", ""));
        if (walkDate !== "") {
            var now = ra.date.YYYYMMDD(new Date());
            if (walkDate < now) {
                valueSet.add("idWhen", "Past", "ID12345Past");
            } else {
                valueSet.add("idWhen", "Future", "ID12345Future");
            }
        } else {
            valueSet.add("idWhen", "Not defined", "ID12345Ndd");
        }

        var updated = ra.getObjProperty(this.data, "admin.updated", "");
        if (updated !== null && updated !== "") {
            //     valueSet.add("idUpdated", updated);
        }

        var walks = ra.getObjProperty(this.data, 'walks', []);
        walks.forEach(item => {
            valueSet.add("idDistance", this.filterDistance(item));
            valueSet.add("idGrade", ra.getObjProperty(item, 'natgrade', 'Not defined'));
            valueSet.add("idShape", ra.getObjProperty(item, 'type', 'Not defined'));
        });
        valueSet.add("idContacts", ra.getObjProperty(this.data, "contact.displayName", "Not defined"));
        if (this.hasEditorNotes() > 0) {
            valueSet.add("idNotes", "Has notes");
        } else {
            valueSet.add("idNotes", "No notes");
        }
        return  valueSet;
    };
    this.setDisplayFilter = function (filter) {
        var values = this.getFilterValues();
        this.displayWalk = filter.shouldDisplayItem(values);
    };
    this.filterDistance = function (walk) {
        var distanceOrder = ['Not defined',
            'Up to 3 miles (5 km)',
            '3+ to 5 miles (5-8 km)',
            '5+ to 8 miles (8-13 km)',
            '8+ to 10 miles (13-16 km)',
            '10+ to 13 miles (16-21 km)',
            '13+ to 15 miles (21-24 km)',
            '15+ miles (24 km)'];
        var dist = ra.getObjProperty(walk, 'distance', -1);
        var units = ra.getObjProperty(walk, 'units', '');
        if (units === "Km" && dist !== -1) {
            dist = dist * 0.621371;
        }

        switch (true) {
            case (dist <= 0):
                return distanceOrder[0];
            case (dist <= 3):
                return distanceOrder[1];
            case (dist <= 5):
                return distanceOrder[2];
            case (dist <= 8):
                return distanceOrder[3];
            case (dist <= 10):
                return distanceOrder[4];
            case (dist <= 13):
                return distanceOrder[5];
            case (dist <= 15):
                return distanceOrder[6];
            default:
                return distanceOrder[7];
        }
    };
    this.createFromObject = function (data) {
        if (typeof data.admin !== 'undefined') {
            this.data.admin = data.admin;
        }
        if (typeof data.basics !== 'undefined') {
            this.data.basics = data.basics;
        }
        if (typeof data.meeting !== 'undefined') {
            this.data.meeting = data.meeting;
        }
        if (typeof data.start !== 'undefined') {
            this.data.start = data.start;
        }
        if (typeof data.walks !== 'undefined') {
            this.data.walks = data.walks;
        }
        if (typeof data.contact !== 'undefined') {
            this.data.contact = data.contact;
        }
        if (typeof data.facilities !== 'undefined') {
            this.data.facilities = data.facilities;
        }
        if (typeof data.transport !== 'undefined') {
            this.data.transport = data.transport;
        }
        if (typeof data.accessibility !== 'undefined') {
            this.data.accessibility = data.accessibility;
        }
        if (typeof data.notes !== 'undefined') {
            this.data.notes = data.notes;
        }
    };
    this.addDisplayClasses = function (cl) {
        var classes = this.getStatusClasses();
        classes.forEach(c => {
            cl.add(c);
        });
        return;
    };
    this.getEventClassesArray = function () {
        var out = [];
        out.push('pointer');
        var classes = this.getStatusClasses();
        classes.forEach(c => {
            out.push(c);
        });

        return out;
    };
    this.dateSet = function () {
        var basics = this.data.basics;
        if (basics.hasOwnProperty('date')) {
            return basics.date !== "";
        } else {
            return false;
        }
    };
    this.dow = function () {
        if (this.dateSet()) {
            var basics = this.data.basics;
            return  ra.date.dow(basics.date);
        } else {
            return null;
        }
    };
    this.isSqlDateCorrect = function (d) {
        var result;
        if (this.dateSet()) {
            var basics = this.data.basics;
            result = ra.date.YYYYMMDD(basics.date) === ra.date.YYYYMMDD(d);
        } else {
            result = null === d;
        }
        return result;
    };
    this.getContact = function () {
        return ra.getObjProperty(this.data, 'contact.displayName', 'Undefined');
    };
    this.exportToWMLine = function () {
        var distance = '';
        var units = '';
        var natgrade = '';
        var data = [];
        var walkdate = ra.getObjProperty(this.data, 'basics.date', null);
        if (walkdate === null) {
            data.push('');
        } else {
            data.push(ra.date.DDMMYYYY(walkdate));
        }
        data.push(ra.getObjProperty(this.data, 'basics.title', ''));
        data.push(ra.getObjProperty(this.data, 'basics.description', ''));
        data.push(ra.getObjProperty(this.data, 'basics.notes', ''));
        data.push(''); // website link
        data.push(ra.getObjProperty(this.data, 'contact.displayName', ''));
        var walks = ra.getObjProperty(this.data, 'walks', null);
        if (walks.length > 0) {
            if (walks.length > 1) {
                var reason = "WM does not support led walks with more than one walk/distance";
                this.walkNotSupported(reason);
            }
            var walk1 = walks[0];
            var linear = ra.getObjProperty(walk1, 'type', '') === 'linear';
            if (linear) {
                data.push('Linear');
            } else {
                data.push('Circular');
            }
            distance = ra.getObjProperty(walk1, 'distance', '');
            units = ra.getObjProperty(walk1, 'units', '');
            natgrade = ra.getObjProperty(walk1, 'natgrade', '');
        } else {
            data.push('null');
        }
        var area = ra.getObjProperty(this.data, 'start.type', null) === "area";
        if (area) {
            var reason = "WM does not support option to publish the general area of a walk";
            this.walkNotSupported(reason);
        }
        this._exportLocation(data, ra.getObjProperty(this.data, 'start.location', null));
        var meetloc = ra.getObjProperty(this.data, 'meeting.locations', []);
        if (meetloc.length > 0) {
            this._exportLocation(data, meetloc[0]);
        } else {
            this._exportLocation(data, null);
        }

        this._exportLocation(data, ra.getObjProperty(this.data, 'finish.location', null), this.estimatedFinishTime());
        data.push(natgrade);
        switch (units) {
            case "miles":
                data.push('');
                data.push(distance);
                break;
            case "km":
                data.push(distance);
                data.push('');
                break;
            default:
                data.push('');
                data.push('');
        }
        data.push(''); // ascent
        data.push('');
        data.push(this.outFlag(this.data, 'accessibility.dog.value'));
        data.push(this.outFlag(this.data, 'accessibility.intro.value', ''));
        data.push(this.outFlag(this.data, 'accessibility.nostiles.value', ''));
        data.push(this.outFlag(this.data, 'accessibility.family.value', ''));
        data.push(this.outFlag(this.data, 'accessibility.wheelchair.value', ''));
        data.push(this.outFlag(this.data, 'transport.access.value', ''));
        data.push(this.outFlag(this.data, 'transport.park.value', ''));
        data.push(this.outFlag(this.data, 'transport.share.value', ''));
        data.push(this.outFlag(this.data, 'transport.coach.value', ''));
        data.push(this.outFlag(this.data, 'facilities.refresh.value', ''));
        data.push(this.outFlag(this.data, 'facilities.toilet.value', ''));
        var out = "\n" + ra.arrayToCSV(data);
        return out;
    };
    this.outFlag = function (data, item) {
        var value = ra.getObjProperty(data, item, false);
        if (value) {
            return 'True';
        } else {
            return '';
        }

    };
    this._exportLocation = function (dataarray, data, estimatedTime = null) {
        if (data === null) {
            if (estimatedTime === null) {
                dataarray.push('');
            } else {
                dataarray.push(estimatedTime);
            }
            dataarray.push('');
            dataarray.push('');
            dataarray.push('');
            dataarray.push('');
        } else {
            var time = ra.getObjProperty(data, 'time', '');
            if (time === '') {
                if (estimatedTime !== null) {
                    time = estimatedTime;
                }
            }
            dataarray.push(time);
            dataarray.push('');
            dataarray.push('');
            dataarray.push(ra.getObjProperty(data, 'gridref10', ''));
            dataarray.push(ra.getObjProperty(data, 'name', ''));
    }

    };
    this.exportToGWEMLine = function () {
        var difficulty = '';
        var localgrade = '';
        var km = '';
        var miles = '';
        var data = [];
        var walkdate = ra.getObjProperty(this.data, 'basics.date', null);
        if (walkdate === null) {
            data.push('');
        } else {
            data.push(ra.date.DDMMYYYY(walkdate));
        }
        data.push(ra.getObjProperty(this.data, 'basics.title', ''));
        data.push(ra.getObjProperty(this.data, 'basics.description', ''));
        var walks = ra.getObjProperty(this.data, 'walks', null);
        if (walks.length > 0) {
            if (walks.length > 1) {
                var reason = "GWEM does not support led walks with more than one walk/distance";
                this.walkNotSupported(reason);
            }
            var walk1 = walks[0];
            difficulty = ra.getObjProperty(walk1, 'natgrade', '');
            localgrade = ra.getObjProperty(walk1, 'localgrade', '');
            var units = ra.getObjProperty(walk1, 'units', '');
            if (units === 'miles') {
                miles = ra.getObjProperty(walk1, 'distance', 0);
            } else {
                km = ra.getObjProperty(walk1, 'distance', 0);
            }
            var linear = ra.getObjProperty(walk1, 'type', '') === 'linear';
            if (linear) {
                data.push('Linear');
            } else {
                data.push('Circular');
            }
        } else {
            data.push('');
        }

        // start
        this._exportGWEMLocation(data, ra.getObjProperty(this.data, 'start.location', null));
        var exact = ra.getObjProperty(this.data, 'start.type', null) === "start";
        if (exact) {
            data.push('Yes');
        } else {
            data.push('No');
        }
        data.push(ra.getObjProperty(this.data, 'start.location.time', ''));
        // meeting
        var meetloc = ra.getObjProperty(this.data, 'meeting.locations', []);
        if (meetloc.length > 0) {
            this._exportGWEMLocation(data, meetloc[0]);
            data.push('Yes'); // exact
            data.push(ra.getObjProperty(meetloc[0], 'time', ''));
        } else {
            this._exportGWEMLocation(data, null);
            data.push('');
            data.push('');
        }

        // finish
        this._exportGWEMLocation(data, ra.getObjProperty(this.data, 'finish.location', null));
        data.push('Public');
        data.push(difficulty);
        data.push(localgrade);
        data.push(km);
        data.push(miles);
        var estfinish = '';
        data.push(estfinish);
        // contact
        data.push(ra.getObjProperty(this.data, 'contact.id', ''));
        var contactType = ra.getObjProperty(this.data, 'contact.contactType', '');
        if (contactType === 'isLeader') {
            data.push('Yes');
        } else {
            data.push('No');
        }
        data.push(ra.getObjProperty(this.data, 'contact.displayName', ''));
        data.push(''); // Festivals
        data.push(''); // Strands

        data.push(ra.getObjProperty(this.data, 'basics.notes', ''));
        var out = ra.arrayToCSV(data) + "\n\r";
        return out;
    };
    this._exportGWEMLocation = function (dataarray, data) {
        if (data === null) {
            dataarray.push('');
            dataarray.push('');
            dataarray.push('');
            dataarray.push('');
        } else {
            dataarray.push('');
            dataarray.push(ra.getObjProperty(data, 'postcode.value', ''));
            dataarray.push(ra.getObjProperty(data, 'gridref10', ''));
            dataarray.push(ra.getObjProperty(data, 'name', ''));
        }

    };
    this.walkNotSupported = function (reason) {
        var walkdate = ra.getObjProperty(this.data, 'basics.date', 'No date defined');
        var title = ra.getObjProperty(this.data, 'basics.title', 'No title defined');
        var msg = "Unable to export all features of this walk\n\r";
        msg += "Reason: " + reason + "\n\r";
        msg += "Walk date: " + walkdate + "\n\r";
        msg += "Walk Tilte: " + title;
        ra.showError(msg);
    };
    this.previewWalk = function () {
        var $html = "<div id='ramblers-details-buttons1' ></div>";
        $html += this.walkDetails();
        $html += "<div id='ramblers-details-buttons2' ></div>";
        $html += "<div id='ramblers-diagnostics' ></div>";
        var modal = ra.modals.createModal($html, false);
        this._addMaptoWalk();
        this.addButtons(document.getElementById('ramblers-details-buttons1'));
        this.addButtons(document.getElementById('ramblers-details-buttons2'));
        if (!this.privacy) {
            var tag = document.getElementById('ramblers-diagnostics');
            var details = document.createElement('details');
            tag.appendChild(details);
            var summary = document.createElement('summary');
            summary.textContent = "Diagnostics";
            details.appendChild(summary);
            var div = document.createElement('div');
            details.appendChild(div);
            div.innerHTML = "<pre>" + ra.html.escape(JSON.stringify(this.data, undefined, 4)) + "</pre>";
        }
        document.addEventListener('preview-close-modal', function (e) {
            modal.close();
        });
    };
    this.addButtons = function (tag) {

        //      this.addButton(div, 'View', item.viewUrl);
        if (ra.walkseditor.preview.editButton) {
            this.addButton(tag, 'Edit', ra.walkseditor.help.editQuestion());
        }
        if (ra.walkseditor.preview.duplicateButton) {
            this.addButton(tag, 'Duplicate');
        }
        if (ra.walkseditor.preview.deleteButton) {
            this.addButton(tag, 'Delete', ra.walkseditor.help.deleteQuestion());
        }

        return;
    };
    this.addButton = function (div, name, confirmMsg = '') {
        var button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.classList.add('ra-button');
        button.innerHTML = name;
        var _this = this;
        button.addEventListener('click', function () {
            var ok = true;
            if (confirmMsg !== '') {
                switch (_this.getStatus()) {
                    case "Published":
                    case "Cancelled":
                        ok = ra.showConfirm(confirmMsg);
                        break;
                }
            }
            if (ok) {
                let ev = new Event("preview-close-modal");
                document.dispatchEvent(ev);
                let event = new Event("preview-walk-" + name.toLowerCase());
                event.ra = {};
                event.ra.walk = _this;
                document.dispatchEvent(event);
            }
        });
        div.appendChild(button);
    };
    this.checkFields = function () {
        this.notifications.clear();
        this.checkFieldsBasics();
        this.checkFieldsMeeting();
        this.checkFieldsStart();
        this.checkFieldsFinish();
        this.checkFieldsWalks();
        this.checkFieldsContact();
    };
    this.checkFieldsBasics = function () {
        var walk = this.data;
        if (ra.getObjProperty(walk, 'basics') === null) {
            this.notifications.addItem("No basics section found");
        }
        if (ra.getObjProperty(walk, 'basics.date') === null) {
            this.notifications.addItem("Basics: No walk date found");
        }
        if (ra.getObjProperty(walk, 'basics.title') === null) {
            this.notifications.addItem("Basics: No walk title found");
        }
        if (ra.getObjProperty(walk, 'basics.description') === null) {
            this.notifications.addItem("Basics: No walk description found", false);
        }
        if (ra.getObjProperty(walk, 'basics.description') === '') {
            this.notifications.addItem("Basics: No walk description found", false);
        }
    };
    this.checkFieldsMeeting = function () {
        var walk = this.data;
        if (ra.getObjProperty(walk, 'meeting') === null) {
            this.notifications.addItem("No meeting found");
        }
        var meet = ra.getObjProperty(walk, 'meeting');
        var type = ra.getObjProperty(meet, 'type');
        if (type === null) {
            this.notifications.addItem("Meeting type not defined");
        }

        switch (type) {
            case 'undefined':
                this.notifications.addItem("Meeting type not defined");
                break;
            case 'car':
            case 'coach':
            case 'public':
                var meets = ra.getObjProperty(meet, 'locations');
                meets.forEach(element => {
                    if (ra.getObjProperty(element, 'time') === null) {
                        this.notifications.addItem("Meeting time not defined");
                    }
                    if (ra.getObjProperty(element, 'name') === null) {
                        this.notifications.addItem("Meeting location name not defined");
                    }

                    if (ra.getObjProperty(element, 'latitude') === null) {
                        this.notifications.addItem("Meeting location latitude/longitude not defined");
                    }
                });
                break;
            case 'none':
            default:
        }
    };
    this.checkFieldsStart = function () {
        var walk = this.data;
        var type = ra.getObjProperty(walk, 'start.type');
        switch (type) {
            case 'area':
                var meetingType = ra.getObjProperty(walk, 'meeting.type');
                if (meetingType === null || meetingType === 'undefined' || meetingType === 'none') {
                    this.notifications.addItem("Information: You have not supplied a meeting point nor a starting place", false);
                }
                if (ra.getObjProperty(walk, 'start.location.name') === null) {
                    this.notifications.addItem("Walk area name not defined");
                }
                if (ra.getObjProperty(walk, 'start.location.latitude') === null) {
                    this.notifications.addItem("Walk area latitude/longitude not defined");
                }
                break;
            case 'start':
                if (ra.getObjProperty(walk, 'start.location.time') === null) {
                    this.notifications.addItem("Start time not defined");
                }
                if (ra.getObjProperty(walk, 'start.location.name') === null) {
                    this.notifications.addItem("Start name not defined");
                }
                if (ra.getObjProperty(walk, 'start.location.latitude') === null) {
                    this.notifications.addItem("Start latitude/longitude not defined");
                }
                break;
            default:
                this.notifications.addItem("Start information not defined");
        }
    };

    this.checkFieldsFinish = function () {
        if (this.isWalkLinear()) {
            var walk = this.data;
            if (ra.getObjProperty(walk, 'finish.location.time') === null) {
                this.notifications.addItem("Finish time not defined");
            }
            if (ra.getObjProperty(walk, 'finish.location.name') === null) {
                this.notifications.addItem("Finish name not defined");
            }
            if (ra.getObjProperty(walk, 'finish.location.latitude') === null) {
                this.notifications.addItem("Finish latitude/longitude not defined");
            }
        }
    };
    this.checkFieldsWalks = function () {
        var walk = this.data;
        var walks = ra.getObjProperty(walk, 'walks');
        if (walks === null) {
            this.notifications.addItem("No walk defined");
        } else {
            walks.forEach(singlewalk => {
                var dist = ra.getObjProperty(singlewalk, 'distance');
                if (dist === null || dist === '') {
                    this.notifications.addItem("Walk - No distance specified");
                }
                if (ra.getObjProperty(singlewalk, 'units') === null) {
                    this.notifications.addItem("Walk - No distance units (miles/km) specified");
                }
                if (ra.getObjProperty(singlewalk, 'natgrade') === null) {
                    this.notifications.addItem("Walk - No national grade has been assigned");
                }
                if (ra.getObjProperty(singlewalk, 'type') === null) {
                    this.notifications.addItem("Walk - No walk shape assigned, circular,linear");
                }
                if (ra.getObjProperty(singlewalk, 'type') === "undefined") {
                    this.notifications.addItem("Walk - No walk shape assigned, circular,linear");
                }
            });
        }
    };
    this.checkFieldsContact = function () {
        var walk = this.data;
        if (ra.getObjProperty(walk, 'contact') === null) {
            this.notifications.addItem("Contact - Not defined");
        }
        if (ra.getObjProperty(walk, 'contact.displayName') === null) {
            this.notifications.addItem("Contact - No name defined");
        }
        var type = ra.getObjProperty(walk, 'contact.contactType');
        if (type === null || type === 'undefined') {
            this.notifications.addItem("Contact - No type, Leader/Not leader, defined");
        }
        var email = ra.getObjProperty(walk, 'email.title') !== null;
        var tel1 = ra.getObjProperty(walk, 'contact.telephone1') !== null;
        var tel2 = ra.getObjProperty(walk, 'contact.telephon2') !== null;
        if (email || tel1 || tel2) {
            // at least one contact method defined
        } else {
            this.notifications.addItem("Information:  Contact - No contact method defined (email or telephone)", false);
        }
    };
    this.setCategory = function (category, reason = '') {
        this.data.admin.category = category;
    };
    this.getCategory = function () {
        return this.data.admin.category;
    };
    this.setStatus = function (status, reason = '') {
        this.data.admin.status = status;
        if (status === "Cancelled") {
            this.data.admin.cancelledReason = reason;
        } else {
            this.data.admin.cancelledReason = "";
    }
    };
    this.getGroupCode = function () {
        var code = ra.getObjProperty(this.data, 'basics.group', "undefined");
        return code;
    };
    this.getGroupName = function () {
        var code = this.getGroupCode();
        if (code === "undefined") {
            return "INVALID";
        }
        return ra.getObjProperty(this.data, 'basics.groupName', "INVALID");
    };
    this.getStatus = function () {
        return this.data.admin.status;
    };

    this.getStatusClasses = function () {
        var status = this.getStatus();
        var past = this.dateStatus() === ra.walkseditor.DATETYPE.Past;
        switch (status) {
            case "Draft":
                return ['statusDraft'];
            case "Awaiting Approval":
                return ['statusAwaiting_Approval'];
            case 'Published':
                if (past) {
                    return ['statusPast'];
                } else {
                    return ['statusPublished'];
                }
            case 'Cancelled':
                if (past) {
                    return ['statusPast', 'statusCancelled'];
                } else {
                    return ['statusPublished', 'statusCancelled'];
                }
        }
    };
    this.dateStatus = function () {
        var d = ra.getObjProperty(this.data, 'basics.date', null);
        if (ra.date.isValidString(d)) {
            var value = ra.date.YYYYMMDD(d);
            var today = new Date();
            today = ra.date.YYYYMMDD(today);
            if (value < today) {
                return ra.walkseditor.DATETYPE.Past;
            } else {
                return ra.walkseditor.DATETYPE.Future;
            }
        }
        return ra.walkseditor.DATETYPE.NoDate;
    };
    this.setWalkDate = function (odate) {
        this.data.basics.date = odate;
    };
    this.getWalkDate = function (view) {
        var d = ra.getObjProperty(this.data, 'basics.date');
        var past = '';
        if (d !== null && d !== '') {
            if (ra.date.isValidString(d)) {
                var status = this.dateStatus();
                if (status === ra.walkseditor.DATETYPE.Past) {
                    past = " [PAST]";
                }
                switch (view) {
                    case 'table':
                        return  "<b>" + ra.date.dowdd(d) + "</b><br/>" + " " + ra.date.month(d) + " " + ra.date.YY(d);
                    case 'list':
                    case 'details':
                        return  "<b>" + ra.date.dowdd(d) + "</b>" + " " + ra.date.month(d) + " " + ra.date.YY(d) + past;
                    case 'dateError':
                        return   ra.date.dowdd(d) + " " + ra.date.month(d) + " " + ra.date.YY(d);
                }
            }
        }
        return 'Date not defined';
    };
    this.getWalkSortDate = function () {
        var d = ra.getObjProperty(this.data, 'basics.date');
        if (d !== null && d !== '') {
            if (ra.date.isValidString(d)) {
                return ra.date.getDateTime(d);
            }
        }
        return null;
    };
    this.getWalkBasics = function (view) {
        switch (view) {
            case 'details':
                var group = ra.getObjProperty(this.data, 'basics.groupName');
                var title = ra.getObjProperty(this.data, 'basics.title');
                var description = ra.getObjProperty(this.data, 'basics.description');
                var notes = ra.getObjProperty(this.data, 'basics.notes');
                var out = '';
                if (group !== null) {
                    out += '<h3>Group: ' + group + '</h3>';
                }
                out += '<h3>Title: ' + title + '</h3>';
                out += '<h3>Date: ' + this.getWalkDate('details') + '</h3>';
                if (description === null) {
                    description = '';
                }
                if (notes === null) {
                    notes = '';
                }
                out += '<h4>Description: </h4>' + description;
                out += '<h4>Notes: </h4>' + notes;
                return out;
                break;
            case 'dateError':
                out = "Date: " + this.getWalkDate('dateError');
                out += " Title: " + ra.getObjProperty(this.data, 'basics.title');
                return out;
                break;
            default:
                break;
        }


        return "";
    };
    this.getWalkMeeting = function (view) {
        var startTag = "", endTag = "";
        switch (view) {
            case 'table':
            case 'list':
                break;
            case 'details':
                startTag = "<p>";
                endTag = "</p>";
                break;
            default:
                break;
        }
        var type = ra.getObjProperty(this.data, 'meeting.type');
        if (type === null) {
            return  startTag + "Meeting type not defined ????" + endTag;
        }
        var out = type;
        switch (type) {
            case 'car':
                out = startTag + 'Car Share ' + endTag;
                break;
            case 'coach':
                out = startTag + 'Coach walk ' + endTag;
                break;
            case 'public':
                out = startTag + 'Using public transport ' + endTag;
                break;
            case 'none':
                out = startTag + 'Meet at start of walk' + endTag;
                break;
            default:
                return  startTag + "Meeting type not defined" + endTag;
        }


        var meets = ra.getObjProperty(this.data, 'meeting.locations');
        meets.forEach(element => {
            out += this.displayLocation(element, view, 'Meeting');
        });
        return out;
    };
    this.getWalkStart = function (view) {
        var startTag = "", endTag = "";
        switch (view) {
            case 'table':
            case 'list':
                break;
            case 'details':
                startTag = "<p>";
                endTag = "</p>";
                break;
            default:
                break;
        }
        var type = ra.getObjProperty(this.data, 'start.type');
        var out = "";
        var location = ra.getObjProperty(this.data, 'start.location');
        switch (type) {
            case 'start':
                out += this.displayLocation(location, view, 'start');
                break;
            case 'area':
                var name = ra.getObjProperty(location, 'name', '[?Place name?]');
                var gr = ra.getObjProperty(location, 'gridref8', '[?no grid ref?]');
                switch (view) {
                    case 'table':
                        out += 'General Area: ';
                        out += '</br/>' + name;
                        out += "</br/>Around Grid Ref " + gr;
                        break;
                    case 'list':
                        out += 'General Area: ';
                        out += name;
                        out += ", Around Grid Ref " + gr;
                        break;
                    case 'details':
                        out += '<br/>General Area: ';
                        out += '<br/>Name: ' + name;
                        out += "</br/>Around Grid Ref: " + gr;
                        break;
                }
                break;
            default:
                out = startTag + "Start type not defined" + endTag;
        }
        return out;
    };
    this.getWalkFinish = function (view) {
        var out = "";
        var location = ra.getObjProperty(this.data, 'finish.location');
        out += this.displayLocation(location, view, 'finish');
        return out;
    };
    this.displayLocation = function (location, view, type) {
        var out = '';
        var time = ra.getObjProperty(location, 'time', '[?Time?]');
        var name = ra.getObjProperty(location, 'name', '[?Place?]');
        var lat = ra.getObjProperty(location, 'latitude', 0);
        var long = ra.getObjProperty(location, 'longitude', 0);
        var gr = ra.getObjProperty(location, 'gridref8', null);
        var gr10 = ra.getObjProperty(location, 'gridref10', null);
        if (gr === "") {
            gr = 'Outside UK';
        } else {
            if (gr === null) {
                gr = '[?no grid ref?]';
            } else {
                gr = gr + " [" + gr10 + "]";
            }
        }

        var pc = ra.getObjProperty(location, 'postcode.value', '');
        var w3w = ra.getObjProperty(location, 'w3w', '');
        switch (view) {
            case 'table':
                out += time + ' at ' + name + ' (' + gr + ' ' + pc + ')';
                break;
            case 'list':
                out += ', ' + time + ' at ' + name + ' (' + gr + ' ' + pc + ')';
                break;
            case 'details':
                out += '<b>Time: </b>' + time;
                out += '<br/><b>Place: </b>' + name;
                out += '<ul>';
                out += '<li><b>Lat/Long: </b>' + lat.toFixed(5) + "/" + long.toFixed(5) + "</li>";
                out += '<li><b>Grid Ref: </b>' + gr + "</li>";
                out += '<li><b>Postcode: </b>' + pc + "</li>";
                out += '<li><b>W3W: </b>' + w3w + "</li>";
                out += "<li><b>Maps</b></li>" + this._displayOSMaps(location);
                out += '</ul>';
        }
        return out;
    };
    this._displayOSMaps = function (location) {
        var out = '';
        var maps = ra.getObjProperty(location, 'osmaps', null);
        if (maps === null) {
            return out;
        }
        out = "<ul>";
        maps.forEach(map => {
            out += '<li>' + map.type + " " + map.number + " - " + map.title + "</li>";
        });
        out += "</ul>";
        return out;
    };
    this.getWalkTitle = function (view = 'default') {
        return ra.getObjProperty(this.data, 'basics.title', '????');
    };
    this.getWalkDifficulty = function (view) {
        var out = '';
        if (view === 'details') {
            out += "<ul>";
        }
        var walks = ra.getObjProperty(this.data, 'walks');
        walks.forEach(element => {
            var distance = ra.getObjProperty(element, 'distance', '[?Distance?]');
            var units = ra.getObjProperty(element, 'units', '[?miles/km?]');
            var natgrade = ra.getObjProperty(element, 'natgrade', '[?National grade?]');
            switch (view) {
                case 'table':
                    out += distance + ' ' + units + ' ' + natgrade + '<br/>';
                    break;
                case 'list':
                    out += distance + ' ' + units + ', ';
                    break;
                case 'details':
                    var type = ra.getObjProperty(element, 'type', "");
                    var localgrade = ra.getObjProperty(element, 'localgrade', "");
                    var leader = ra.getObjProperty(element, 'leader', "");
                    var ascent = ra.getObjProperty(element, 'ascent', "");
                    var duration = ra.getObjProperty(element, 'duration', "");
                    out += '<li>' + type + " " + distance + ' ' + units + ' ' + natgrade;
                    if (localgrade !== "") {
                        out += " / " + localgrade;
                    }
                    if (ascent !== "") {
                        out += " Ascent: " + ascent;
                    }
                    if (duration !== "") {
                        out += " Duration: " + duration;
                    }
                    if (leader !== "") {
                        out += " Leader: " + leader;
                    }
                    out += '</li>';
                    break;
            }

        });
        if (view === 'details') {
            out += "</ul>";
        }
        return out;
    };
    this.getWalkContact = function (view) {
        var d = ra.getObjProperty(this.data, 'contact');
        var out = '';
        if (typeof (d.displayName) === "undefined") {
            out += '[?No contact name?]';
        } else {
            out += d.displayName;
        }

        switch (view) {
            case 'table':
                if (typeof (d.email) !== "undefined") {
                    out += '<br/>' + this.obscureInfo(d.email, this.privacy);
                }
                if (typeof (d.telephone1) !== "undefined") {
                    out += '<br/>' + this.obscureInfo(d.telephone1, this.privacy);
                }
                if (typeof (d.telephone2) !== "undefined") {
                    out += '<br/>' + this.obscureInfo(d.telephone2, this.privacy);
                }
                break;
            case 'list':
                if (typeof (d.telephone1) !== "undefined") {
                    out += ', ' + this.obscureInfo(d.telephone1, this.privacy);
                }
                break;
            case 'details':
                if (typeof (d.email) !== "undefined") {
                    out += '<br/>' + this.obscureInfo(d.email, this.privacy);
                }
                if (typeof (d.telephone1) !== "undefined") {
                    out += '<br/>' + this.obscureInfo(d.telephone1, this.privacy);
                }
                if (typeof (d.telephone2) !== "undefined") {
                    out += '<br/>' + this.obscureInfo(d.telephone2, this.privacy);
                }
                break;
        }
        return out;
    };
    this.getStatusCategory = function (delimiter, singleCategory) {
        var notes = '';
        notes += '[' + this.getStatus() + "]";
        if (!singleCategory) {
            notes += delimiter + "[" + this.getCategory() + "]";
        }
        if (this.hasEditorNotes()) {
            notes += delimiter + "[Editor Notes]";
        }
        var no = this.getNoWalkIssues();
        if (no > 0) {
            notes += delimiter + "[" + no.toString() + " issues]";
        }
        return notes;
    };
    this.getWalkFacilities = function () {
        var out = "";
        out += this.getWalkFacilitiesItem("Facilities", 'facilities');
        out += this.getWalkFacilitiesItem("Transport", 'transport');
        out += this.getWalkFacilitiesItem("Accessibility", 'accessibility');
        if (out === "") {
            out = "No facilities, transport, accessibility flags";
        }
        return out;
    };
    this.getWalkFacilitiesItem = function (title, section) {
        var out = "";
        var d = ra.getObjProperty(this.data, section);
        if (d !== null) {
            for (var key in d) {
                var item = d[key];
                if (item.value) {
                    out += "<li>" + item.name + "</li>";
                }
            }
        }
        if (out !== "") {
            out = "<b>" + title + "</b><br/><ul>" + out + "</ul>";
        }
        return out;
    };
    this.getNoWalkIssues = function () {
        this.checkFields();
        return this.notifications.getNumberErrors();
    };
    this.getWalkMessages = function (view = 'all') {
        this.checkFields();
        return this.notifications.getHtmlMsgs();
    };
    this.getWalkNotes = function (view) {
        var d = ra.getObjProperty(this.data, 'notes.comments');
        var out = '';
        switch (view) {
            case 'table':
                out += d;
                break;
            case 'list':
                out += d;
                break;
            case 'details':
                out += d;
                break;
        }
        return out;
    };
    this.getAsEvent = function () {
        var meetingTime = '';
        var d = ra.getObjProperty(this.data, 'basics.date');
        var startTime = ra.getObjProperty(this.data, 'start.location.time');
        var meeting = ra.getObjProperty(this.data, 'meeting.locations');
        if (meeting !== null) {
            meetingTime = ra.getObjProperty(meeting[0], 'time');
            if (meetingTime === "undefined") {
                meetingTime = null;
            }
        }
        var title = ra.getObjProperty(this.data, 'basics.title');
        if (title === null) {
            title = 'No title defined';
        }
        if (d === null) {
            var now = new Date();
            var iso = now.toISOString();
            d = iso.substring(0, iso.indexOf('T'));
        }

        var event = {};
        if (meetingTime !== null) {
            //      d = d + "T" + meetingTime + ":00";
        } else {
            if (startTime !== null) {
                //          d = d + "T" + startTime + ":00";
            }
        }
        event.start = d;
        event.title = title;
        event.classNames = this.getEventClassesArray();

        return event;
    };
    this.getAsMarker = function (cluster) {
        var type = ra.getObjProperty(this.data, 'start.type');
        var icon, popup, popupoffset, title;
        var lat;
        var lng;
        var date = ra.getObjProperty(this.data, 'basics.date', 'Date not set');
        var title = ra.getObjProperty(this.data, 'basics.title', 'Walk title not set');
        var time = ra.getObjProperty(this.data, 'start.location.time', 'Time not set');
        var name = ra.getObjProperty(this.data, 'start.location.name', 'Location name not set');
        var gr = ra.getObjProperty(this.data, 'start.location.gridref8', '');
        var _this = this;
        popup = "<div class='popupStatus " + this.getStatusClasses().join(' ') + "'>" + this.getStatus() + "</div>";
        popup += "<b>Date: " + date + "<br/>Title: " + title + "</b><br/>";
        switch (type) {
            case 'start':
                lat = ra.getObjProperty(this.data, 'start.location.latitude');
                lng = ra.getObjProperty(this.data, 'start.location.longitude');
                if (lat === null) {
                    lat = 53.70774;
                }
                if (lng === null) {
                    lng = 0.76326;
                }
                popup += "<b>Walk start</b><br/>Time:" + time;
                popup += "<br/>Name:" + name;
                popup += "<br/>GR: " + gr;
                icon = ra.map.icon.markerStart();
                title = 'Start of walk, ' + date;
                popupoffset = [0, -10];
                break;
            case 'area':
                lat = ra.getObjProperty(this.data, 'start.location.latitude');
                lng = ra.getObjProperty(this.data, 'start.location.longitude');
                if (lat === null) {
                    lat = 53.70774;
                }
                if (lng === null) {
                    lng = 0.76326;
                }
                popup += "<b>General area for walk only</b><br/>Name: " + name;
                popup += "<br/>Contact group if you wish to join the walk at the start";
                icon = ra.map.icon.markerArea();
                title = 'General area of walk, ' + date;
                popupoffset = [0, -10];
                break;
            default:
                lat = 53.70774;
                lng = 0.76326;
                icon = ra.map.icon.markerArea();
                title = 'Start not defined,' + date;
                popup += 'Start not defined';
                popupoffset = [0, -10];
        }
        //      var marker = L.marker([lat, lng], {icon: icon, title: title, riseOnHover: true}).addTo(layer);
        //     marker.bindPopup(popup, {offset: popupoffset, autoClose: false}).closePopup();
        var pp = document.createElement('div');
        pp.setAttribute('class', 'pointer');
        pp.setAttribute('title', 'View walk details');
        pp.innerHTML = popup;
        pp.addEventListener('click', function () {
            _this.previewWalk();
        });
        var marker = cluster.addMarker(pp, lat, lng, {icon: icon, title: title, riseOnHover: true});
    };
    this.hasEditorNotes = function () {
        var notes = ra.getObjProperty(this.data, 'notes.comments');
        if (notes !== null) {
            return notes.length > 0;
        }
        return false;
    };
    this.displayJson = function (tag, walk, gwemWalk) {
        var hr = document.createElement('hr');
        tag.appendChild(hr);
        var details = document.createElement('details');
        tag.appendChild(details);
        var summary = document.createElement('summary');
        summary.textContent = "Diagnostics";
        details.appendChild(summary);
        var div = document.createElement('div');
        details.appendChild(div);
        div.innerHTML = "<pre>" + JSON.stringify(walk, undefined, 4) + "</pre>";
        var div = document.createElement('div');
        details.appendChild(div);
        div.innerHTML = "<pre>" + JSON.stringify(gwemWalk, undefined, 4) + "</pre>";
    };
    this.walkDetails = function () {

        var PHP_EOL = "\n";
        var $html = "";
        $html += "<div class='walkstdfulldetails stdfulldetails walk draft' >" + PHP_EOL;
        $html += "<div class=\'group " + this.getStatusClasses().join(' ') + "'><b>Walk details/preview- " + this.getStatus() + " </b></div>" + PHP_EOL;
        $html += "<div class='ra preview section'>" + PHP_EOL;
        $html += '<b>Basics:</b><br/>' + this.getWalkBasics('details');
        $html += '<br>Estimated finish: ' + this.estimatedFinishTime();
        $html += "</div>" + PHP_EOL;
        $html += "<div class='ra preview section'>";
        $html += '<b>Meeting:</b>' + this.getWalkMeeting('details');
        $html += "</div>" + PHP_EOL;
        $html += "<div class='ra preview section'>";
        $html += '<b>Start:</b>' + this.getWalkStart('details');
        $html += "</div>" + PHP_EOL;
        if (this.isWalkLinear()) {
            $html += "<div class='ra preview section'>";
            $html += '<b>Finish:</b>' + this.getWalkFinish('details');
            $html += "</div>" + PHP_EOL;
        }
        $html += "<div class='ra preview section'>";
        $html += '<b>Walk(s):</b><br/>' + this.getWalkDifficulty('details');
        $html += "</div>" + PHP_EOL;
        $html += "<div class='ra preview section'>";
        $html += '<b>Contact:</b><br/>' + this.getWalkContact('details');
        $html += "</div>" + PHP_EOL;
        var mapdiv = "detailsMapDiv";
        $html += "<div class='ra preview section' id='" + mapdiv + "'></div>" + PHP_EOL;
        $html += "<div class='ra preview section'>" + PHP_EOL;
        $html += this.getWalkFacilities();
        $html += "</div>" + PHP_EOL;
        $html += "<div class='ra preview section'>" + PHP_EOL;
        $html += '<b>Issues:</b><br/>' + this.getWalkMessages();
        $html += "</div>" + PHP_EOL;
        var notes = this.getWalkNotes('details');
        if (notes !== '') {
            $html += "<div class='ra preview section'>" + PHP_EOL;
            $html += '<b>Editor Notes:</b><br/>' + notes;
            $html += "</div>" + PHP_EOL;
        }
        $html += "</div>" + PHP_EOL;
        return $html;
    };
    this.isWalkLinear = function () {
        var walks = ra.getObjProperty(this.data, 'walks', null);
        for (var walk of walks) {
            var shape = ra.getObjProperty(walk, 'type');
            if (shape === 'linear') {
                return true;
            }
        }
        return false;
    };
    this.estimatedFinishTime = function () {
        var time = ra.getObjProperty(this.data, 'finish.location.time', '');
        if (time !== '') {
            return time;
        }
        var startTime = ra.getObjProperty(this.data, 'start.location.time', '');
        if (startTime === '') {
            return '20:00';
        }
        var walks = ra.getObjProperty(this.data, 'walks', null);
        if (walks.length === 0) {
            return '20:00';
        }
        var walk = walks[0];
        var distance = ra.getObjProperty(walk, 'distance', null);
        var units = ra.getObjProperty(walk, 'units', null);
        if (distance === null || units === null) {
            return '20:00';
        }
        var duration = 0;
        switch (units) {
            case 'miles':
                duration = distance * 60 / 2;
                break;
            case 'km':
                duration = distance * 0.621371 * 60 / 2;
                break;
            default:
                return '20:00';
        }
        var t = new Time().setTime(startTime);
        t.addMinutes(duration);
        t.roundUpToNext30();
        return t.toString();
    };
    this._addMaptoWalk = function () {
        var tag = document.getElementById("detailsMapDiv");
        var lmap = new ra.leafletmap(tag, ra.defaultMapOptions);
        lmap.display();
        var map = lmap.map();
        var points = 0;
        var layer = L.featureGroup().addTo(map);
        var type = ra.getObjProperty(this.data, 'meeting.type');
        if (type !== null) {
            var out = '';
            switch (type) {
                case 'car':
                    out = 'Car Share at ';
                    break;
                case 'coach':
                    out = 'Coach walk pickup at ';
                    break;
                case 'public':
                    out = 'Using public transport at ';
                    break;
                case 'none':
                    break;
                default:
            }
            if (out !== '') {
                var meets = ra.getObjProperty(this.data, 'meeting.locations');
                meets.forEach(element => {
                    var time = ra.getObjProperty(element, 'time', '???');
                    var name = ra.getObjProperty(element, 'name', '???');
                    var gr = ra.getObjProperty(element, 'gridref8');
                    var lat = ra.getObjProperty(element, 'latitude');
                    var long = ra.getObjProperty(element, 'longitude');
                    var pc = ra.getObjProperty(element, 'postcode.value', null);
                    var pcLat = ra.getObjProperty(element, 'postcode.latitude');
                    var pcLng = ra.getObjProperty(element, 'postcode.longitude');
                    if (lat !== null && long !== null) {
                        out += time + ' from ' + name + ', ' + gr;
                        var marker = L.marker([lat, long]).addTo(layer);
                        marker.bindPopup(out);
                        points += 1;
                        if (pc !== null) {
                            ra.map.addPostcodeIcon(pc, [pcLat, pcLng], layer);
                        }
                    }
                });
            }
        }

        var type = ra.getObjProperty(this.data, 'start.type');
        var out = "";
        var icon;
        var lat = null;
        var long = null;
        switch (type) {
            case 'start':
                var time = ra.getObjProperty(this.data, 'start.location.time', '???');
                var name = ra.getObjProperty(this.data, 'start.location.name', '???');
                var gr = ra.getObjProperty(this.data, 'start.location.gridref8');
                lat = ra.getObjProperty(this.data, 'start.location.latitude');
                long = ra.getObjProperty(this.data, 'start.location.longitude');
                var pc = ra.getObjProperty(this.data, 'start.location.postcode.value', null);
                var pcLat = ra.getObjProperty(this.data, 'start.location.postcode.latitude');
                var pcLng = ra.getObjProperty(this.data, 'start.location.postcode.longitude');
                icon = ra.map.icon.markerStart();
                out = "Start of walk<br/>";
                out += time;
                out += " from " + name;
                out += ", " + gr;
                if (pc !== null) {
                    ra.map.addPostcodeIcon(pc, [pcLat, pcLng], layer);
                }
                break;
            case 'area':
                var name = ra.getObjProperty(this.data, 'start.location.name');
                var gr = ra.getObjProperty(this.data, 'start.location.gridref8');
                var lat = ra.getObjProperty(this.data, 'start.location.latitude');
                var long = ra.getObjProperty(this.data, 'start.location.longitude');
                icon = ra.map.icon.markerArea();
                out += 'General area: os walk<br/>';
                out += name;
                out += ", Grid Ref " + gr;
                break;
        }
        var options = {
            icon: icon
        };
        if (lat !== null && long !== null) {
            var marker = L.marker([lat, long], options).addTo(layer);
            marker.bindPopup(out);
            points += 1;
        }
        if (points > 0) {

            var bounds = layer.getBounds();
            map.fitBounds(bounds, {maxZoom: 15, padding: [20, 20]});
        }
    };
    this.gradeCSS = function (nationalGrade) {
        var $class = "";
        switch (nationalGrade) {
            case "Easy Access":
                $class = "grade-ea";
                break;
            case "Easy":
                $class = "grade-e";
                break;
            case "Leisurely":
                $class = "grade-l";
                break;
            case "Moderate":
                $class = "grade-m";
                break;
            case "Strenuous":
                $class = "grade-s";
                break;
            case "Technical":
                $class = "grade-t";
                break;
            default:
                break;
        }
        return $class;
    };
    this.obscureInfo = function ($text, privacy) {
        if (!privacy) {
            return $text;
        } else {
            var $no = $text.length;
            if ($no < 5) {
                return "*".repeat($no);
            }
            var $out = $text;
            for (var $i = 0; $i <= $no - 5; $i++) {
                var $char = $out.substr($i, 1);
                if ($char !== "@" && $char !== " ") {
                    $out = $out.replaceAt($i, "*");
                }
            }

            return $out;
        }
    };
    this.YYYYMMDD = function () {
        if (this.dateSet()) {
            var basics = this.data.basics;
            return  ra.date.YYYYMMDD(basics.date);
        } else {
            return null;
        }
    };
    String.prototype.replaceAt = function (index, char) {
        var a = this.split("");
        a[index] = char;
        return a.join("");
    };
}
;
ra.walkseditor.notifications = function () {
    this._notifications = [];
    this._noErrors = 0;
    this.clear = function () {
        this._notifications = [];
        this._noErrors = 0;
    };
    this.addItem = function (msg, error = true) {
        if (error) {
            this._noErrors += 1;
        }
        var item = new ra.walkseditor.notification(msg, error);
        this._notifications.push(item);
    };
    this.getNumberErrors = function () {
        return this._noErrors;
    };
    this.getHtmlMsgs = function () {
        var html = "";
        this._notifications.forEach(function (item, index) {
            html += item.getHtmlMsg();
        });
        return html;
    };
};
ra.walkseditor.notification = function (msg, isError) {
    this._msg = msg;
    this._isError = isError;
    this.getHtmlMsg = function () {
        var iclass = "ra-we-issues";
        if (this._isError) {
            iclass += " error";
        }
        return '<div class="' + iclass + '">' + this._msg + '</div>';
    };
};