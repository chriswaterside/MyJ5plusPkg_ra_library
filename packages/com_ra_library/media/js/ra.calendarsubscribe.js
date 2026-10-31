/*
 * Calendar download / subscribe form.
 * Moved here from jsonwalks/std/display.js (ra.display.subscribeCalendarForm and helpers)
 * so that it can be used by any display, not just the tabbed display.
 *
 * Nothing here creates .ics content: downloads and subscriptions are all served by
 *   index.php?option=com_ra_library&task=calendarfeed.calendarfeed...
 *
 * What can be done (the server, calendarfeed controller, creates all .ics content):
 *   Download   a one-off file of the walks/events the page is showing: sent as a list of ids
 *              (at most 100), so display filters are honoured exactly.
 *   Subscribe  the groups shown plus filters (day, type, grade, distance, maximum), kept up to date.
 *   One walk   ra.calendarsubscribe.walkUrls(baseUrl, id) gives download and subscribe links.
 *
 * Usage:
 *   ra.calendarsubscribe.open({
 *       baseUrl:      'https://example.org/',      // Uri::root(), must end with /
 *       groups:       [{code:'SR05', name:'..'}],  // or a Map: groups offered for subscribing
 *       ids:          ['100493667', ...],          // walks/events to download
 *       idsTotal:     120,                         // optional, total available if ids was cut to 100
 *       calendarName: 'Ramblers Walks',            // optional
 *       lists:        {types:[], days:[], grades:[], distances:[]} // each [{code, name}, ...], from PHP Feedfilters
 *   });
 * Either give an options object, or a function returning one (used when the options depend on
 * what is on the page when the button is clicked).
 *
 * Buttons output by PHP (Calendarbutton helper) carry the options in data attributes
 * and are wired up automatically:
 *   <button class="ra-calsub-button" data-options='{...json...}'>Add to calendar</button>
 */
var ra = ra || {};
if (typeof (ra.html) === "undefined") {
    ra.html = {};
}
ra.calendarsubscribe = ra.calendarsubscribe || {};

