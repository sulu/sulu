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
        suluresource: '../../suluresource/js'
    }
});

define({

    name: "SuluResourceBundle",

    initialize: function(app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('suluresource', '/bundles/suluresource/js/components');

        //flat list of attributes
        sandbox.mvc.routes.push({
            route: 'resource/filters',
            callback: function() {
                this.html('<div data-aura-component="filters@suluresource" data-aura-display="list"/>');
            }
        });

        sandbox.mvc.routes.push({
            route: 'resource/filters/:locale/add',
            callback: function(locale) {
                this.html('<div data-aura-component="filters@suluresource" data-aura-display="form" data-aura-locale="' + locale + '"/>');
            }
        });

        sandbox.mvc.routes.push({
            route: 'resource/filters/:locale/edit::id/:details',
            callback: function(locale, id) {
                this.html('<div data-aura-component="filters@suluresource" data-aura-display="form" data-aura-locale="' + locale + '" data-aura-id="' + id + '"/>');
            }
        });
    }
});
