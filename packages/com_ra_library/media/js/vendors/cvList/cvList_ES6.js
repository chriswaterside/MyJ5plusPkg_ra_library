// this is out of date, non ES6 version is used
// as this version seemed to give the web optimisation by Cloud Next issues
class cvList {
    constructor(displayTag) {
        this._displayTag = displayTag;
        this._items = [];
        this.keys = [];
        this._fields = new cvListFields(this._displayTag);
        this._pagination = null;
    }
    addItem(element, nonPaginatedItem = null) {
        var item = {element: element,
            nonPaginatedItem: nonPaginatedItem};
        this._items.push(item);
    }
    appendPrintItems(tag) {
        var oddRow = true;
        var keys = this.keys;
        for (var key in keys) {
            var itemKey = keys[key];
            var item = this._items[itemKey];
            var element = item.element.cloneNode(true);
            element.classList.remove("odd");
            element.classList.remove("even");
            if (oddRow) {
                element.classList.add("odd");
            } else {
                element.classList.add("even");
            }
            oddRow = !oddRow;
            if (item.nonPaginatedItem !== null) {
                tag.appendChild(item.nonPaginatedItem.cloneNode(true));
            }
            tag.appendChild(element);
        }
        if (keys.length === 0) {
            var none = document.createElement('p');
            none.innerHTML = "<b>No information found</b>";
            tag.appendChild(none);
        }
    }
    getNumberItems() {
        return this._items.length;
    }
    display() {
        var self = this;
        this._fields._display();
        this._calcKeys();
        this._display(this);
        const idleTime = 300; // milliseconds     
        //  let mouseStopTimer;
        this._displayTag.addEventListener("cvList-redisplay", function (e) {
            self._display(self);
        });
        this._displayTag.addEventListener("cvList-reCalcKeys", function (e) {
            self._calcKeys();
            //  console.log('Done');
            self._display(self);
        });
    }
    createField(title, type) {
        return this._fields._addField(title, type);
    }
    createPagination(userOptions) {
        var options = {pagination: {
                "10 per page": 10,
                "20 per page": 20,
                "50 per page": 50,
                "View all": 0
            },
            itemsPerPage: 10
        };
        const merged = {...options, ...userOptions};
        this._pagination = new cvListPagination(this._displayTag, merged);
        return this._pagination;
    }

// internal functions

    _calcKeys() {
        var keys = Object.keys(this._items);
        this.keys = this._fields._setKeys(keys, this._items);
    }
    _display() {
        var oddRow = true;
        this._displayTag.innerHTML = '';
        var keys = this.keys;
        if (this._pagination !== null) {
            keys = this._pagination.setPagination(keys);
        }
        this._displayPagination();
        for (var key in keys) {
            var itemKey = keys[key];
            var item = this._items[itemKey];
            item.element.classList.remove("odd");
            item.element.classList.remove("even");
            if (oddRow) {
                item.element.classList.add("odd");
            } else {
                item.element.classList.add("even");
            }
            oddRow = !oddRow;
            if (item.nonPaginatedItem !== null) {
                this._displayTag.appendChild(item.nonPaginatedItem);
            }
            this._displayTag.appendChild(item.element);
        }
        if (keys.length === 0) {
            var none = document.createElement('p');
            none.innerHTML = "<b>No information found</b>";
            this._displayTag.appendChild(none);
        }
        let event = new Event("cvList-after-display");
        this._displayTag.dispatchEvent(event);

    }
    _displayPagination() {
        this._pagination._displayPagination();
    }
}
cvList.uniquenumber = 0;
cvList.uniqueID = function () {
    cvList.uniquenumber += 1;
    return 'cvlistuid' + cvList.uniquenumber; // lowercase 
};

