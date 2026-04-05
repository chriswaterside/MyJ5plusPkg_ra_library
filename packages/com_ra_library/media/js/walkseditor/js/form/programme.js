/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var document;
var ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}
if (typeof (ra.walkseditor.form) === "undefined") {
    ra.walkseditor.form = {};
}

ra.walkseditor.form.programme = function (options, data) {
    this.programme = new ra.walkseditor.walks();
    this.groups = data.groups;
    this.mapoptions = options;
    this.localGrades = data.localGrades;

    this.load = function () {

        ra.walkseditor.preview.editButton = true;
        ra.walkseditor.preview.deleteButton = true;
        ra.walkseditor.preview.duplicateButton = true;
        var tag = document.getElementById(options.divId);
        this.addSections(tag);

    };
    this.addSections = function (tag) {
        var _this = this;
        var viewerDiv = document.createElement('div');
        tag.appendChild(viewerDiv);
        var viewer = new ra.walkseditor.walksprogrammeViewer();
        viewer.load(viewerDiv, this.mapoptions, this.programme);

        document.addEventListener("walk-create-new", function (e) {
            var walk = new ra.walkseditor.walk();
            _this.programme.addWalk(walk);
            _this.editWalk(walk);
            document.addEventListener('ra-modal-closing', function (e) {
                let ev = new Event("editor-done");
                document.dispatchEvent(ev);
            });
        });

        document.addEventListener('preview-walk-newdate', function (e) {
            var walkdate = e.ra.date;
            var walk = new ra.walkseditor.walk();
            walk.setWalkDate(walkdate);
            _this.programme.addWalk(walk);
            _this.editWalk(walk);
        });

        document.addEventListener("editor-done", function (e) {
            _this.programme.sort();
            viewer.refresh();
        });

        document.addEventListener('preview-walk-edit', function (e) {
            _this.editWalk(e.ra.walk);
            document.addEventListener('ra-modal-closing', function (e) {
                let ev = new Event("editor-done");
                document.dispatchEvent(ev);
            });
        });

        document.addEventListener('preview-walk-duplicate', function (e) {

            _this.programme.duplicateWalk(e.ra.walk);
            viewer.refresh();
        });

        document.addEventListener('preview-walk-delete', function (e) {
            var $okay = ra.showConfirm("This walk will be permanently deleted");
            if ($okay) {
                _this.programme.deleteWalk(e.ra.walk);
                viewer.refresh();
            }
        });

    };

    this.editWalk = function (walk) {
        var content = document.createElement('div');
        ra.modals.createModal(content, false);
        var editor = new ra.walkseditor.walkeditor();
        editor.setGroups(this.groups);
        editor.setLocalGrades(this.localGrades);
        editor.load(content, walk.data, true);
    };


};

ra.walkseditor.walksprogrammeViewer = function () {
    this.programme = null;
    this.viewer = null;
    this.tag = null;
    this.mapoptions = null;

    this.load = function (tag, mapoptions, programme) {
        this.tag = tag;
        this.mapoptions = mapoptions;
        this.programme = programme;
        this.refresh();
    };
    this.refresh = function () {
        this.tag.innerHTML = "";
        this.addButtons(this.tag);
        this.viewer = new ra.walkseditor.viewWalks(this.tag, this.mapoptions, this.programme);
        this.viewer.load();
    };

    this.addButtons = function (tag) {

        var _this = this;
        var topOptions = document.createElement('div');
        topOptions.setAttribute('class', 'ra-edit-options');
        tag.appendChild(topOptions);
        var helpSpan = document.createElement('div');
        helpSpan.style.cssFloat = "right";
        topOptions.appendChild(helpSpan);
        new ra.help(helpSpan, ra.walkseditor.help.programmeOptions).add();
        var button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.classList.add('ra-button');
        button.textContent = "Create new walk";
        button.title = "Create a new walk and add it to the walks programme";
        button.addEventListener('click', function () {
            let event = new Event("walk-create-new"); // 
            document.dispatchEvent(event);
        });
        topOptions.appendChild(button);

        var saveButton = document.createElement('button');
        saveButton.setAttribute('type', 'button');
        saveButton.setAttribute('class', 'ra-button');
        saveButton.textContent = "Save walks programme";
        saveButton.title = "Save walks to PC";
        saveButton.addEventListener("click", function (e) {
            var data = _this.programme.getWalksJson();
            try {
                var blob = new Blob([data], {type: "application/json"});
                // add date to file name??
                name = "walkprogramme.walks";
                saveAs(blob, name);
            } catch (e) {
                ra.showError('Your web browser does not support his option!');
            }
        });
        topOptions.appendChild(saveButton);

        var uploadButton = document.createElement('button');
        // uploadButton.setAttribute('type', 'button');
        uploadButton.setAttribute('class', 'ra-button');
        uploadButton.textContent = "Upload walks programme";
        uploadButton.title = "Upload/read previously saved walk programme";
        topOptions.appendChild(uploadButton);

        var upload = new ra.uploadFile();
        upload.addField(uploadButton, ".walks,.json");
        uploadButton.addEventListener("upload-file-read", function (e) {
            var walks = {};
            try {
                var walks = JSON.parse(e.ra.result);
            } catch (e) {
                ra.showError(e, 'Error reading file');
                return;
            }
            if (!Array.isArray(walks)) {
                ra.showError(e, 'Invalid Walks file');
            }
            walks.forEach(function (walk, index) {
                var newWalk = new ra.walkseditor.walk();
                newWalk.createFromObject(walk);
                _this.programme.addWalk(newWalk);
            });
            _this.refresh();
        });


        var wmexport = new ra.walkseditor.exportToWM();
        wmexport.button(topOptions, this.programme);

        var clear = document.createElement('div');
        clear.setAttribute('class', 'clear');
        topOptions.appendChild(clear);

    };
};