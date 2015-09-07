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
        sulutag: '../../sulutag/js',
        "type/tagList": '../../sulutag/js/validation/types/tagList'
    }
});

define(['config'], function(Config) {

    'use strict';

    return {

        name: "SuluTagBundle",

        initialize: function(app) {

            var sandbox = app.sandbox;

            Config.set('suluresource.filters.type.tags', {
                routeToList: 'settings/tags'
            });

            app.components.addSource('sulutag', '/bundles/sulutag/js/components');

            sandbox.mvc.routes.push({
                route: 'settings/tags',
                callback: function() {
                    return '<div data-aura-component="tags@sulutag" data-aura-display="list"/>';
                }
            });
        }
    };
});
