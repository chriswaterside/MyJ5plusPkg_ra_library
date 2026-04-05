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

// walks editor that COMPONENT
ra.walkseditor.comp.viewAllwalks = function (mapOptions, data) {
    this.data = data;
    this.newUrl = this.data.newUrl;
    this.programme = new ra.walkseditor.walks();
    ra.walkseditor.comp.walks = this.programme;
    this.mapOptions = mapOptions;
    this.allowWMExport = false;
    this.settings = {
        currentDisplay: "Table",
        filter: {}
    };
    this.loggedOn = this.newUrl !== null;

    var i, clen, item;
    var items = this.data.items;
    var programDateErrors = "";
    for (i = 0, clen = items.length; i < clen; ++i) {
        item = items[i];
        //  try {
        var json = item.content;
        var status = item.status;
        var category = item.category_name;
        var walk = new ra.walkseditor.walk();
        walk.createFromJson(json);
        walk.id = item.id;
        walk.init(status, category, !this.loggedOn);
        //    var walkitem = new ra.walkseditor.programmeItem(walk);
        if (!walk.isSqlDateCorrect(item.date)) {
            programDateErrors += '<li>' + "Walk ID: " + item.id + "  " + walk.getWalkBasics("dateError") + "</li>";
        }
        walk.editUrl = item.editUrl;
        walk.deleteUrl = item.deleteUrl;
        walk.duplicateUrl = item.duplicateUrl;
        this.programme.addWalk(walk);

        if (i === 0) {
            ra.walkseditor.preview.editButton = item.editUrl !== null;
            ra.walkseditor.preview.deleteButton = item.deleteUrl !== null;
            ra.walkseditor.preview.duplicateButton = item.duplicateUrl !== null;
        }

        if (item.deleteUrl !== null) {
            this.allowWMExport = true;
        }
    }
    this.masterdiv = document.getElementById(this.mapOptions.divId);
    if (programDateErrors !== "") {
        ra.showError("<p>The data stored for some walk(s) is incorrect due to a software error.</p>" +
                "<p>You may be able to correct this by editing and then saving the walk, you do not need to make any changes.</p><ul>" +
                programDateErrors + "</ul>", "Walks Editor Error");
    }

    this.load = function () {
        var tags = [
            {name: 'heading', parent: 'root', tag: 'h2'},
            {name: 'buttons', parent: 'root', tag: 'div', attrs: {class: 'alignRight'}},
            {name: 'viewer', parent: 'root', tag: 'div', attrs: {class: 'viewer'}},
            {name: 'legend', parent: 'root', tag: 'div'}
        ];
        var _this = this;
        this.elements = ra.html.generateTags(this.masterdiv, tags);
        var viewer = new ra.walkseditor.viewWalks(this.elements.viewer, this.mapOptions, this.programme, this.loggedOn);
        viewer.load();
        this.legend(this.elements.legend);
        this.elements.heading.innerHTML = 'Display Draft Walks Programme';
        if (this.loggedOn) {
            var button = this.addButton(this.elements.buttons, 'Create New Walk');
            button.addEventListener('click', function (e) {
                let event = new Event("preview-walk-new");
                document.dispatchEvent(event);
            });

        } else {
            var login = document.createElement('div');
            login.innerHTML = 'Log in to add/edit walks';
            this.elements.buttons.appendChild(login);
        }

        document.addEventListener('preview-walk-edit', function (e) {
            var walk = e.ra.walk;
            document.location.href = walk.editUrl;
        });
        document.addEventListener('preview-walk-new', function (e) {
            var url = _this.newUrl;
            document.location.replace(url);
        });
        document.addEventListener('preview-walk-newdate', function (e) {
            var walkdate = e.ra.date;
            var option = "?";
            if (_this.newUrl.includes('?')) { // allow for SEO
                option = "&";
            }
            var url = _this.newUrl + option + "date=" + walkdate.replaceAll("-", "%20");
            document.location.replace(url);
        });

        document.addEventListener('preview-walk-duplicate', function (e) {
            var walk = e.ra.walk;
            document.location.href = walk.duplicateUrl;
        });

        document.addEventListener('preview-walk-delete', function (e) {
            var $okay = ra.showConfirm("This walk will be permanently deleted");
            if ($okay) {
                var walk = e.ra.walk;
                document.location.href = walk.deleteUrl;
            }
        });
    };

    this.addButton = function (div, name) {
        var button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.classList.add('ra-button');
        button.innerHTML = name;
        div.appendChild(button);
        return button;
    };

    this.legend = function (tag) {

        var tags = [
            {name: 'details', parent: 'root', tag: 'details', attrs: {open: true}},
            {name: 'summary', parent: 'details', tag: 'summary', textContent: 'Legend', attrs: {class: 'ra legendsummary'}},
            {name: 'draft', parent: 'details', tag: 'div', attrs: {class: 'ra legend'}},
            {parent: 'draft', tag: 'div', attrs: {class: 'legendbox statusDraft'}, textContent: 'Draft'},
            {name: 'waiting', parent: 'details', tag: 'div', attrs: {class: 'ra legend waiting'}},
            {parent: 'waiting', tag: 'div', attrs: {class: 'legendbox statusAwaiting_Approval'}, textContent: 'Awaiting Approval'},
            {name: 'published', parent: 'details', tag: 'div', attrs: {class: 'ra legend published'}},
            {parent: 'published', tag: 'div', attrs: {class: 'legendbox statusPublished'}, textContent: 'Published'},
            {name: 'publishedPast', parent: 'details', tag: 'div', attrs: {class: 'ra legend past'}},
            {parent: 'publishedPast', tag: 'div', attrs: {class: 'legendbox statusPast'}, textContent: 'Published/Past'}
        ];
        ra.html.generateTags(tag, tags);
    };
    ra.walkseditor.comp.editWalk = function (id) {
        var walk = ra.walkseditor.comp.walks.getWalkById(id);
        var ok = true;
        switch (walk.getStatus()) {
            case "Published":
            case "Cancelled":
                ok = ra.showConfirm(ra.walkseditor.help.editQuestion());
                break;
        }
        if (ok) {
            document.location.href = walk.editUrl;
        }

    };
    ra.walkseditor.comp.deleteWalk = function (id) {
        var $okay = ra.showConfirm(ra.walkseditor.help.deleteQuestion());
        if ($okay) {
            var walk = ra.walkseditor.comp.walks.getWalkById(id);
            document.location.href = walk.deleteUrl;
        }

    };
};