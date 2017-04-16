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
        "type/smartContent": '../../sulucontent/js/validation/types/smartContent',
        "type/internalLinks": '../../sulucontent/js/validation/types/internalLinks',
        "type/singleInternalLink": '../../sulucontent/js/validation/types/singleInternalLink',
        "type/block": '../../sulucontent/js/validation/types/block'
    }
});

define({

    name: "Sulu Content Bundle",

    initialize: function(app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('sulucontent', '/bundles/sulucontent/js/components');

        function getContentLanguage() {
            return sandbox.sulu.getUserSetting('contentLanguage') || sandbox.sulu.user.locale;
        }

        // redirects to list with specific language
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace',
            callback: function(webspace) {
                var language = getContentLanguage();
                sandbox.emit('sulu.router.navigate', 'content/contents/' + webspace + '/' + language);
            }
        });

        // list all contents for a language
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace/:language',
            callback: function(webspace, language) {
                this.html('<div data-aura-component="content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-display="column" data-aura-preview="false"/>');
            }
        });

        // show form for new content with a parent page
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace/:language/add::id/:content',
            callback: function(webspace, language, id, content) {
                this.html(
                    '<div data-aura-component="content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-content="' + content + '" data-aura-parent="' + id + '"/>'
                );
            }
        });

        // show form for new content
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace/:language/add/:content',
            callback: function(webspace, language, content) {
                this.html(
                    '<div data-aura-component="content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-content="' + content + '"/>'
                );
            }
        });

        // redirects to edit with specific language
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace/edit::id/:content',
            callback: function(webspace, id, content) {
                var language = getContentLanguage();
                sandbox.emit('sulu.router.navigate', 'content/contents/' + webspace + '/' + language + '/edit:' + id + '/' + content);
            }
        });

        // show form for editing a content
        sandbox.mvc.routes.push({
            route: 'content/contents/:webspace/:language/edit::id/:content',
            callback: function(webspace, language, id, content) {
                this.html(
                    '<div data-aura-component="content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-content="' + content + '" data-aura-id="' + id + '" data-aura-preview="true"/>'
                );
            }
        });
    }
});
