/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var document;
var L, ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}

ra.walkseditor.editplace = function (options, data) {

    this.data = data;
    this.options = options;

    this.load = function () {

        var data = this.data;
        data.editMode = true;
        data.record = {};
        var fields = data.fields;
        var property;
        for (property in fields) {
            if (fields.hasOwnProperty(property)) {
                var buttonID = fields[property];
                data.record[property] = document.getElementById(buttonID).value;
            }
        }
        if (data.record.latitude === "") {
            data.record.latitude = 0;
        } else {
            data.record.latitude = parseFloat(data.record.latitude);
        }
        if (data.record.longitude === "") {
            data.record.longitude = 0;
        } else {
            data.record.longitude = parseFloat(data.record.longitude);
        }

        if (data.record.latitude === 0 && data.record.longitude === 0) {
            data.record.isLatLongSet = false;
        } else {
            data.record.isLatLongSet = true;
        }

        var tag = document.getElementById(this.options.divId);
        this.controller = new placecontroller(tag, data);
        this.controller.placeEditor();
        this.controller.setSubmitButton();

    };


};

ra.walkseditor.editwalk = function (options, data) {

    this.data = data;

    this.load = function () {
        var data = this.data;
        data.walk = new ra.walkseditor.walk();
        var date = null;
        if (this.data.walkdate !== null) {
            date = this.data.walkdate.replaceAll(' ', '-');
        }
        var content = document.getElementById(this.data.fields.content);

        this.setupWalksRecord(content, date);

        var tag = document.getElementById(options.divId);
        var topOptions = document.createElement('div');
        topOptions.setAttribute('class', 'ra-edit-options');

        tag.appendChild(topOptions);
        var topDiv = document.createElement('div');
        tag.appendChild(topDiv);
        var _this = this;
        window.onscroll = function () {
            if (!_this.elementInViewport(topDiv)) {
                topOptions.style.position = 'fixed';
            } else {
                topOptions.style.position = 'relative';
            }

        };


        var soptions = this.getElementOptions(this.data.fields.status);
        var coptions = this.getElementOptions(this.data.fields.category);
        //   this.statusSelect = this.setElementOptions(topOptions, 'Status', soptions, ra.walkseditor.help.editButtons);
        //   this.categorySelect = this.setElementOptions(topOptions, 'Category', coptions);

        var resultStatus = this.setElementOptions('Status', soptions, ra.walkseditor.help.editButtons);
        this.statusSelect = resultStatus.selecttab;

        var resultCategory = this.setElementOptions('Category', coptions);
        this.categorySelect = resultCategory.selecttab;
        if (coptions.items.length < 2) {
            this.categorySelect.style.display = 'none';
        }
        this.addButtons(topOptions);

        var draftwalk = this.data.walk;
        var errors = draftwalk.getNoWalkIssues();
        this.resetStatusButton(errors);
        this.statusSelect.addEventListener("mouseover", function (e) {
            var draftwalk = _this.data.walk;
            var errors = draftwalk.getNoWalkIssues();
            _this.resetStatusButton(errors);
        });
        this.statusSelect.addEventListener("change", function (e) {
            var draftwalk = _this.data.walk;
            var reason = '';
            var ele = e.target;
            var option = ele.options[ele.selectedIndex].text;
            if (option === "Cancelled") {
                reason = ra.showPrompt("Please say why the walk is being cancelled");
            }
            draftwalk.setStatus(option, reason);
        });
        this.categorySelect.addEventListener("change", function (e) {
            var draftwalk = _this.data.walk;
            var ele = e.target;
            var option = ele.options[ele.selectedIndex].text;
            draftwalk.setCategory(option);
        });

        var editorDiv = document.createElement('div');
        tag.appendChild(editorDiv);
        this.editor = new ra.walkseditor.walkeditor();
        this.editor.setStatusSelect(resultStatus.container);
        this.editor.setCategorySelect(resultCategory.container);
        this.editor.load(editorDiv, this.data.walk.data, false);
        this.editor.sortData();
        // editor.addEditForm();

    };
    this.elementInViewport = function (el) {
        var top = el.offsetTop;
        var left = el.offsetLeft;
        var width = el.offsetWidth;
        var height = el.offsetHeight;

        while (el.offsetParent) {
            el = el.offsetParent;
            top += el.offsetTop;
            left += el.offsetLeft;
        }

        return (
                top >= window.pageYOffset &&
                left >= window.pageXOffset &&
                (top + height) <= (window.pageYOffset + window.innerHeight) &&
                (left + width) <= (window.pageXOffset + window.innerWidth)
                );
    };
    this.getElementOptions = function (id) {
        var element = document.getElementById(id);
        var children = element.children;
        var options = {};
        options.items = [];
        options.id = id;
        var i;
        for (i = 0; i < children.length; i++) {
            var text = children[i].textContent;
            var value = children[i].value;
            var selected = children[i].getAttribute('selected');
            var item = {text: text, value: value, selected: selected};
            options.items.push(item);
        }
        return options;
    };

    this.setElementOptions = function (name, options, helpPage = null) {
        var container = document.createElement('div');
        //  tag.appendChild(container);
        var label = document.createElement('label');
        label.setAttribute('class', 'we-label gwem');
        label.textContent = name;
        container.appendChild(label);

        var select = document.createElement('select');
        select.setAttribute('class', 'gwem');
        container.appendChild(select);

        var i;
        for (i = 0; i < options.items.length; i++) {
            var option = options.items[i];
            var optionTag = document.createElement('option');

            select.appendChild(optionTag);
            optionTag.textContent = option.text;
            optionTag.value = option.value;
            if (option.selected !== null) {
                optionTag.setAttribute('selected', true);
            }
        }
        if (helpPage !== null) {
            var help = new ra.help(container, helpPage);
            help.add();
        }
        var result = {container: container,
            selecttab: select};
        return result;
    };
    this.addButtons = function (tag) {
        var _this = this;
        var submitButton = document.createElement('button');
        submitButton.setAttribute('type', 'button');
        submitButton.setAttribute('class', 'ra-button');
        submitButton.textContent = "Save";
        submitButton.addEventListener("click", function (e) {
            _this.updateDateFields();
            var element = _this.statusSelect;
            var disabled = element.selectedOptions[0].disabled;
            if (disabled) {
                ra.showError('Cannot save: Walk has errors and must be Draft status');
            } else {
                var status = document.getElementById(_this.data.fields.status);
                status.value = _this.statusSelect.value;
                var category = document.getElementById(_this.data.fields.category);
                category.value = _this.categorySelect.value;
                var submit = document.getElementById(_this.data.fields.submit);
                submit.click();
            }

        });
        submitButton.addEventListener("mouseover", function () {
            _this.updateDateFields();
        });
        tag.appendChild(submitButton);
        var cancelButton = document.createElement('button');
        cancelButton.setAttribute('type', 'button');
        cancelButton.setAttribute('class', 'ra-button');
        cancelButton.textContent = "Cancel";
        cancelButton.addEventListener("click", function (e) {
            var cancelURL = _this.data.fields.cancel;
            window.location.replace(cancelURL);
        });
        tag.appendChild(cancelButton);

        var previewButton = document.createElement('button');
        previewButton.innerHTML = 'Preview';
        previewButton.setAttribute('class', 'ra-button');
        previewButton.addEventListener("click", function () {
            _this.data.walk.previewWalk();
        });
        tag.appendChild(previewButton);
    };
    this.updateDateFields = function () {
        var draftwalk = this.data.walk;
        var walk = draftwalk.data;
        walk.admin.updated = new Date();
        var errors = draftwalk.getNoWalkIssues();
        this.resetStatusButton(errors);

        this.editor.sortData();
        var content = document.getElementById(this.data.fields.content);
        content.value = JSON.stringify(walk);
        var dateField = document.getElementById(this.data.fields.date);
        dateField.value = walk.basics.date;
        dateField.defaultValue = walk.basics.date;
        dateField.setAttribute("data-local-value", walk.basics.date);
        dateField.setAttribute("data-alt-value", walk.basics.date);

    };

    this.resetStatusButton = function (errors) {
        var element = this.statusSelect;
        var children = element.children;
        var i;
        for (i = 0; i < children.length; i++) {
            var child = children[i];
            if (child.value !== 'Draft') {
                if (errors > 0) {
                    child.disabled = true;
                } else {
                    child.disabled = false;
                }
            }
        }
    };
    this.setupWalksRecord = function (content, date) {

        if (content !== null) {
            var json = content.value;
            if (json === "") {
                json = "{}";

            }
        }
        var walk = this.data.walk;
        walk.createFromJson(json);
        if (date !== null) {
            walk.data.basics.date = date;
        }

    };

};