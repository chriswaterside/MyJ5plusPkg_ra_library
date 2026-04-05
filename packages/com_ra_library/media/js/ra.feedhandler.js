var ra, OsGridRef;
if (typeof (ra) === "undefined") {
    ra = {};
}
ra.feedhandler = function () {
    this.modalSearchForm = function (eventTag) {
        var searchDiv = document.createElement('div');
        searchDiv.setAttribute('class', 'search');
        this.modal = ra.modals.createModal(searchDiv, false);
        var _this = this;
        this.getSearchTags(eventTag, searchDiv);
        eventTag.addEventListener("locationfound", function (e) {
            _this.modal.close();
        });
    };

    this.getSearchTags = function (eventTag, contentTag) {
        var formDiv = document.createElement("div");
        contentTag.appendChild(formDiv);

        var label = document.createElement('h4');
        label.setAttribute('class', 'caps');
        label.textContent = 'Location Search  ';
        formDiv.appendChild(label);

        var ukField = {checked: true};
        this.addYesNo(formDiv, 'divClass', "UK Only", ukField, 'checked');

        var inputField = document.createElement('input');
        inputField.setAttribute('type', 'text');
        inputField.setAttribute('class', 'map-select');
        inputField.setAttribute('placeholder', 'Enter place name, grid reference, postcode or W3W');
        inputField.textContent = 'Location Search  ';
        formDiv.appendChild(inputField);

        var searchBtn = this.searchButton(formDiv);

        var comment1 = document.createElement('p');
        comment1.setAttribute('class', 'smaller');
        comment1.innerHTML = 'You may enter an OS Grid Reference, of any length, a post code or a road or place name with the town or county. You may qualify a road or place name e.g. Bulls Head, Foolow or London Road, Derby.  ';
        comment1.innerHTML += " You may also specify a location using <a href='https://what3words.com/about-us/' target='_blank'>What3Words</a>, e.g for the summit of Snowden ///super.ultra.enhancement";
        formDiv.appendChild(comment1);

        inputField.addEventListener("keyup", function (event) {
            if (event.key === "Enter") {
                // click search button
                searchBtn.dispatchEvent(new Event("click"));
            }
        });

        var selectDiv = document.createElement('div');
        formDiv.appendChild(selectDiv);

        var selectTitle = document.createElement('p');
        selectDiv.appendChild(selectTitle);
        var selectTag = document.createElement('select');
        selectTag.style.display = "none";
        selectDiv.appendChild(selectTag);

        var acceptBtn = document.createElement('button');
        acceptBtn.setAttribute('type', 'button');
        acceptBtn.setAttribute('class', 'actionbutton');
        acceptBtn.textContent = 'Accept';
        acceptBtn.style.display = "none";
        formDiv.appendChild(acceptBtn);

        searchBtn.raData = {};
        searchBtn.raData.feedhelper = this;
        searchBtn.raData.inputField = inputField;
        searchBtn.raData.searchBtn = searchBtn;
        searchBtn.raData.selectDiv = selectDiv;
        searchBtn.raData.acceptBtn = acceptBtn;
        searchBtn.raData.selectTag = selectTag;

        searchBtn.raData.ukField = ukField;
        searchBtn.addEventListener("click", function (e) {
            var target = e.currentTarget;
            selectTitle.textContent = 'Searching ...';
            target.raData.feedhelper.getPossibleMapLocations(target.raData);
        });
        searchBtn.addEventListener("mapLocations", function (e) {
            var feed = e.target.raData.feedhelper;
            if (e.data.length === 1) {
                let event = new Event("locationfound", {bubbles: true}); // (2)
                event.raData = {};
                event.raData.item = e.data[0];
                eventTag.dispatchEvent(event);
            }
            feed.setUpSelectTagForMapSearch(selectTitle, selectTag, e.error, e.data, function (item) {
                item.class = ra.capitalizeFirstLetter(item.class);
                item.type = ra.capitalizeFirstLetter(item.type);
                item.display_name = item.display_name.replace(", United Kingdom", "");
                return "-  " + item.display_name;
            });
        });
        acceptBtn.addEventListener("click", function (e) {
            var items = selectTag.raData.items;
            var item = items[selectTag.value];
            let event = new Event("locationfound", {bubbles: true}); // (2)
            event.raData = {};
            event.raData.item = item;
            eventTag.dispatchEvent(event);
        });
        selectTag.addEventListener("change", function () {
            if (selectTag.value < 0) {
                acceptBtn.style.display = "none";
            } else {
                acceptBtn.style.display = "block";
            }
        });
        selectTag.addEventListener("dblclick", function () {
            if (selectTag.value < 0) {
                acceptBtn.style.display = "none";
            } else {
                acceptBtn.style.display = "block";
                acceptBtn.dispatchEvent(new Event("click"));
            }
        });

    };

    this.getPossibleMapLocations = function (raData) { // called via an event on input field
        var inputField = raData.inputField;
        var input = inputField.value;
        var ukonly = raData.ukField.checked;
        var searchBtn = raData.searchBtn;
        var found = false;
        try {
            var gr;
            var latlng;
            gr = OsGridRef.parse(input);
            gr.easting += 1; // rounding error
            gr.northing += 1;
            var gr8 = gr.toString(6);
            var gr10 = gr.toString(8);
            latlng = OsGridRef.osGridToLatLon(gr);
            var result = [];
            result[0] = {};
            result[0].lat = latlng.lat;
            result[0].lon = latlng.lon;
            result[0].display_name = gr8 + " / " + gr10;
            result[0].class = "Ordnance Survey";
            result[0].type = "Grid Reference";
            let event = new Event("mapLocations", {});
            event.error = null;
            event.data = result;
            searchBtn.dispatchEvent(event);
            found = true;
        } catch (err) {
        }
        if (!found) {
            //var input = "index.home.raft";
            var regex = /^\/{0,}[^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[・.。][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[・.。][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}$/i;
            if (regex.test(input)) {
                // print(text + " is the format of a three word address");
                inputField.addEventListener("what3wordsfound", function (e) {
                    var raData = e.raData;
                    let event = new Event("mapLocations", {});
                    var result = [];

                    event.error = raData.err;
                    if (raData.err === null) {
                        result[0] = {};
                        result[0].lat = raData.coordinates.lat;
                        result[0].lon = raData.coordinates.lng;
                        result[0].display_name = raData.nearestPlace + " - " + raData.words;
                        result[0].class = "What3words";
                        result[0].type = "Location";
                    }
                    if (raData.err === 400) {
                        event.error = null;
                    }
                    event.data = result;
                    searchBtn.dispatchEvent(event);
                });
                ra.w3w.toLocation(inputField, input);
                return;
            }
        }

        if (!found) {
            // try place look up
            var url = "https://nominatim.openstreetmap.org/search?q=" + input + "&format=json&limit=20";
            if (ukonly) {
                url = url + "&countrycodes=gb";
            }
            //   var url = "https://nominatim.openstreetmap.org/search?q=" + input + "&format=json&countrycodes=gb";
            ra.ajax.getJSON(url, function (err, result) {
                let event = new Event("mapLocations", {});
                event.error = err;
                event.data = result;
                searchBtn.dispatchEvent(event);
            }
            );
        }

    };
    this.setUpSelectTagForMapSearch = function (selectTitle, selectTag, error, items, itemFormatFunction) {
        // sort items into class order
        function compare(a, b) {
            // Use toUpperCase() to ignore character casing
            const classa = a.class.toUpperCase() + "/" + a.type.toUpperCase();
            const classb = b.class.toUpperCase() + "/" + b.type.toUpperCase();

            let comparison = 0;
            if (classa > classb) {
                comparison = 1;
            } else if (classa < classb) {
                comparison = -1;
            }
            return comparison;
        }

        items = items.sort(compare);
        var category = "";
        selectTag.innerHTML = '';
        selectTitle.textContent = '';
        if (error !== null) {
            selectTitle.innerHTML = "<p>Error: Sorry something went wrong: " + error + "</p>";
            return;
        } else {
            if (items.length === 0) {
                selectTitle.innerHTML = "<p class='error'>No items found</p>";
                selectTag.style.display = "none";
            }
            if (items.length > 0) {
                selectTag.style.display = "block";
                selectTitle.textContent = 'Select item from list, you may need to tap to see list, then use the Accept button that will appear.';
                if (items.length > 10) {
                    selectTag.setAttribute('size', 10);
                } else {
                    selectTag.setAttribute('size', items.length + 2);
                }
                selectTag.raData = {};
                selectTag.raData.items = items; // save items for accept button
                selectTag.setAttribute('class', 'map-select');
                var option = document.createElement("option");
                option.value = -1;
                option.text = "Please select an item from the list";
                option.setAttribute('selected', true);
                selectTag.appendChild(option);
                var i;
                for (i = 0; i < items.length; i++) {
                    var item = items[i];
                    var nextcategory = "[" + ra.capitalizeFirstLetter(item.class) + ": " + ra.capitalizeFirstLetter(item.type) + "]";
                    if (category !== nextcategory) {
                        category = nextcategory;
                        var cat = document.createElement("optgroup");
                        cat.setAttribute('label', category);
                        selectTag.appendChild(cat);
                    }

                    option = document.createElement("option");
                    option.value = i;
                    option.text = itemFormatFunction(item);
                    selectTag.appendChild(option);
                }
            }
        }


    };
    this.setUpSelectTag = function (selectTitle, selectTag, error, items, itemFormatFunction) {
        selectTag.innerHTML = '';
        selectTitle.textContent = '';
        if (error !== null) {
            selectTitle.innerHTML = "<p>Error: Sorry something went wrong: " + error + "</p>";
            return;
        } else {
            if (items.length === 0) {
                selectTitle.innerHTML = "<p class='error'>No items found</p>";
                selectTag.style.display = "none";
            }
            if (items.length > 0) {
                selectTag.style.display = "block";
                selectTitle.textContent = 'Select item from list, you may need to tap to see list, then use the Accept button that will appear.';
                if (items.length > 21) {
                    selectTag.setAttribute('size', 21);
                } else {
                    selectTag.setAttribute('size', items.length + 2);
                }
                selectTag.raData = {};
                selectTag.raData.items = items; // save items for accept button
                selectTag.setAttribute('class', 'map-select');
                var option = document.createElement("option");
                option.value = -1;
                option.text = "Please select an item from the list";
                option.setAttribute('selected', true);
                selectTag.appendChild(option);
                var i;
                for (i = 0; i < items.length; i++) {
                    var item = items[i];
                    option = document.createElement("option");
                    option.value = i;
                    option.text = itemFormatFunction(item);
                    selectTag.appendChild(option);
                }
            }
        }
    };

    this.getPredefinedSearchModal = function (e, title, context, record, url) {
        var findButton = e.target;
        var contentTag = document.createElement('div');
        var p = document.createElement('p');
        contentTag.appendChild(p);
        var myModal = ra.modals.createModal(contentTag);
        var label = document.createElement('label');
        label.textContent = title;
        contentTag.appendChild(label);
        var inputField = document.createElement('input');
        inputField.setAttribute('type', 'text');
        inputField.setAttribute('class', 'map-select');
        inputField.setAttribute('placeholder', 'Enter name');
        contentTag.appendChild(inputField);

        var searchBtn = this.searchButton(contentTag);

        var comment1 = document.createElement('p');
        comment1.textContent = context;
        contentTag.appendChild(comment1);

        var selectDiv = document.createElement('div');
        contentTag.appendChild(selectDiv);
        var selectTitle = document.createElement('p');
        selectDiv.appendChild(selectTitle);
        var selectTag = document.createElement('select');
        selectTag.style.display = "none";
        selectDiv.appendChild(selectTag);
        var acceptBtn = document.createElement('button');
        acceptBtn.setAttribute('type', 'button');
        acceptBtn.setAttribute('class', 'actionbutton');
        acceptBtn.textContent = 'Accept';
        contentTag.appendChild(acceptBtn);
        acceptBtn.style.display = "none";

        searchBtn.raData = {};
        searchBtn.raData.feedhelper = findButton.feedhelper;
        searchBtn.raData.inputField = inputField;
        searchBtn.raData.selectDiv = selectDiv;
        searchBtn.raData.acceptBtn = acceptBtn;
        searchBtn.raData.selectTag = selectTag;
        inputField.addEventListener("keyup", function (event) {
            if (event.key === "Enter") {
                // click search button
                searchBtn.dispatchEvent(new Event("click"));
            }
        });
        searchBtn.addEventListener("click", function (e) {
            var target = e.currentTarget;
            selectTitle.textContent = 'Searching ...';
            target.raData.feedhelper.getPossibleRecords(target, url);
        });
        searchBtn.addEventListener("records", function (e) {
            var feed = e.target.raData.feedhelper;
            feed.setUpSelectTag(selectTitle, selectTag, e.error, e.data, record);
        });
        acceptBtn.addEventListener("click", function (e) {
            var items = selectTag.raData.items;
            var item = items[selectTag.value];
            let event = new Event("recordfound", {bubbles: true}); // (2)
            event.raData = {};
            item.latitude = parseFloat(item.latitude); // in case it is char
            item.longitude = parseFloat(item.longitude);
            event.raData.item = item;
            findButton.dispatchEvent(event);
            var closeBtn = myModal.elements.close;
            closeBtn.dispatchEvent(new Event("click"));
        });
        selectTag.addEventListener("change", function () {
            if (selectTag.value < 0) {
                acceptBtn.style.display = "none";
            } else {
                acceptBtn.style.display = "block";
            }
        });
        selectTag.addEventListener("dblclick", function () {
            if (selectTag.value < 0) {
                acceptBtn.style.display = "none";
            } else {
                acceptBtn.style.display = "block";
                acceptBtn.dispatchEvent(new Event("click"));
            }
        });
    };
    this.getPossibleRecords = function (searchBtn, urlbase) {
        var inputField = searchBtn.raData.inputField;
        var url = urlbase + inputField.value;
        ra.ajax.getJSON(url, function (err, result) {
            let event = new Event("records", {});
            event.error = err;
            if (typeof (result) !== "undefined" && result !== null) {
                event.data = result.data;
            } else {
                event.data = [];
            }
            searchBtn.dispatchEvent(event);
        });
    };
    this.searchButton = function (tag) {
        var btn = document.createElement('button');
        btn.setAttribute('type', 'submit');
        btn.setAttribute('class', 'btn hasTooltip');
        tag.appendChild(btn);
        var icon = document.createElement('i');
        icon.setAttribute('data-original-title', 'Search');
        icon.setAttribute('class', 'icon-search');
        btn.appendChild(icon);
        return btn;
    };
    this.addYesNo = function (tag, divClass, label, raobject, property) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.textContent = label;
        _label.style.display = "inline";
        var inputTag = document.createElement('button');
        inputTag.setAttribute('class', 'small link-button white');
        inputTag.style.display = "inline";
        inputTag.style.marginLeft = "10px";
        if (raobject[property]) {
            inputTag.textContent = "Yes";
            inputTag.classList.add("button-p0555");
            inputTag.classList.remove("button-p0186");
        } else {
            inputTag.textContent = "No";
            inputTag.classList.add("button-p0186");
            inputTag.classList.remove("button-p0555");
        }
        inputTag.raobject = raobject;
        inputTag.property = property;

        inputTag.addEventListener("click", function (e) {
            inputTag.raobject[inputTag.property] = !inputTag.raobject[inputTag.property];

            if (inputTag.raobject[property]) {
                inputTag.textContent = "Yes";
                inputTag.classList.add("button-p0555");
                inputTag.classList.remove("button-p0186");
            } else {
                inputTag.textContent = "No";
                inputTag.classList.add("button-p0186");
                inputTag.classList.remove("button-p0555");
            }
            let event = new Event("yesnochange", {bubbles: true}); // (2)
            inputTag.dispatchEvent(event);
        });
        itemDiv.appendChild(_label);
        itemDiv.appendChild(inputTag);

        return inputTag;
    };

};