class cvListFields {
    constructor(displayTag) {
        this._displayTag = displayTag;
        this._fields = [];
        this.no = 0;
        this.activeSort = null; // field that is active for sorting
        this.sortOrder = 1; // direction 1: asc -1: desc
        var self = this;
        this._displayTag.addEventListener("cvList-setActiveSort", function (e) {
            self.activeSort = e.cvList.activeSort;
            self.sortOrder = e.cvList.sortOrder;
            let event = new Event("cvList-reCalcKeys");
            self._displayTag.dispatchEvent(event);
        });
    }
    _display() {
        // display filters after the min / max has been calculated
        this._fields.forEach((field) => {
            field._display();
        });
    }
    _addField(title, type) {
        var no = this._fields.length + 1;
        var field = null;
        switch (type.toLowerCase()) {
            case 'text':
                field = new cvListFieldText(this._displayTag, title, no);
                break;
            case 'number':
                field = new cvListFieldNumber(this._displayTag, title, no);
                break;
            case 'date':
                field = new cvListFieldDate(this._displayTag, title, no);
                break;
            default:
                alert('Invalid type of cvList Field defined: ' + type);
                field = new cvListFieldText(this._displayTag, title, no);
                break;
        }
        this._fields.push(field);
        return field;
    }
    _setKeys(keys, items) {
        keys = this._setKeysFilters(keys, items);
        this._setKeysSort(keys, items);
        return keys;
    }
    _setKeysFilters(keys, items) {
        // get list of keys that are set
        var activeFields = [];
        for (var field of this._fields) {
            if (field._isSet()) {
                activeFields.push(field);
            }
        }
        // if null return keys
        if (activeFields.length === 0) {
            return keys;
        }
        // for each item check if it should be include against keys
        for (var i in items) {
            var item = items[i];
            var keep = true;
            for (var f of activeFields) {
                if (!f._shouldDisplayItem(item)) {
                    keep = false;
                }
            }
            if (!keep) {
                // remove key.
                var index = keys.indexOf(i);
                keys.splice(index, 1);
            }
        }
        return keys;
    }
    _setKeysSort(keys, items) {
        if (this.activeSort === null) {
            return keys;
        }
        // retrieve data for that active filter/sort
        this.activeSort._sortKeys(keys, items, this.sortOrder);
    }
}
class cvListField {
    constructor(displayTag, title, no) {
        this._displayTag = displayTag;
        this.title = title;
        this.no = no;
        this.type = 'Invalid';
    }
    addSort(tag, direction) {
        var sort = {order: 1,
            tag: tag
        };
        var which = 'Invalid';
        var self = this;
        switch (direction.toLowerCase()) {
            case 'asc':
                sort.order = 1;
                which = 'ascending';
                break;
            case 'desc':
                sort.order = -1;
                which = 'descending';
                break;
            default:
                alert('Invalid type of direction for Sort defined');
                return null;
        }
        tag.title = 'Sort ' + this.title + " " + which;
        tag.addEventListener("click", function (e) {
            var event = new Event("cvList-setActiveSort");
            event.cvList = {activeSort: self, sortOrder: sort.order};
            self._displayTag.dispatchEvent(event);
        });
    }
    addSortArrows(tag) {
        var div = this._addElement(tag, 'div', 'cvList sortArrows');
        var up = this._addElement(div, 'img', 'asc');
        this.addSort(up, 'asc');
        var down = this._addElement(div, 'img', 'desc');
        this.addSort(down, 'desc');
    }
    getType() {
        return this.type;
    }

