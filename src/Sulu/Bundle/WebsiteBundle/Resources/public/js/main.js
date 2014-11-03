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
        suluwebsite: '../../suluwebsite/js'
    }
});

define({

    name: "SuluWebsiteBundle",

    initialize: function(app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('suluwebsite', '/bundles/suluwebsite/js/components');

        // cache clear button
        sandbox.mvc.routes.push({
            route: 'settings/cache',
            callback: function() {
                this.html('<div data-aura-component="cache@suluwebsite"/>');
            }
        });
    }
});
