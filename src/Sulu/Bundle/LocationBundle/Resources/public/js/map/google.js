/*
 * This file is part of the Husky Validation.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * Google Maps adapter
 *
 */
define([], function() {
    return function GMaps(selector, providerOptions, options) {

        this.pendingShow = null;
        this.google = null;
        this.map = null;
        this.marker = null;

        var gmapApiUrl = 'http://maps.google.com/maps/api/js?sensor=false';

        if (providerOptions.api_key) {
            gmapApiUrl = gmapApiUrl + '&key=' + providerOptions.api_key;
        }

        // NOTE: We include the dependency here so that we have access
        //       to the API key (given in the providerOptions).
        //
        //       This is why this adapter might seem somewhat strange.
        //
        require(['async!' + gmapApiUrl], function () {
            this.google = google;
            this.map = new google.maps.Map(document.getElementById(selector), {});
            this.marker = new google.maps.Marker({
                map: this.map,
                draggable: options.draggableMarker
            });

            // register position update callback
            if (options.positionUpdateCallback) {
                google.maps.event.addListener(this.marker, 'position_changed', function () {
                        options.positionUpdateCallback(this.marker.position.B, this.marker.position.k);
                }.bind(this));
            }

            // register zoom change callback
            if (options.zoomChangeCallback) {
                google.maps.event.addListener(this.map, 'zoom_changed', function () {
                    options.zoomChangeCallback(this.map.getZoom());
                }.bind(this));
            }

            // the show method of this object will be called before the gmap has loaded,
            // in which case we will show it now
            if (this.pendingShow) {
                this.show(this.pendingShow.lng, this.pendingShow.lat, this.pendingShow.zoom);
                this.pendingShow = null;
            }
        }.bind(this));

        this.show = function (long, lat, zoom) {
            if (this.google === null) {
                this.pendingShow = {lng: long, lat: lat, zoom: zoom};
                return;
            }

            var position = new this.google.maps.LatLng(lat, long);
            this.map.setCenter(position);
            this.map.setZoom(parseInt(zoom));
            this.marker.setPosition(position);
        }.bind(this);
    };
});