    _getItemValue(attrib, item) {
        var search = '[' + attrib + '="' + this.no + '"]';
        const matches = item.element.querySelectorAll(search);
        if (matches.length > 0) {
            return matches[0].innerText;
        }
        return null;
    }
    _addElement(target, tagType, classes) {
        var tag = document.createElement(tagType);
        if (classes !== '') {
            tag.setAttribute('class', classes);
        }
        target.appendChild(tag);
        return tag;
    }
}
class cvListFieldText extends cvListField {
    constructor(displayTag, title, no) {
        super(displayTag, title, no);
        this.filterValue = {text: '',
            caseSensitive: false};
        this.type = 'text';
    }
    setValue(tag, value) {
        tag.setAttribute('data-cvListTextField', this.no);
        tag.innerHTML = value;
    }
    setFilter(tag) {
        var self = this;
        const idleTime = 200; // milliseconds 
        var mouseStopTimer;
        var div = this._addElement(tag, 'div', 'cvList text filter');
        var id1 = cvList.uniqueID();
        var input = this._addElement(div, 'input', '');
        input.setAttribute('id', id1);
        input.placeholder = "Filter by " + this.title;
        input.classList.add('textFilter');
        var textLabel = this._addElement(div, 'label', '');
        var id2 = cvList.uniqueID();
        textLabel.setAttribute('for', id2);
        textLabel.innerHTML = 'Match case';
        var textCase = this._addElement(div, 'input', '');
        textCase.setAttribute('id', id2);
        textCase.setAttribute('type', 'checkbox');

        input.addEventListener("keyup", function (e) {
            clearTimeout(mouseStopTimer);
            //  console.log('text filter change ' + e.target.value);
            mouseStopTimer = setTimeout(function () {
                self.filterValue = {text: e.target.value,
                    caseSensitive: textCase.checked};
                //  console.log('Process: ' + e.target.value);
                let event = new Event("cvList-reCalcKeys");
                self._displayTag.dispatchEvent(event);
            }, idleTime);
        });
        textCase.addEventListener("click", function (e) {
            let event = new Event("keyup");

            input.dispatchEvent(event);
        });
    }
    _display() {
    }
    _isSet() {
        return this.filterValue.text !== '';
    }
    _shouldDisplayItem(item) {
        var result;
        var value = super._getItemValue('data-cvListTextField', item);
        if (value === null) {
            return false;
        }
        if (this.filterValue.text === '') {
            return true;
        }
        if (this.filterValue.caseSensitive) {
            result = value.includes(this.filterValue.text);
        } else {
            result = value.toLowerCase().includes(this.filterValue.text.toLowerCase());
        }
        return result;
    }
    _sortKeys(keys, items, order) {
        var values = [];
        for (var key of keys) {
            var item = items[key];
            var text = super._getItemValue('data-cvListTextField', item);
            if (text === null) {
                values.push({key: key, value: text});
            } else {
                values.push({key: key, value: text});
            }
        }
        // sort values
        function compare(a, b) {
            if (a.value < b.value) {
                return -1 * order;
            }
            if (a.value > b.value) {
                return 1 * order;
            }
            return 0;
        }
        values.sort(compare);
        // recreate keys array
        var i = 0;
        for (var val of values) {
            keys[i] = val.key;
            i += 1;
        }
    }
}
class cvListFieldNumber extends cvListField {
    constructor(displayTag, title, no) {
        super(displayTag, title, no);
        this.filterValue = null;
        this.type = 'number';
        this.minValue = null;
        this.maxValue = null;
        this.filterTag = null;
    }
    setValue(tag, value) {
        var no;
        tag.setAttribute('data-cvListNumberField', this.no);
        tag.innerHTML = value;
        if (typeof value === 'string' || value instanceof String) {
            no = parseFloat(value.replaceAll(",", ""));
        } else {
            no = value;
        }
        if (no !== null) {
            if (this.minValue === null) {
                this.minValue = Math.floor(no);
                this.maxValue = Math.ceil(no);
            }
            if (no < this.minValue) {
                this.minValue = Math.floor(no);
            }
            if (no > this.maxValue) {
                this.maxValue = Math.ceil(no);
            }

        }
    }
    setFilter(tag) {
        this.filterTag = tag;
    }
    _display() {
        var self = this;
        if (this.filterTag === null) {
            return;
        }
        if (this.minValue === this.maxValue) {
            return;
        }
        var idleTime = 200;
        var mouseStopTimer;
        var tag = this.filterTag;
        var div = this._addElement(tag, 'div', 'cvList number range filter');
        var title = this._addElement(div, 'div', 'title');
        title.innerText = this.title;
        div.appendChild(title);
        var divAcross = this._addElement(div, 'div', 'cvList');
        var minText = this._addElement(divAcross, 'div', 'minText');
        minText.innerText = this.minValue;
        var slider = createRangeSlider(this.minValue, this.maxValue);
        divAcross.appendChild(slider);
        var maxText = this._addElement(divAcross, 'div', 'maxText');
        maxText.innerText = this.maxValue;
        slider.addEventListener("rangeSlider-change", function (e) {
            clearTimeout(mouseStopTimer);
            self.filterValue = e.range;
            minText.innerText = round(e.range.min, 4);
            maxText.innerText = round(e.range.max, 4);
            mouseStopTimer = setTimeout(function () {
                //  console.log('Range change');
                let event = new Event("cvList-reCalcKeys");
                self._displayTag.dispatchEvent(event);
            }, idleTime);
        });
        /**
         * Rounds a value to a given number of significant figures
         * @param value The number to round
         * @param significantFigures The number of significant figures
         * @returns The value rounded to the given number of significant figures
         */
        function round(value, significantFigures) {
            return removeTrailingZeros(value.toPrecision(significantFigures));
        }
        function removeTrailingZeros(value) {
            value = value.toString();
            // # if not containing a dot, we do not need to do anything
            if (value.indexOf('.') === -1) {
                return value;
            }
            // # as long as the last character is a 0 or a dot, remove it
            while ((value.slice(-1) === '0' || value.slice(-1) === '.') && value.indexOf('.') !== -1) {
                value = value.substr(0, value.length - 1);
            }
            return value;
        }
    }
    _isSet() {
        if (this.filterValue === null) {
            return false;
        }
        if (this.filterValue.min !== this.minValue) {
            return true;
        }
        if (this.filterValue.max !== this.maxValue) {
            return true;
        }
        return false;
    }
    _shouldDisplayItem(item) {
        var value = super._getItemValue('data-cvListNumberField', item);
        if (value === null) {
            return false;
        }
        var num = parseFloat(value.replaceAll(",", ""));
        if (Number.isNaN(num)) {
            return false;
        }
        if (num < this.filterValue.min) {
            return false;
        }
        if (num > this.filterValue.max) {
            return false;
        }
        return true;
    }
    _sortKeys(keys, items, order) {
        var values = [];
        for (var key of keys) {
            var item = items[key];
            var no = super._getItemValue('data-cvListNumberField', item);
            no = parseFloat(no.replaceAll(",", ""));
            if (no === null) {
                values.push({key: key, value: no});
            } else {
                values.push({key: key, value: no});
            }
        }
        // sort values
        function compare(a, b) {
            if (a.value < b.value) {
                return -1 * order;
            }
            if (a.value > b.value) {
                return 1 * order;
            }
            return 0;
        }
        values.sort(compare);
        // recreate keys array
        var i = 0;
        for (var val of values) {
            keys[i] = val.key;
            i += 1;
        }
    }
}
class cvListFieldDate extends cvListField {
    constructor(displayTag, title, no) {
        super(displayTag, title, no);
        this.filterValue = null;
        this.type = 'date';
        this.filterTag = null;
    }
    setValue(tag) {
        tag.setAttribute('data-cvListDateField', this.no);
    }
    setFilter(tag) {
        this.filterTag = tag;
    }
    _display() {
        if (this.filterTag === null) {
            return;
        }
        var tag = this.filterTag;
        var self = this;
        var input = document.createElement('input');
        input.classList.add('cvList');
        input.classList.add('text');
        input.classList.add('filter');
        input.placeholder = "Filter by " + this.title;
        tag.appendChild(input);
        input.addEventListener("keyup", function (e) {
            self.filterValue = e.target.value;
            let event = new Event("cvList-reCalcKeys");
            self._displayTag.dispatchEvent(event);
        });
    }
    _isSet() {
        return this.filterValue !== null;
    }
    _shouldDisplayItem(item) {
        var value = super._getItemValue('data-cvListDateField', item);
        if (value === null) {
            return false;
        }
        var result = value.includes(this.filterValue);
        return result;
    }
    _sortKeys(keys, items, order) {
        var values = [];
        for (var key of keys) {
            var item = items[key];
            var d;
            var txt = super._getItemValue('data-cvListDateField', item);
            txt = txt.replace("st", "");
            txt = txt.replace("rd", "");
            txt = txt.replace("nd", "");
            txt = txt.replace("th", "");
            txt = txt.replace("\n", "");
            d = this.parseDateFromFormat(txt, "DD-MM-YYYY");
            if (!this.isValidDate(d)) {
                d = new Date(txt);
            }
            if (!this.isValidDate(d)) {
                d = new Date('01-01-1970');
            }
            values.push({key: key, value: d});


        }
        // sort values
        function compare(a, b) {
            if (a.value < b.value) {
                return -1 * order;
            }
            if (a.value > b.value) {
                return 1 * order;
            }
            return 0;
        }
        values.sort(compare);
        // recreate keys array
        var i = 0;
        for (var val of values) {
            keys[i] = val.key;
            i += 1;
        }
    }
    parseDateFromFormat(dateString, format) {
        const formatParts = format.split(/[-\/]/); // supports '-' or '/' as separators
        const dateParts = dateString.split(/[-\/]/);

        let day, month, year;

        formatParts.forEach((part, idx) => {
            if (part === 'DD')
                day = parseInt(dateParts[idx], 10);
            if (part === 'MM')
                month = parseInt(dateParts[idx], 10) - 1; // JS months are 0-based
            if (part === 'YYYY')
                year = parseInt(dateParts[idx], 10);
        });

        return new Date(year, month, day);
    }
    isValidDate(date) {
        return date instanceof Date && !isNaN(date.getTime());
    }
}


