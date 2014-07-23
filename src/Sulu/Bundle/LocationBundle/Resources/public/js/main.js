/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        sululocation: '../../sululocation/js',
        "type/location": '../../sululocation/js/validation/types/location',
        "map/leaflet": '../../sululocation/js/map/leaflet',
        "leaflet": '../../sululocation/js/vendor/leaflet/leaflet'
    }
});

define({

    name: "SuluLocationBundle",

    initialize: function(app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('sululocation', '/bundles/sululocation/js/components');
        // Example: list all contacts
        // sandbox.mvc.routes.push({
        //     route: 'contacts/contacts',
        //    callback: function(){
        //         this.html('<div data-aura-component="contacts@sulucontact" data-aura-display="list"/>');
        //     }
        // });
    }
});
