/* 
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */
var L, ra, document;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.map) === "undefined") {
    ra.map = {};
}
ra.map.Settings = function () {

    this.saveOptions = {
        saveSettings: false
    };
    this.controlsWithSettings = [];
    this.cookieName = '__settingsSave';
    this.add = function (tag, leafletMap) {
        var _this = this;
        ra.settings.read(this.cookieName, this.saveOptions);
        var settingsDiv = document.createElement('div');
        settingsDiv.setAttribute('class', 'settings');
        tag.appendChild(settingsDiv);
        var title = document.createElement('h4');
        title.textContent = "Settings";
        settingsDiv.appendChild(title);

        var tabOptions = {
            style: {side: 'left', // left or right
                vert:'above',
                size: 'medium'}, // normal, medium or small
            tabs: {route: {title: 'Plot Walking Route', staticContainer: true, enabled: true},
                mouse: {title: 'Mouse position', staticContainer: true, enabled: true},
                rightmouse: {title: 'Mouse Right Click', staticContainer: true, enabled: true},
                mylocation: {title: 'My Location', staticContainer: true, enabled: true},
                save: {title: 'Save<sup>ON</sup>/Reset Settings', staticContainer: true, enabled: true}}
        };

        tabOptions.tabs.route.enabled = leafletMap.plotControl() !== null;
        tabOptions.tabs.mouse.enabled = leafletMap.mouseControl() !== null;
        tabOptions.tabs.rightmouse.enabled = leafletMap.rightclickControl() !== null;
        tabOptions.tabs.mylocation.enabled = leafletMap.mylocationControl() !== null;
        tabOptions.tabs.save.enabled = true;
        this.tabs = new ra.tabs(settingsDiv, tabOptions);
        this.tabs.display();

        if (tabOptions.tabs.route.enabled) {
            var tag = this.tabs.getStaticContainer('route');
            leafletMap.plotControl().settingsForm(tag);
            _this.controlsWithSettings.push(leafletMap.plotControl());
        }
        if (tabOptions.tabs.mouse.enabled) {
            var tag = this.tabs.getStaticContainer('mouse');
            leafletMap.mouseControl().settingsForm(tag);
            _this.controlsWithSettings.push(leafletMap.mouseControl());
        }
        if (tabOptions.tabs.rightmouse.enabled) {
            var tag = this.tabs.getStaticContainer('rightmouse');
            leafletMap.rightclickControl().settingsForm(tag);
            _this.controlsWithSettings.push(leafletMap.rightclickControl());
        }
        if (tabOptions.tabs.mylocation.enabled) {
            var tag = this.tabs.getStaticContainer('mylocation');
            leafletMap.mylocationControl().settingsForm(tag);
            _this.controlsWithSettings.push(leafletMap.mylocationControl());
        }

        this._addSave(this.tabs.getStaticContainer('save'));
        document.addEventListener("ra-setting-changed", function (e) {
            _this._saveSettings();
        });
    };

    this._addSave = function (tag) {
        var hdg1 = document.createElement('h3');
        hdg1.textContent = 'Save settings between sessions';
        tag.appendChild(hdg1);
        var save = ra.html.input.yesNo(tag, '', "Save settings between sessions/future visits to web site (you accept cookies)", this.saveOptions, 'saveSettings');
        var reset = ra.html.input.action(tag, '', "Reset all settings to default values", 'Reset');
        var _this = this;
        reset.addEventListener("action", function (e) {
            // _this.controlsWithSettings.forEach(function (control) {

            // });
            for (var control of _this.controlsWithSettings) {
                control.resetSettings();
            }
            setTimeout(function () {
                ra.html.input.actionReset(reset);
            }, 500);
        });
        save.addEventListener("click", function (e) {
            _this._saveSettings();

        });
    };

    this._saveSettings = function () {
        var saveValues = this.saveOptions.saveSettings;
        var title = '';
        if (saveValues) {
            title = 'Save<sup>ON</sup>/Reset Settings';
        } else {
            title = 'Save<sup>OFF</sup>/Reset Settings';
        }
        this.tabs.changeTabTitle('save', title);

        ra.settings.save(saveValues, this.cookieName, this.saveOptions);
        this.controlsWithSettings.forEach(function (control) {
            control.saveSettings(saveValues);
        });
    };
};