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
        sulucontent: '../../sulucontent/js',
        "type/resourceLocator": '../../sulucontent/js/validation/types/resourceLocator',
        "type/textEditor": '../../sulucontent/js/validation/types/textEditor',
        "type/smartContent": '../../sulucontent/js/validation/types/smartContent'
    }
});

define({

    name: "Sulu Content Bundle",

    initialize: function(app) {
        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('sulucontent', '/bundles/sulucontent/js/components');

        // list all contacts
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace/:language',
            callback: function(webspace, language) {
                this.html('<div data-aura-component="content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-display="column"/>');
            }
        });

        // show form for new content
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace/:language/add::id/:content',
            callback: function(webspace, language, id, content) {
                this.html(
                    '<div data-aura-component="content/components/content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-content="' + content + '" data-aura-parent="' + id + '"/>'
                );
            }
        });

        // show form for new content
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace/:language/add/:content',
            callback: function(webspace, language, content) {
                this.html(
                    '<div data-aura-component="content/components/content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-content="' + content + '"/>'
                );
            }
        });

        // show form for editing a content
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace/:language/edit::id/:content',
            callback: function(webspace, language, id, content) {
                this.html(
                    '<div data-aura-component="content/components/content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-content="' + content + '" data-aura-id="' + id + '"/>'
                );
            }
        });
    }
});
