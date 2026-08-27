/* 
 * Copyright (C) 2024 chris vaughan
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

var document;
if (typeof (ra) === "undefined") {
    ra = {};
}

ra.tabs = function (tag, options) {

    var defaultOptions = {tabClass: 'myclass',
        style: {side: 'left', // left or right
            vert: 'above', // 'above', 'middle' or 'below'
            size: 'normal'}, // normal, medium or small
        tabs: {id0: {title: "one"},
            id10: {title: "two"},
            id20: {title: "three"}, staticContainer: true,
            id30: {title: "extras", enabled: false}}
    };
    this.options = {...defaultOptions, ...options};
    this.buttons = [];
    this.containers = [];
    var tags = [
        {name: 'container', parent: 'root', tag: 'div', attrs: {class: 'ra tabs'}},
        {name: 'tabContainer', parent: 'container', tag: 'div', attrs: {class: 'ra tabContainer ' + this.options.style.size + ' ' + this.options.style.side+ ' ' + this.options.style.vert}},
        {name: 'contentContainer', parent: 'container', tag: 'div', attrs: {class: 'ra contentContainer ' + this.options.style.size + ' ' + this.options.style.side+ ' ' + this.options.style.vert}},
        {name: 'dynamicContainer', parent: 'contentContainer', tag: 'div', attrs: {'data-container': 'dynamicContainer'}}
    ];
    this.tabsContainer = tag;
    this.elements = ra.html.generateTags(tag, tags);
    this.containers.push(this.elements.dynamicContainer);
    if ('tabClass' in options) {
        this.elements.container.classList.add(options.tabClass);
    }
    for (const property in this.options.tabs) {
        var id = property;
        var item = this.options.tabs[id];
        var enabled = true;
        var title = item.title;
        if ('enabled' in item) {
            enabled = item.enabled;
        }
        if (enabled) {
            var button = document.createElement('button');
            button.innerHTML = title;
            button.setAttribute("data-id", id);
            this.elements.tabContainer.appendChild(button);
            this.buttons.push(button);
            if ('staticContainer' in item) {
                if (item.staticContainer) {
                    var staticContainer = document.createElement('div');
                    staticContainer.style.display = 'none';
                    staticContainer.setAttribute("data-container", id);
                    this.elements.contentContainer.appendChild(staticContainer);
                    this.containers.push(staticContainer);
                }
            }
        }
    }
    var _this = this;
    this.buttons.forEach((button) => {
        button.addEventListener("click", function (e) {
            _this.buttons.forEach((item) => {
                item.classList.remove("active");
            });
            _this._displayContent(this);
        });
    });
    this.display = function () {
        this._displayContent(this.buttons[0]);
    };
    this.getStaticContainer = function (id) {
        for (var cont of this.containers) {
            if (cont.getAttribute('data-container') === id) {
                return cont;
            }
        }
        return null;
    };
    this.getDynamicContainer = function () {
        return this.elements.dynamicContainer;
    };
    this.changeTabTitle = function (tabId, newTitle) {
        var selector = '[data-id="' + tabId + '"]';
        var tag = this.elements.tabContainer.querySelectorAll(selector);
        if (tag.length > 0) {
            tag[0].innerHTML = newTitle;
        }
    };
    this.clickToTab = function (tabId) {
        var tabs = this.tabsContainer.querySelectorAll('[data-id="' + tabId + '"]');
        if (tabs.length > 0) {
            tabs[0].click();
        }
    };

    this._displayContent = function (button) {
        var id = button.getAttribute("data-id");
        button.classList.add("active");
        // find static container?
        // var tags = this.elements.contentContainer.querySelectorAll('[data-container]');
        for (var tag of this.containers) {
            tag.style.display = 'none';
        }
        var container = this.getStaticContainer(id);
        //  var container = this.elements.contentContainer.querySelectorAll('[data-container="' + id + '"]');
        if (container !== null) {
            container.style.display = '';
            const displayEvent = new Event("displayTabContents");
            displayEvent.tabDisplay = {tab: id,
                displayInElement: container, tabType: 'static'};
            this.tabsContainer.dispatchEvent(displayEvent);
        } else {
            // set attribute to allow different styling for each content
            this.elements.dynamicContainer.setAttribute('data-tab', id);
            this.elements.dynamicContainer.textContent = "";
            this.elements.dynamicContainer.style.display = '';
            const displayEvent = new Event("displayTabContents");
            displayEvent.tabDisplay = {tab: id,
                tabType: 'dynamic',
                displayInElement: this.elements.dynamicContainer};
            this.tabsContainer.dispatchEvent(displayEvent);
        }
    };
};