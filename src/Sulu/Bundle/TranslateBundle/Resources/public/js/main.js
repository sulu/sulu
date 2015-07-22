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
        sulutranslate: '../../sulutranslate/js'
    }
});

define({

    name: 'Sulu Translate Bundle',

    initialize: function(app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('sulutranslate', '/bundles/sulutranslate/js/components');

        // list all translation packages
        sandbox.mvc.routes.push({
                route: 'settings/translate',
                callback: function() {
                    return '<div data-aura-component="packages@sulutranslate" data-aura-display="list" data-aura-display="settings"/>';
                }
            }
        );

        // show form for new translation package
        sandbox.mvc.routes.push({
                route: 'settings/translate/add',
                callback: function() {
                    return '<div data-aura-component="packages/components/content@sulutranslate" data-aura-display="settings" data-aura-startComponent="packages@sulutranslate" />';
                }
            }
        );

        // show form for editing a translation package
        sandbox.mvc.routes.push({
                route: 'settings/translate/edit::id/:content',
                callback: function(id, content) {
                    return '<div data-aura-component="packages/components/content@sulutranslate" data-aura-display="' + content + '" data-aura-id="' + id + '"/>';
                }
            }
        );

        // show form for editing codes for catalogue
        sandbox.mvc.routes.push({
                route: 'settings/translate/edit::id/details::catalogueId',
                callback: function(id, catalogueId) {
                    return '<div data-aura-component="packages/components/content@sulutranslate" data-aura-display="details" data-aura-id="' + id + '" data-aura-catalogue="' + catalogueId + '"/>';
                }
            }
        );
    }
});