class cvListPagination {
    constructor(displayTag, userOptions) {
        var defaultOptions = {pagination: {
                "20 per page": 20,
                "View all": 0
            },
            itemsPerPage: 10,
            currentPage: 1
        };
        const options = {...defaultOptions, ...userOptions};
        this.displayTag = displayTag;
        this.paginationOptions = options.pagination;
        this.itemsPerPage = options.itemsPerPage;
        this.currentPage = options.currentPage;
        this.numberPages = 0;
        this.firstItem = 0;
        this.lastItem = 0;
        this.totalItems = 0;
        this._displayItems = [];
        var self = this;
        this.displayTag.addEventListener("cvList-resetItems", function (e) {
            self.itemsPerPage = e.cvList.itemsPerPage;
            self.currentPage = 1;
            self._reportPagination();
        });
        this.displayTag.addEventListener("cvList-resetPage", function (e) {
            self.currentPage = e.cvList.pageNumber;
            self._reportPagination();
        });
    }

    addPaginationDisplayText(tag, format) {
        var item = new cvListPaginationDisplayTextItem(tag, format);
        this._displayItems.push(item);
    }
    addPaginationDisplayButton(tag, type) {
        var item = new cvListPaginationDisplayButtonItem(this.displayTag, tag, type, this.paginationOptions);
        this._displayItems.push(item);
    }
    setPagination(keys) {
        this.totalItems = keys.length;
        if (this.totalItems === 0) {
            this.firstItem = 0;
            this.lastItem = 0;
            this.numberPages = 0;
            this.currentPage = 0;
            return keys;
        }
        this.firstItem = 1;
        this.lastItem = this.totalItems;
        if (this.itemsPerPage === 0) {
            this.numberPages = 1;
            this.currentPage = 1;
            return keys;
        }
        this.numberPages = Math.ceil(this.totalItems / this.itemsPerPage);
        if (this.currentPage > this.numberPages) {
            this.currentPage = this.numberPages;
        }
        if (this.currentPage < 1) {
            this.currentPage = 1;
        }
        this.firstItem = (this.currentPage - 1) * this.itemsPerPage + 1;
        this.lastItem = this.currentPage * this.itemsPerPage;
        if (this.lastItem > this.totalItems) {
            this.lastItem = this.totalItems;
        }
        return keys.slice(this.firstItem - 1, this.lastItem);
    }

// internal functions
    _reportPagination() {
        let ev = new Event("cvList-reportPagination");
        ev.cvList = {
            itemsPerPage: this.itemsPerPage,
            currentPage: this.currentPage,
            numberPages: this.numberPages,
            totalItems: this.totalItems};
        this.displayTag.dispatchEvent(ev);
    }
    _displayPagination() {
        var data = {
            currentPage: this.currentPage,
            numberPages: this.numberPages,
            firstItem: this.firstItem,
            lastItem: this.lastItem,
            totalItems: this.totalItems,
            itemsPerPage: this.itemsPerPage
        };
        this._displayItems.forEach((item) => {
            item._displayInfo(data);
        });
    }
}
class cvListPaginationDisplayTextItem {
    constructor(tag, format) {
        this.tag = tag;
        this.format = format;
    }
    _displayInfo(data) {
        // replace the following strings with approriate values
        //  {pageNumber}, {numberPages}, {startItem}, {endItem}, {itemsNumber}
        var content = this.format;
        content = content.replaceAll("{pageNumber}", data.currentPage);
        content = content.replaceAll("{numberPages}", data.numberPages);
        content = content.replaceAll("{startItem}", data.firstItem);
        content = content.replaceAll("{endItem}", data.lastItem);
        content = content.replaceAll("{itemsNumber}", data.totalItems);
        this.tag.innerHTML = content;
    }
}

