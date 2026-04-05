/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.display) === "undefined") {
    ra.display = {};
}
ra.display.walksMap = function (mapOptions, data) {
    this.options = null;
    this.walkClass = "walk";
    var b = ra.baseDirectory();
    var sImg = b + "media/com_ra_library/images/marker-start.png";
    var cImg = b + "media/com_ra_library/images/marker-cancelled.png";
    var aImg = b + "media/com_ra_library/images/marker-area.png";
    this.legend = '<p><strong>Zoom</strong> in to see where our walks are going to be. <strong>Click</strong> on a walk to see details.</p>';
    this.legend += '<img src="' + sImg + '" alt="Walk start" height="26" width="26">&nbsp; Start locations&nbsp; <img src="' + cImg + '" alt="Cancelled walk" height="26" width="26"> Cancelled walk&nbsp; <img src="' + aImg + '" alt="Walking area" height="26" width="26"> Walk in that area.';
    this.options = mapOptions;
    this.data = data;
    //   this.walks = null;

    this.load = function ( ) {
        var tags = [
            {name: 'container', parent: 'root', tag: 'div'},
            {name: 'legendtop', parent: 'container', tag: 'div'},
            {name: 'map', parent: 'container', tag: 'div', attrs: {id: 'ra-map'}},
            {name: 'legendbottom', parent: 'container', tag: 'div'}
        ];
        //   this.walks = new ra.events();
        this.masterdiv = document.getElementById(this.options.divId);

        this.elements = ra.html.generateTags(this.masterdiv, tags);
        switch (this.data.legendposition) {
            case "top":
                this.elements.legendtop.innerHTML = this.legend;
                break;
            case "bottom":
                this.elements.legendbottom.innerHTML = this.legend;
                break;
            default:
                this.elements.legendtop.innerHTML = this.legend;
                this.elements.legendbottom.innerHTML = this.legend;
        }

        this.lmap = new ra.leafletmap(this.elements.map, this.options);
        this.lmap.display();
        this.map = this.lmap.map();
        this.cluster = new ra.map.cluster(this.map);
        var walks = new ra.events();
        this.data.walks.forEach(phpwalk => {
            var newEvent = new ra.event();
            newEvent.convertPHPWalk(phpwalk);
            walks.registerEvent(newEvent);
            ra.walk.registerEvent(newEvent);

        });
        this.data.walks = null;

        this.displayWalks(walks);
    };
    this.displayWalks = function (walks) {
        this.cluster.removeClusterMarkers();
        walks.forEachAll(walk => {
            walk.addWalkMarker(this.cluster, this.walkClass);
        });
        this.cluster.addClusterMarkers();
        this.cluster.zoomAll();
        return;
    };

};