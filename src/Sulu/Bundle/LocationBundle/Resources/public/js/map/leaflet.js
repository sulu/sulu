define(['leaflet'], function (leaflet) {
    var leaflet = leaflet;

    /**
     * Leaflet map class
     * @param selector - Put the map in the DOM element with this ID
     * @param options - Options for the map
     */
    return function Leaflet(selector, options) {
        var selector = selector;
        var map = leaflet.map(selector);
        var marker = null;

        /**
         * Show a position on the map
         * @param long - Longitude
         * @param lat - Latitude
         * @param zoom - Zoom
         */
        this.show = function (long, lat, zoom) {

            map.setView([lat, long], zoom)

            var MarkerIcon = L.Icon.Default.extend({
                options: {
                    iconUrl: '../vendor/leaflet/images/marker-icon.png',
                    shadowUrl: '../vendor/leaflet/images/marker-shadow.png'
                }
            });

            // add an OpenStreetMap tile layer
            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            if (null == marker) {
                marker = L.marker([lat, long], {
                    icon: new MarkerIcon
                });
                marker.addTo(map)
            } else {
                marker.setLatLng([lat, long]);
            }
        };
    };
});