class cvListPaginationDisplayButtonItem {
    constructor(displayTag, tag, type, paginationOptions) {
        this.displayTag = displayTag;
        this.tag = tag;
        this.type = type; //  paginationButtons or itemsPerPage
        this.paginationOptions = paginationOptions;
    }
    _displayInfo(data) {

        var tag = this.tag;
        tag.innerHTML = '';
        switch (this.type) {
            case 'paginationButtons':
                this._createPaginationButtons(tag, data);
                break;
            case 'itemsPerPage':
                this._createItemsPerPageSelect(tag, data);
                break;
            default:
                tag.innerHTML = 'Invalid pagination button specified: ' + this.type;
                break;
        }
    }
    _createPaginationButtons(tag, data) {
        var current = data.currentPage;
        var start, last, previous, next;
        start = current - 4;
        if (start < 1) {
            start = 1;
        }
        last = current + 4;
        if (last > data.numberPages) {
            last = data.numberPages;
        }
        previous = current - 1;
        if (previous < 1) {
            previous = 1;
        }
        next = current + 1;
        if (next > data.numberPages) {
            next = data.numberPages;
        }
        if (data.numberPages > 1) {
            this._addPageButton(tag, 1, '|&lt;', current > 1, false);
            this._addPageButton(tag, previous, '&lt;', current > 1, false);
            for (let i = start; i < last + 1; i++) {
                this._addPageButton(tag, i, i, true, i === current);
            }
            this._addPageButton(tag, next, '&gt;', data.numberPages !== current, false);
            this._addPageButton(tag, data.numberPages, '&gt;|', data.numberPages !== current, false);
        }
    }
    _addPageButton(tag, page, text, enabled, active) {
        var self = this;
        var button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.setAttribute('data-page', page);
        button.innerHTML = text;
        button.classList.add('cvList');
        button.classList.add('pagination');
        if (!enabled) {
            button.classList.add('disabled');
        }
        if (active) {
            button.classList.add('active');
        }
        tag.appendChild(button);
        button.addEventListener("click", function (e) {
            let ev = new Event("cvList-resetPage");
            ev.cvList = {pageNumber: parseInt(e.currentTarget.getAttribute("data-page"))};
            self.displayTag.dispatchEvent(ev);
            let event = new Event("cvList-redisplay");
            self.displayTag.dispatchEvent(event);
        });
    }

