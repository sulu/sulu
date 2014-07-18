define(['leaflet'], function (leaflet) {
    var leaflet = leaflet;
    return {
        show: function (selector, long, lat, zoom) {
            var map = leaflet.map(selector).setView([long, lat], zoom);

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

            L.marker([long, lat], {
                icon: new MarkerIcon
            }).addTo(map)
                .bindPopup('A pretty CSS3 popup. <br> Easily customizable.')
                .openPopup();
            }
    };
});
