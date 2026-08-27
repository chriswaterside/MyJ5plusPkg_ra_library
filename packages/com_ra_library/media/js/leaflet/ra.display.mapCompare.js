var L, ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.display) === "undefined") {
    ra.display = {};
}
ra.display.mapCompare = function (options, data) {
    this.formatOptions = ["In line Maps", "Side by side"];

    this.mapDiv1 = null;
    this.mapDiv2 = null;
    this.noEvents = 0;
    this.options = options;  //public
    this.masterdiv = document.getElementById(options.divId);


    this.format = document.createElement("div");
    this.format.textContent = this.formatOptions[0];
    this.masterdiv.appendChild(this.format);
    var self = this;
    this.format.addEventListener("click", function (event) {
        self.swapFormat();
    });
    this.mapDiv1 = document.createElement("div");
    this.mapDiv1.classList.add("ra-map-compare");
    this.mapDiv1.classList.add("first");
    this.masterdiv.appendChild(this.mapDiv1);
    this.mapDiv2 = document.createElement("div");
    this.mapDiv2.classList.add("ra-map-compare");
    this.mapDiv2.classList.add("second");
    this.masterdiv.appendChild(this.mapDiv2);

    this.mapDiv1.classList.add("sidebyside");
    this.mapDiv2.classList.add("sidebyside");


    this.load = function () {
        var lmap = new ra.leafletmap(this.mapDiv1, options);
        lmap.display();
        this._map1 = lmap.map();
        lmap = new ra.leafletmap(this.mapDiv2, options);
        lmap.display();
        this._map2 = lmap.map();

        var self = this;
        self.noEvents = 0;
        this._map1.on("moveend", function (e) {
            self.syncBounds(self._map1, self._map2);
        });
        this._map2.on("moveend", function (e) {
            self.syncBounds(self._map2, self._map1);
        });

    };
    this.syncBounds = function (map1, map2) {
        if (self.noEvents > 0) {
            self.noEvents = 0;
            return;
        }
        self.noEvents += 1;
        var bounds = map1.getBounds();
        map2.fitBounds(bounds);
    };
    this.swapFormat = function () {
        if (this.format.textContent === this.formatOptions[0]) {
            this.format.textContent = this.formatOptions[1];
            this.mapDiv1.classList.remove("sidebyside");
            this.mapDiv2.classList.remove("sidebyside");
        } else {
            this.format.textContent = this.formatOptions[0];
            this.mapDiv1.classList.add("sidebyside");
            this.mapDiv2.classList.add("sidebyside");

        }
        this._map1.invalidateSize();
        this._map2.invalidateSize();
    };

};