(function () {
    var cs = ra.calendarsubscribe;

    // The filter lists, each [{code, name}, ...]. PHP (Library/Ics/Feedfilters.php) is the only
    // place they are defined, and it supplies them in options.lists.
    cs.getLists = function (options) {
        var l = options.lists || {};
        return {
            types: l.types || [],
            days: l.days || [],
            grades: l.grades || [],
            distances: l.distances || []
        };
    };

    cs.getBaseUrl = function (options) {
        var base = options.baseUrl;
        if (!base) {
            base = window.location.origin + '/';
        }
        if (base.slice(-1) !== '/') {
            base += '/';
        }
        return base;
    };

    // Links to the controller for ONE walk/event (Walks Manager id).
    // download: one-off file.  httpsUrl / webcalUrl: subscription. Once the walk is over a week old
    // the feed holds a single event telling the user to remove the subscription.
    cs.walkUrls = function (baseUrl, id) {
        var base = cs.getBaseUrl({baseUrl: baseUrl});
        var path = 'index.php?option=com_ra_library&task=calendarfeed.calendarfeed&ids=' + encodeURIComponent(String(id));
        return {
            downloadUrl: base + path + '&download=1',
            httpsUrl: base + path,
            webcalUrl: 'webcal://' + base.replace(/^https?:\/\//i, '') + path
        };
    };

    // builds the url used for download links; filters are applied as the form currently stands
    cs.buildFeedUrls = function (options, form, opts) {
        opts = opts || {};
        var result = form.getStatus();
        var lists = cs.getLists(options);

        function fail(message) {
            if (!opts.silent) {
                ra.showError(message);
            }
            return null;
        }
        var params = new URLSearchParams();
        params.set('option', 'com_ra_library');
        params.set('task', 'calendarfeed.calendarfeed');
        if (!options.hasGroups) {
            return fail('No groups available');
        }
        if (result.groups) {
            params.set('groups', result.groups);
        } else {
            return fail('You must select at least one Group');
        }
        // The url names the values the user UNTICKED, in nodow, notype, nograde and nodist.
        // Nothing unticked means the filter is left out, so new values are included automatically.
        function addFilter(param, chosen, list, message) {
            if (!chosen || chosen.length === 0) {
                fail(message);
                return false;
            }
            var left = list.filter(function (item) {
                return chosen.indexOf(String(item.code)) < 0;
            }).map(function (item) {
                return item.code;
            });
            if (left.length > 0) {
                params.set(param, left.join(','));
            }
            return true;
        }
        if (!addFilter('notype', result.type, lists.types, 'You must select at least one type')
                || !addFilter('nodow', result.dow, lists.days, 'You must select at least one day of the week')
                || !addFilter('nograde', result.grades, lists.grades, 'You must select at least one grade')
                || !addFilter('nodist', result.distance, lists.distances, 'You must not deselect all distance options')) {
            return null;
        }
        params.set('limit', result.number);

        var base = cs.getBaseUrl(options);
        var path = 'index.php?' + params.toString();
        var withoutScheme = base.replace(/^https?:\/\//i, '');
        return {
            webcalUrl: 'webcal://' + withoutScheme + path,
            httpsUrl: base + path
        };
    };

    cs.subscribeToCalendar = function (options, type, form) {
        var urls = cs.buildFeedUrls(options, form);
        if (!urls) {
            return;
        }
        var url = urls.webcalUrl;
        switch (type) {
            case 'google':
                url = 'https://calendar.google.com/calendar/r?cid=' + encodeURIComponent(urls.httpsUrl);
                break;
            case 'outlook':
                url = 'https://outlook.office.com/calendar/0/addfromweb?url=' + encodeURIComponent(urls.httpsUrl)
                        + '&name=' + encodeURIComponent(options.calendarName || 'Ramblers Walks');
                break;
        }
        window.open(url, '_blank');
    };

    cs.addButton = function (tag, name, title) {
        var b = document.createElement('button');
        b.innerHTML = name;
        b.classList.add('link-button', 'tiny', 'button', 'mintcake');
        if (title) {
            b.title = title;
        }
        tag.appendChild(b);
        return b;
    };

    // creates the 'Add to calendar' button that opens the form
    cs.createButton = function (tag, options, text) {
        var button = cs.addButton(tag, text || 'Add to calendar');
        button.addEventListener('click', function () {
            cs.open(typeof options === 'function' ? options() : options);
        });
        return button;
    };

    cs.open = function (options) {
        options = options || {};
        cs.defineGroupedItemsSelect();
        var lists = cs.getLists(options);

        var groups = options.groups || [];
        var count = (groups instanceof Map) ? groups.size : groups.length;
        options.hasGroups = count > 0;

        var numberOptions = [
            {text: '100', value: '100'},
            {text: '50', value: '50'},
            {text: '25', value: '25'},
            {text: '10', value: '10'},
            {text: '5', value: '5'}];

        var div = document.createElement("div");
        div.style.display = "inline-block";
        ra.modals.createModal(div, false);

        const desc = document.createElement('div');
        desc.classList.add('ra-calendar-desc');
        desc.innerHTML = '<b>This option allows you to add our Walks/Events to your calendar</b>' +
                '<p>There are two basic options:</p><ul style="margin-top:0px;">' +
                '<li>Download a calendar/.ICS file of the Walks/Events currently shown and manually add this to your calendar</li>' +
                '<li>Subscribe to our Walks/Events, and your calendar will automatically update information</li>' +
                '</ul>';
        div.appendChild(desc);
        div.appendChild(document.createElement('hr'));

        // The download is a link to the server for the walks/events shown (a list of ids).
        // The old version built the .ics in the browser (ra.ics.file).
        var ids = (options.ids || []).slice(0, 100);
        var down = cs.addButton(div, 'Download calendar .ics file (' + ids.length + ' walks/events)',
                'Add the walks currently shown to your calendar, you will need to repeat this action to get updates');
        if (ids.length === 0) {
            down.disabled = true;
            down.title = 'There are no future walks/events to download';
        } else {
            down.addEventListener('click', function () {
                var params = new URLSearchParams();
                params.set('option', 'com_ra_library');
                params.set('task', 'calendarfeed.calendarfeed');
                params.set('ids', ids.join(','));
                params.set('download', '1');
                window.location.href = cs.getBaseUrl(options) + 'index.php?' + params.toString();
            });
        }
        if (options.idsTotal && options.idsTotal > ids.length) {
            var note = document.createElement('div');
            note.classList.add('ra-calendar-desc');
            note.innerHTML = '<p>Only the first ' + ids.length + ' of ' + options.idsTotal +
                    ' walks/events can be downloaded at once. Subscribe to get them all.</p>';
            div.appendChild(note);
        }
        div.appendChild(document.createElement('hr'));
        if (options.hasGroups) {
            var subDesc = document.createElement('div');
            subDesc.classList.add('ra-calendar-desc');
            subDesc.innerHTML = '<p><b>Subscribe</b> to all future walks/events of the groups below, not just those shown now. ' +
                    'Deselect anything you do not want.</p>';
            div.appendChild(subDesc);
        }

        const filters = [];
        if (options.hasGroups) {
            filters.push({title: 'Groups', type: 'groups', values: groups});
        }
        filters.push({title: 'Day of week', type: 'dow', values: lists.days},
                {title: 'Type', type: 'type', values: lists.types},
                {title: 'Grade', type: 'grades', values: lists.grades},
                {title: 'Distance', type: 'distance', values: lists.distances},
                {title: 'Maximum walks/events', select: 'yes', type: 'number', values: numberOptions});
        var filtersForm = new ra.html.groupedItemsSelect();
        filtersForm.display(div, filters);

        var sub = cs.addButton(div, 'Subscribe to calendar', 'Subscribe and select which calendar app you use');
        sub.addEventListener('click', function () {
            cs.subscribeToCalendar(options, 'webcal', filtersForm);
        });
        var google = cs.addButton(div, 'Subcribe via Google Calendar', 'Subscribe to walks using Google');
        google.addEventListener('click', function () {
            cs.subscribeToCalendar(options, 'google', filtersForm);
        });
        var outlook = cs.addButton(div, 'Subcribe via Outlook', 'Subscribe to walks using Outlook');
        outlook.addEventListener('click', function () {
            cs.subscribeToCalendar(options, 'outlook', filtersForm);
        });
        sub.disabled = true;
        google.disabled = true;
        outlook.disabled = true;

        // Copyable link, for calendar apps that need a pasted URL rather than a one-click handoff
        const linkDesc = document.createElement('div');
        linkDesc.classList.add('ra-calendar-desc');
        linkDesc.innerHTML = '<p>Having trouble? Copy the link below and use your calendar app\'s own \'Add by URL\' option instead</p>';
        div.appendChild(linkDesc);

        var urlField = document.createElement('input');
        urlField.type = 'text';
        urlField.readOnly = true;
        urlField.classList.add('ra-calendar-url');
        urlField.placeholder = 'Adjust the filters above to build a link';
        div.appendChild(urlField);

        var copyLink = cs.addButton(div, 'Copy link', 'Copy the calendar link to your clipboard');
        copyLink.disabled = true;
        copyLink.addEventListener('click', function () {
            if (!urlField.value) {
                return;
            }
            navigator.clipboard.writeText(urlField.value).then(function () {
                var original = copyLink.innerHTML;
                copyLink.innerHTML = 'Copied!';
                setTimeout(function () {
                    copyLink.innerHTML = original;
                }, 1500);
            });
        });

        // Auto-regenerate the link whenever a filter changes
        var updateTimer = null;
        function setEnabled(on) {
            copyLink.disabled = !on;
            sub.disabled = !on;
            google.disabled = !on;
            outlook.disabled = !on;
        }
        function scheduleLinkUpdate() {
            clearTimeout(updateTimer);
            updateTimer = setTimeout(function () {
                var urls = cs.buildFeedUrls(options, filtersForm, {silent: true});
                if (urls) {
                    urlField.value = urls.httpsUrl;
                    setEnabled(true);
                } else {
                    urlField.value = '';
                    setEnabled(false);
                }
            }, 50);
        }
        div.addEventListener('click', scheduleLinkUpdate);
        div.addEventListener('change', scheduleLinkUpdate);
        scheduleLinkUpdate(); // populate immediately if defaults are already valid
    };

    // wire up buttons output by PHP
    cs.initButtons = function () {
        document.querySelectorAll('button.ra-calsub-button:not([data-ra-init])').forEach(function (button) {
            button.setAttribute('data-ra-init', '1');
            var options = {};
            try {
                options = JSON.parse(button.getAttribute('data-options') || '{}');
            } catch (e) {
                options = {};
            }
            button.addEventListener('click', function () {
                cs.open(options);
            });
        });
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', cs.initButtons);
    } else {
        cs.initButtons();
    }
})();

// Moved from jsonwalks/std/display.js. The class itself is unchanged.
// ra.js replaces ra.html when it loads, so the definition is wrapped in a function and
// run once ra.js is present (immediately if it already is, otherwise when the form is opened).
ra.calendarsubscribe.defineGroupedItemsSelect = function () {
if (typeof (ra.html.groupedItemsSelect) !== "undefined") {
    return;
}
ra.html.groupedItemsSelect = function () {
    this.container = null;
    this.form = null;
    this.display = function (tag, options) {
        this.container = document.createElement('div');
        this.container.classList.add('ra-container');
        tag.appendChild(this.container);
        var heading = document.createElement('h3');
        heading.classList.add('ra-container-header');
        heading.innerHTML = "Deselect any walks/events you don't wish to subscribe to";
        this.container.appendChild(heading);
        this.form = document.createElement('div');
        this.form.classList.add('ra-walksfilter');
        this.container.appendChild(this.form);

        options.forEach(option => {
            const set = document.createElement('div');
            set.classList.add("ra-filtergroup");
            const heading = document.createElement('h3');
            heading.innerHTML = option.title;
            const container = document.createElement('div');
            set.append(heading, container);
            this.form.appendChild(set);
            if (option.select === 'yes') {
                this.createDropDown(container, option.type, option.values);
            } else {
                this._renderCheckboxGroup(container, option.type, option.values);
            }
        });
        return this.container;
    };
    this.getStatus = function () {
        if (this.form === null) {
            return null;
        }
        const result = {};
        this.form.querySelectorAll('.active[data-type][data-code]').forEach(el => {
            const type = el.dataset.type;
            const code = el.dataset.code;
            if (!result[type]) {
                result[type] = [];
            }
            result[type].push(code);
        });
        this.form.querySelectorAll('select[data-type]').forEach(el => {
            const type = el.dataset.type;
            var value = el.value;
            result[type] = value;
        });
        return result;
    };
    this._renderCheckboxGroup = function (container, type, items) {
        const fragment = document.createDocumentFragment();

        items.forEach((item, index) => {
            const label = typeof item === 'object' && item.name ? item.name : item;
            const code = typeof item === 'object' && item.code ? item.code : index;

            const input = document.createElement('div');
            input.setAttribute('data-code', code);
            input.setAttribute('data-type', type);
            input.innerHTML = label;
            input.classList.add('feed-field', 'active');
            input.addEventListener('click', (event) => {
                var tag = event.target;
                if (tag.classList.contains('active')) {
                    tag.classList.remove('active');
                    tag.classList.add('inactive');
                } else {
                    tag.classList.add('active');
                    tag.classList.remove('inactive');
                }
            });
            fragment.appendChild(input);
        });
        container.appendChild(fragment);
    };
    this.createDropDown = function (tag, type, options) {
//         var config = [
//            {text: 'Add to calendar options:-', value: 'none'},
//            {text: 'Subscribe to calendar', value: 'webcal'},
//            {text: 'Add to Google Calendar', value: 'google'},
//            {text: 'Add to Outlook', value: 'outlook'},
//            {text: 'Download calendar(.ics) file', value: 'download'}];
        var div = ra.html.createElement(tag, 'span');


        var select = ra.html.createElement(div, 'select');
        select.setAttribute('data-type', type);
        options.forEach(opt => {
            var ele = document.createElement('option');
            ele.setAttribute('value', opt.value);
            ele.innerHTML = opt.text;
            select.appendChild(ele);
        });
        return select;

    };

};
};
if (typeof (ra.html.createElement) !== "undefined") {
    ra.calendarsubscribe.defineGroupedItemsSelect();
}