    _createItemsPerPageSelect(tag, data) {
        var self = this;
        var select = document.createElement('select');
        select.setAttribute('class', 'cvList itemsPerPage nonmobile');
        select.setAttribute('name', 'itemsSelect');
        tag.appendChild(select);
        for (var key in this.paginationOptions) {
            var option = document.createElement('option');
            option.textContent = key;
            option.value = this.paginationOptions[key];
            if (parseInt(option.value) === data.itemsPerPage) {
                option.setAttribute('selected', 'selected');
            }
            select.appendChild(option);
        }
        select.addEventListener("change", function (e) {
            let ev = new Event("cvList-resetItems");
            ev.cvList = {itemsPerPage: parseInt(e.currentTarget.value)};
            self.displayTag.dispatchEvent(ev);
            let event = new Event("cvList-reCalcKeys");
            self.displayTag.dispatchEvent(event);
        });
    }
}

createRangeSlider = function (minValue, maxValue) {
    // input range's work on 0 to 1000 and result is then scalled to data values
    var realRange = {min: minValue,
        max: maxValue};
    var range = {min: 0,
        max: 1000,
        step: 1};
    var options = {
        width: 200,
        thumbnailWidth: 5
    };

    var slider = null;
    var minSlider, maxSlider;

    slider = document.createElement('div');
    slider.classList.add("min-max-slider");
    minSlider = createRangeInput('min', range.min, range.max, range.step);
    slider.appendChild(minSlider);
    maxSlider = createRangeInput('max', range.min, range.max, range.step);
    slider.appendChild(maxSlider);
    resize(slider);
    rangeSliderDraw();


    minSlider.addEventListener("input", function () {
        rangeSliderDraw();
        let event = new Event("rangeSlider-change");
        event.range = {min: getRealSliderValue(minSlider.value, range, realRange),
            max: getRealSliderValue(maxSlider.value, range, realRange)};
        slider.dispatchEvent(event);
    });
    maxSlider.addEventListener("input", function () {
        rangeSliderDraw();
        // console.log(maxSlider.value);
        let event = new Event("rangeSlider-change");
        event.range = {min: getRealSliderValue(minSlider.value, range, realRange),
            max: getRealSliderValue(maxSlider.value, range, realRange)};
        slider.dispatchEvent(event);
    });

    return slider;
    function getRealSliderValue(t, range, realRange) {
        var y = parseInt(t);
        var x = realRange.min + (y - range.min) / (range.max - range.min) * (realRange.max - realRange.min);
        return x;
    }
    function createRangeInput(type, minValue, maxValue, step) {
        var ele = document.createElement('input');
        ele.classList.add(type, 'rangeslider');
        ele.setAttribute('id', cvList.uniqueID());
        ele.setAttribute('name', cvList.uniqueID());
        ele.setAttribute('type', 'range');
        ele.setAttribute('step', step);
        ele.setAttribute('min', minValue);
        ele.setAttribute('max', maxValue);
        if (type === 'min') {
            ele.value = minValue;
        }
        if (type === 'max') {
            ele.value = maxValue;
        }
        return ele;
    }

    function resize(divElem) {
        const resizeObserver = new ResizeObserver((entries) => {
            for (const entry of entries) {
                //  console.log('Size changed:', entry.contentRect.width, entry.contentRect.height);
                options.width = entry.contentRect.width;
                rangeSliderDraw();
            }
        });
        resizeObserver.observe(divElem);
    }
    function rangeSliderDraw() {
        var minValue = parseInt(minSlider.value); // value is a string
        var maxValue = parseInt(maxSlider.value);
        var splitvalue = parseInt((minValue + maxValue) / 2);

        //  var thumbsize = options.thumbnailWidth;
        var sliderwidth = options.width;
        var minWidth = parseInt(((splitvalue - range.min) / (range.max - range.min)) * sliderwidth);
        var maxWidth = sliderwidth - minWidth;
        /* set min and max attributes */
        minSlider.setAttribute('max', splitvalue);
        maxSlider.setAttribute('min', splitvalue);
        minSlider.style.width = minWidth + 'px';
        maxSlider.style.width = maxWidth + 'px';
        minSlider.style.left = '0px';
        maxSlider.style.left = minWidth + 'px';
    }

};