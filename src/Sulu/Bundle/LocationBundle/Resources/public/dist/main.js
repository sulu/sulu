require.config({paths:{sululocation:"../../sululocation/dist",sululocationcss:"../../sululocation/css","type/location":"../../sululocation/dist/validation/types/location","map/leaflet":"../../sululocation/dist/map/leaflet","map/google":"../../sululocation/dist/map/google",leaflet:"../../sululocation/dist/vendor/leaflet/leaflet",async:"../../sululocation/dist/vendor/requirejs-plugins/async"}}),define(["css!sululocationcss/main","css!sululocation/vendor/leaflet/leaflet.css"],function(){return{name:"SuluLocationBundle",initialize:function(a){"use strict";a.sandbox;a.components.addSource("sululocation","/bundles/sululocation/dist/components")}}});