/*
 * This file is part of the Husky Validation.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

define(['require', 'leaflet'], function (require, leaflet) {
    'use strict';

    /**
     * Leaflet map class
     * @param selector - Put the map in the DOM element with this ID
     * @param options - Options for the map
     */
    return function Leaflet(selector, providerOptions, options) {
        var map = leaflet.map(selector);
        var marker = null;

        // add an OpenStreetMap tile layer
        L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        if (options.zoomChangeCallback) {
            map.on('zoomend', function () {
                options.zoomChangeCallback(map.getZoom());
            });
        }

        /**
         * Show a position on the map
         * @param long - Longitude
         * @param lat - Latitude
         * @param zoom - Zoom
         */
        this.show = function (long, lat, zoom) {
            map.setView([lat, long], zoom);

            var MarkerIcon = L.Icon.Default.extend({
                options: {
                    iconUrl: require.toUrl('../../../sululocation/js/vendor/leaflet/images/marker-icon.png'),
                    shadowUrl: require.toUrl('../../../sululocation/js/vendor/leaflet/images/marker-shadow.png')
                }
            });

            if (null === marker) {
                // create new marker
                marker = L.marker([lat, long], {
                    icon: new MarkerIcon(),
                    draggable: options.draggableMarker
                });

                // update coordinates
                marker.on('drag', function (e) {
                    var marker = e.target;
                    var latLng = marker.getLatLng();
                    if (options.positionUpdateCallback) {
                        options.positionUpdateCallback(latLng.lng, latLng.lat);
                    }
                });

                marker.addTo(map);

            } else {
                // update existing marker
                marker.setLatLng([lat, long]);
            }
        };
    };
});
