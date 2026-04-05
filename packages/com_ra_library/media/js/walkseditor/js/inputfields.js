var ra, document, Quill;

if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.walkseditor) === "undefined") {
    ra.walkseditor = {};
}
ra.walkseditor.inputFields = function () {
    this.predefinedUrls = {
        contacts: ra.baseDirectory() + "index.php?option=com_ra_walkseditor&task=contacts.controller&format=json&search=",
        grades: ra.baseDirectory() + "index.php?option=com_ra_walkseditor&task=grades.controller&format=json",
        places: ra.baseDirectory() + "index.php?option=com_ra_walkseditor&task=places.controller&format=json&search="};

    this.setPredefinedURLsforJ5 = function () {
        this.predefinedUrls.contacts = ra.baseDirectory() + "index.php?option=com_ra_walks_editor&view=contacts&format=json&search=";
        this.predefinedUrls.grades = ra.baseDirectory() + "index.php?option=com_ra_walks_editor&view=grades&format=json";
        this.predefinedUrls.places = ra.baseDirectory() + "index.php?option=com_ra_walks_editor&view=places&format=json&search=";
    };
    this.addText = function (tag, divClass, label, raobject, property, placeholder = '', helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.setAttribute('class', 'we-label gwem');
        _label.textContent = label;
        var inputTag = document.createElement('input');
        inputTag.setAttribute('class', ' gwem');
        inputTag.setAttribute('type', 'text');
        inputTag.setAttribute('placeholder', placeholder);
        inputTag.raobject = raobject;
        inputTag.raproperty = property;

        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("input", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        itemDiv.appendChild(_label);
        itemDiv.appendChild(inputTag);
        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        return inputTag;
    };
    this.addComment = function (tag, divClass, label, comment, helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        if (label !== '') {
            var _label = document.createElement('label');
            _label.setAttribute('class', 'we-label gwem');
            _label.textContent = label;
            itemDiv.appendChild(_label);
        }
        var inputTag = document.createElement('span');
        inputTag.setAttribute('class', ' gwem');
        inputTag.setAttribute('type', 'text');
        inputTag.textContent = comment;

        itemDiv.appendChild(inputTag);
        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        return inputTag;
    };
    this.addNumber = function (tag, divClass, label, raobject, property, helpFunction = null) {
        var inputTag = this.addText(tag, divClass, label, raobject, property, '', helpFunction);
        inputTag.setAttribute('type', 'number');
        inputTag.setAttribute('step', '.01');
        inputTag.addEventListener('keypress', function (e) {
            const char = String.fromCharCode(e.which);
            if (!/[0-9.]/.test(char)) {
                e.preventDefault();
            }
            // One dot check
            if (char === '.' && this.value.includes('.')) {
                e.preventDefault();
            }
        });
        return inputTag;
    };

    this.addEmail = function (tag, divClass, label, raobject, property, placeholder = '', helpFunction = null) {
        var inputTag = this.addText(tag, divClass, label, raobject, property, placeholder, helpFunction);
        inputTag.addEventListener("input", function (e) {
            e.target.value = e.target.value.toLowerCase();
        });
        return inputTag;
    };
    this.addHtmlArea = function (tag, divClass, label, rows, raobject, property, placeholder = '', helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.setAttribute('class', ' gwem');
        _label.textContent = label;
        itemDiv.appendChild(_label);
        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        var container = document.createElement('div');
        itemDiv.appendChild(container);
        container.setAttribute('class', 'gwem quill');
        var inputTag = document.createElement('div');
        container.appendChild(inputTag);
        inputTag.style.width = '95%';
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.innerHTML = raobject[property];
        }
        var quill = this.addQuill(inputTag);
        quill.on('text-change', function (delta, oldDelta, source) {
            raobject[property] = quill.getSemanticHTML();
        });
        quill.clipboard.addMatcher(Node.ELEMENT_NODE, function (node, delta) {
            var plaintext = node.innerText;
            var Delta = Quill.import('delta');
            return new Delta().insert(plaintext);
        });
        return inputTag;
    };
    this.addQuill = function (container) {
//        var icons = Quill.import("ui/icons");
//        icons["undo"] = `<svg viewbox="0 0 18 18">
//          <polygon class="ql-fill ql-stroke" points="6 10 4 12 2 10 6 10"></polygon>
//          <path class="ql-stroke" d="M8.09,13.91A4.6,4.6,0,0,0,9,14,5,5,0,1,0,4,9"></path>
//          </svg>`;
//        icons["redo"] = `<svg viewbox="0 0 18 18">
//          <polygon class="ql-fill ql-stroke" points="12 10 14 12 16 10 12 10"></polygon>
//          <path class="ql-stroke" d="M9.91,13.91A4.6,4.6,0,0,1,9,14a5,5,0,1,1,5-5"></path>
//          </svg>`;
        var toolbarOptions = [[{'header': [false, 1, 2, 3]}],
            ['bold', 'italic', 'underline', 'strike', 'link'],
            [{'list': 'ordered'}, {'list': 'bullet'}]
        ];


        var quill = new Quill(container, {
            theme: 'snow',
            modules: {
                toolbar: toolbarOptions
//                history: {
//                    delay: 2000,
//                    maxStack: 500,
//                    userOnly: true
//                }
            }

        });
        return quill;
    };
    this.addHtmlAreaSummary = function (tag, divClass, label, rows, raobject, property, placeholder = '', helpFunction = null) {
        var itemDiv = document.createElement('details');
        itemDiv.classList.add(divClass);
        itemDiv.classList.add("subsection");
        tag.appendChild(itemDiv);
        var _label = document.createElement('summary');
        _label.title = 'Click to open or close section';
        _label.setAttribute('class', 'we-label gwem');
        _label.textContent = label;
        if (helpFunction !== null) {
            new ra.help(_label, helpFunction).add();
        }
        itemDiv.appendChild(_label);
        //   itemDiv.appendChild(inputTag);

        var inputTag = this.addHtmlArea(itemDiv, divClass, '', rows, raobject, property, placeholder);
        return inputTag;
    };
    this.addTextArea = function (tag, divClass, label, rows, raobject, property, placeholder = '', helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.setAttribute('class', 'we-label gwem');
        _label.textContent = label;
        var inputTag = document.createElement('textarea');
        inputTag.setAttribute('class', 'gwem');
        inputTag.setAttribute('rows', rows);
        inputTag.setAttribute('placeholder', placeholder);
        inputTag.style.width = '95%';
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("change", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        itemDiv.appendChild(_label);
        itemDiv.appendChild(inputTag);
        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        return inputTag;
    };

    this.addHeader = function (tag, headTag, label, helpFunction = null) {
        var heading = document.createElement(headTag);
        heading.innerHTML = label;
        heading.title = 'Click to open or close section';
        tag.appendChild(heading);
        if (helpFunction !== null) {
            new ra.help(heading, helpFunction).add();
        }
        return heading;
    };
    this.addTag = function (tag, newTag, html) {
        var heading = document.createElement(newTag);
        heading.innerHTML = html;
        tag.appendChild(heading);
        return heading;
    };
    this.addTime = function (tag, divClass, label, raobject, property, helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.setAttribute('class', 'we-label gwem');
        _label.textContent = label;
        var inputTag = document.createElement('input');
        inputTag.setAttribute('type', 'time');
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("focusout", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        inputTag.setAttribute('class', " time gwem");
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        itemDiv.appendChild(_label);
        itemDiv.appendChild(inputTag);
        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        return inputTag;
    };
    this.addTimeClock = function (tag, divClass, label, raobject, property, helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.setAttribute('class', 'we-label gwem');
        _label.textContent = label;
        var inputTag = document.createElement('input');
        inputTag.setAttribute('data-clocklet', 'format: HH:mm');
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("focusout", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        inputTag.setAttribute('class', " time gwem");
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        itemDiv.appendChild(_label);
        itemDiv.appendChild(inputTag);
        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        return inputTag;
    };
    this.addDate = function (tag, divClass, label, raobject, property, helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.setAttribute('class', 'we-label gwem');
        _label.textContent = label;
        var inputTag = document.createElement('input');
        inputTag.setAttribute('type', "date");
        inputTag.setAttribute('class', 'gwem');
        var spanItem = document.createElement('span');
        spanItem.setAttribute('class', 'gwem dow');
        itemDiv.appendChild(_label);
        itemDiv.appendChild(spanItem); // to hold day of the week
        itemDiv.appendChild(inputTag);
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("change", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        inputTag.addEventListener("change", function (e) {
            var d = new Date(e.target.value);
            var weekday = new Array(7);
            weekday[0] = "Sunday";
            weekday[1] = "Monday";
            weekday[2] = "Tuesday";
            weekday[3] = "Wednesday";
            weekday[4] = "Thursday";
            weekday[5] = "Friday";
            weekday[6] = "Saturday";

            var n = weekday[d.getDay()];
            if (typeof n === 'undefined') {
                e.target.previousSibling.textContent = "";
            } else {
                e.target.previousSibling.textContent = "   " + n;
            }
        });
        if (helpFunction !== null) {
            var h = new ra.help(itemDiv, helpFunction);
            h.add();
        }
        return inputTag;
    };
    this.addMultiChoice = function (tag, divClass, label, options, raobject, property, helpFunction = null) {
        // add property if it does not exsist
        if (!raobject.hasOwnProperty(property)) {
            raobject[property] = {};
        }
        var targetObject = raobject[property];
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.setAttribute('class', 'we-label gwem');
        _label.textContent = label;
        itemDiv.appendChild(_label);
        var div = document.createElement("div");
        div.style.marginLeft = '200px';
        itemDiv.appendChild(div);
        for (var key in options) {
            var option = options[key];
            var checkbox = document.createElement("input");
            checkbox.setAttribute('type', "checkbox");
            checkbox.setAttribute('id', key);
            checkbox.name = key;
            checkbox.value = option;
            div.appendChild(checkbox);
            var lab = document.createElement("label");
            lab.setAttribute('for', key);
            lab.style.display = 'inline';
            lab.textContent = option;
            div.appendChild(lab);
            div.appendChild(document.createElement("br"));
            checkbox.ra = {};
            checkbox.ra.targetObject = targetObject;
            checkbox.ra.data = raobject;

            checkbox.ra.item = key;
            if (targetObject.hasOwnProperty(key)) {  // Initialise value
                checkbox.checked = targetObject[key];
            }
            checkbox.addEventListener("change", function (e) {
                var ra = e.target.ra;
                if (e.target.checked) {
                    ra.targetObject[ra.item] = {};
                    ra.targetObject[ra.item].value = e.target.checked;
                    ra.targetObject[ra.item].name = e.target.value;
                } else {
                    delete ra.targetObject[ra.item];
                }

            });
        }

        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        return;
    };

    this.addSelect = function (tag, divName, labelName, values, raobject, property, propertyDesc = null, helpFunction = null) {
        var div = document.createElement('div');
        div.setAttribute('class', divName);
        tag.appendChild(div);
        var label = document.createElement('label');
        label.setAttribute('class', 'we-label gwem');
        label.textContent = labelName;
        div.appendChild(label);
        var inputTag = document.createElement('select');
        inputTag.setAttribute('class', 'gwem');
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        inputTag.rapropertyDesc = propertyDesc;
        div.appendChild(inputTag);
        var no = 0;
        for (var key in values) {
            no += 1;
        }
        if (no > 1) {
            var option = document.createElement("option");
            option.value = "undefined";
            option.text = "Please Select...";
            inputTag.appendChild(option);
        }

        for (var key in values) {
            var value = values[key];
            var option = document.createElement("option");
            option.value = key;
            option.text = value;
            if (no === 1) {
                raobject[property] = key;
                if (propertyDesc !== null) {
                    raobject[propertyDesc] = value;
                }

            }

            inputTag.appendChild(option);
        }
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
            if (inputTag.value === "") {
                inputTag.selectedIndex = 0;
                raobject[property] = "undefined";
                if (propertyDesc !== null) {
                    raobject[propertyDesc] = "INVALID";
                }
            }
        }
        inputTag.addEventListener("change", function (e) {
            var t = e.target;
            e.target.raobject[e.target.raproperty] = t.value;
            if (propertyDesc !== null) {
                var i = t.selectedIndex;
                e.target.raobject[e.target.rapropertyDesc] = t[i].label;
            }
        });
        if (helpFunction !== null) {
            new ra.help(div, helpFunction).add();
        }
        return inputTag;
    };
    this.addLocalGradeSelect = function (tag, divName, labelName, raobject, property, helpFunction = null) {
        var div = document.createElement('div');
        div.setAttribute('class', divName);
        tag.appendChild(div);
        var label = document.createElement('label');
        label.setAttribute('class', 'we-label gwem');
        label.textContent = labelName;
        div.appendChild(label);
        var inputTag = document.createElement('select');
        inputTag.setAttribute('class', 'gwem');
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        div.appendChild(inputTag);
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("change", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        var url = this.predefinedUrls.grades;
        var option = document.createElement("option");
        option.value = "";
        option.text = "Please Select...";
        inputTag.appendChild(option);
        ra.ajax.getJSON(url, function (err, result) {
            let event = new Event("records", {});
            event.error = err;
            if (typeof (result) !== "undefined" && result !== null) {
                var values = result.data;
                for (var i = 0; i < values.length; i++) {
                    var value = values[i].localgrade;
                    var option = document.createElement("option");
                    option.value = value;
                    option.text = value;
                    inputTag.appendChild(option);
                }
            } else {
                event.data = [];
            }

        }
        );
        inputTag.addEventListener("change", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        if (helpFunction !== null) {
            new ra.help(div, helpFunction).add();
        }
        return inputTag;
    };


    this.addPredefinedLocationButton = function (tag, location, helpFunction = null) {
        var findButton = document.createElement('button');
        findButton.setAttribute('type', 'button');
        findButton.setAttribute('class', 'predefinedlist');
        findButton.textContent = "Load predefined Location";
        findButton.addEventListener("recordfound", function (e) {
            var item = e.raData.item;
            location.updateDetails(item);
            // should use event

//            location.name = item.name;
//            location.gridref10 = item.gridreference;
//            location.latitude = item.latitude;
//            location.longitude = item.longitude;
//            let event = new Event("predefinedLocation", {bubbles: true}); // 
//
//            event.raData = {};
//            event.raData.item = item;
//            tag.dispatchEvent(event);

        });
        tag.appendChild(findButton);
        if (helpFunction !== null) {
            new ra.help(tag, helpFunction).add();
        }
        var feed = new ra.feedhandler();
        findButton.feedhelper = feed;
        findButton.addEventListener("click", function (e) {
            var target = e.target;
            var title = 'Predefined Location Search';
            var context = 'Search for predefined location, enter part of the name and then use search button';
            var url = this.predefinedUrls.places;
            var record = function (item) {
                return item.name + "   (" + item.gridreference + ")";
            };
            target.feedhelper.getPredefinedSearchModal(e, title, context, record, url);
        });
    };
    this.addPredefinedContactButton = function (tag, dataObject, helpFunction = null) {
        var findButton = document.createElement('button');
        findButton.setAttribute('type', 'button');
        findButton.setAttribute('class', 'predefinedlist');
        findButton.textContent = "Search saved contacts";
        findButton.addEventListener("recordfound", function (e) { // (1)
            var item = e.raData.item;
            dataObject.displayName = item.displayname;
            dataObject.email = item.email;
            dataObject.telephone1 = item.telephone1;
            dataObject.telephone2 = item.telephone2;
            let event = new Event("predefinedContact", {bubbles: true}); // 
            event.raData = {};
            event.raData.item = item;
            tag.dispatchEvent(event);

        });
        tag.appendChild(findButton);
        if (helpFunction !== null) {
            new ra.help(tag, helpFunction).add();
        }
        var feed = new ra.feedhandler();
        var url = this.predefinedUrls.contacts;
        findButton.feedhelper = feed;
        findButton.addEventListener("click", function (e) {
            var target = e.target;
            var title = 'Contact Search';
            var context = 'Search for predefined contact details, enter part of name and then use the search button';

            var record = function (item) {
                return item.displayname + "   (" + item.contactname + ", " + item.email + ", " + item.telephone1 + ", " + item.telephone2 + ")";
            };
            target.feedhelper.getPredefinedSearchModal(e, title, context, record, url);
        });
    };

    this.itemsItemDivs = function (tag) {
        var itemsDiv = document.createElement('div');
        itemsDiv.setAttribute('class', 'js-items');
        tag.appendChild(itemsDiv);

        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', 'js-item');
        itemsDiv.appendChild(itemDiv);
        return itemDiv;
    };
    this.addDetailsTag = function (tag) {
        var details = document.createElement('details');
        details.classList.add("subsection");
        tag.appendChild(details);
        var summary = document.createElement('summary');
        details.appendChild(summary);
        return {details: details, summary: summary};
    };
    if (ra.joomlaMajorVersion() > 3) {
        this.setPredefinedURLsforJ5();
    }
};