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
        'filtersutil/toolbarExtensionHandler': '../../suluresource/js/components/filters/util/toolbar-extension-handler',
        'filtersutil/filter': '../../suluresource/js/components/filters/util/filter'
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

            var sandbox = app.sandbox;

            app.components.addSource('suluresource', '/bundles/suluresource/js/components');

            // filter list view
            sandbox.mvc.routes.push({
                route: 'resource/filters/:context',
                callback: function(context) {
                    this.html('<div data-aura-component="filters@suluresource" data-aura-display="list" data-aura-context="' + context + '" />');
                }
            });

            // add a new filter
            sandbox.mvc.routes.push({
                route: 'resource/filters/:context/:locale/add',
                callback: function(context, locale) {
                    this.html('<div data-aura-component="filters@suluresource" data-aura-display="form" data-aura-context="' + context + '" data-aura-locale="' + locale + '"/>');
                }
            });

            // edit an existing filter
            sandbox.mvc.routes.push({
                route: 'resource/filters/:context/:locale/edit::id/:details',
                callback: function(context, locale, id) {
                    this.html('<div data-aura-component="filters@suluresource" data-aura-display="form" data-aura-context="' + context + '" data-aura-locale="' + locale + '" data-aura-id="' + id + '"/>');
                }
            });

            toolbarExtensionHandler.initialize(app);
        }
    };
});
