/**
 * "Style 1" GPX display - a plain list of GPX file links (see
 * ItemRenderer::buildGpxMapList() / {gpx_map_list}), each opening that one
 * route on a map in a popup when clicked.
 *
 * Rather than building its own map-loading logic, this reuses
 * ra.display.gpxSingle (media/js/leaflet/gpx/maplist.js) completely
 * unchanged - the exact same class the standalone single-route map page
 * (DisplayHelper::displayRoutesSingle() / Leaflet\Gpx\Map::displayPath())
 * already uses, so route line, start/end markers, waypoints and the
 * elevation profile all look and behave identically wherever a route is
 * shown. It's opened into a ra.modals popup - the same modal stacking
 * system the photo lightbox (ra.lightbox.js) already uses - rather than a
 * dedicated page, so this can be triggered from anywhere {gpx_map_list}
 * is used (List, Blog, Single Item) without navigating away.
 *
 * Each click builds its own fresh map only when clicked - nothing is
 * pre-built or pre-fetched for files the visitor never opens. The heavy
 * Leaflet asset stack and licensed map options (API keys etc) this relies
 * on are loaded once per page by ItemRenderer::loadGpxMapAssets(),
 * whichever/however many {gpx_map_list} tokens end up rendering.
 *
 * If JavaScript is unavailable, the existing <a href="..."> still works as
 * a plain link to the raw GPX file - this script only intercepts the
 * click, it isn't required for the file to be reachable at all.
 *
 * @since 1.0.0
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest('.ra-gpx-map-link') : null;

        if (!link) {
            return;
        }

        e.preventDefault();

        var wrapper = document.createElement('div');
        wrapper.className = 'ra-gpx-map-modal';

        var detailsDiv = document.createElement('div');
        var mapDiv = document.createElement('div');
        wrapper.appendChild(detailsDiv);
        wrapper.appendChild(mapDiv);

        var mapId = ra.uniqueID();
        mapDiv.id = mapId;
        detailsDiv.id = 'details-' + mapId;

        // .ra-gpx-map-modal (frontend.css) gives the popup an explicit
        // width. Without one, the modal's own CSS (display:table; width:
        // auto - the same shrink-to-fit trick that sizes the photo
        // lightbox around its <img>) has nothing concrete to size around
        // for an empty div, and the map's "width:100%" collapses to
        // nothing before Leaflet ever measures it.

        // Open the modal (attaches wrapper - and mapDiv/detailsDiv inside
        // it - into the live DOM) BEFORE building the map, since
        // ra.display.gpxSingle looks its container up by id via
        // document.getElementById() and needs it to already be attached.
        ra.modals.createModal(wrapper, true);

        var options = Object.assign({}, ra.defaultMapOptions, {
            divId: mapId,
            mapHeight: '450px',
            mapWidth: '100%',
            displayElevation: true,
            cluster: false,
            helpPage: 'singleroute.html'
        });

        var data = {
            // The bare, root-relative file path (not the fully-qualified
            // href) - ra.display.gpxSingle/maplist.js prefixes this itself
            // with ra.baseDirectory() when it fetches the file, so passing
            // the already-domain-qualified href here would double up the
            // site URL and the request would 403/404.
            gpxfile: link.getAttribute('data-gpx-path'),
            linecolour: '#782327',
            imperial: false,
            detailsDivId: detailsDiv.id
        };

        var display = new ra.display.gpxSingle(options, data);
        display.load();
    });
})();
