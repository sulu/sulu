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
        suluresource: '../../suluresource/js',
        'filtersutil/header': '../../suluresource/js/components/filters/util/header',
        'type/conditionSelection': '../../suluresource/js/components/condition-selection/condition-selection-type',
        'filtersutil/toolbarExtensionHandler': '../../suluresource/js/components/filters/util/toolbar-extension-handler'
    }
});

define(['filtersutil/toolbarExtensionHandler'], function(toolbarExtensionHandler) {

    'use strict';

    return {
        name: "SuluResourceBundle",

        /**
         * Initializes the routes for the resource bundle
         * @param app
         */
        initialize: function(app) {

            'use strict';

            var sandbox = app.sandbox;

            app.components.addSource('suluresource', '/bundles/suluresource/js/components');

            // filter list view
            sandbox.mvc.routes.push({
                route: 'resource/filters/:type',
                callback: function(type) {
                    this.html('<div data-aura-component="filters@suluresource" data-aura-display="list" data-aura-type="' + type + '"/>');
                }
            });

            // add a new filter
            sandbox.mvc.routes.push({
                route: 'resource/filters/:type/:locale/add',
                callback: function(type, locale) {
                    this.html('<div data-aura-component="filters@suluresource" data-aura-display="form" data-aura-type="' + type + '" data-aura-locale="' + locale + '"/>');
                }
            });

            // edit an existing filter
            sandbox.mvc.routes.push({
                route: 'resource/filters/:type/:locale/edit::id/:details',
                callback: function(type, locale, id) {
                    this.html('<div data-aura-component="filters@suluresource" data-aura-display="form" data-aura-type="' + type + '" data-aura-locale="' + locale + '" data-aura-id="' + id + '"/>');
                }
            });

            toolbarExtensionHandler.initialize(sandbox);
        }
    };
});
