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
        sulucontentcss: '../../sulucontent/css',

        "type/resourceLocator": '../../sulucontent/js/validation/types/resourceLocator',
        "type/textEditor": '../../sulucontent/js/validation/types/textEditor',
        "type/smartContent": '../../sulucontent/js/validation/types/smartContent',
        "type/internalLinks": '../../sulucontent/js/validation/types/internalLinks',
        "type/singleInternalLink": '../../sulucontent/js/validation/types/singleInternalLink',
        "type/block": '../../sulucontent/js/validation/types/block',
        "type/toggler": '../../sulucontent/js/validation/types/toggler',
        "extensions/sulu-buttons-contentbundle": '../../sulucontent/js/extensions/sulu-buttons',
        "extensions/seo-tab": '../../sulucontent/js/extensions/seo-tab',
        "extensions/excerpt-tab": '../../sulucontent/js/extensions/excerpt-tab'
    }
});

define([
    'config',
    'extensions/sulu-buttons-contentbundle',
    'extensions/seo-tab',
    'extensions/excerpt-tab',
    'sulucontent/ckeditor/internal-link',
    'css!sulucontentcss/main'
], function(Config, ContentButtons, SeoTab, ExcerptTab, InternalLinkPlugin) {
    return {

        name: "Sulu Content Bundle",

        initialize: function(app) {

            'use strict';

            SeoTab.initialize(app);
            ExcerptTab.initialize(app);

            var sandbox = app.sandbox;
            var ckeditorConfig = Config.get('sulu_content.texteditor_toolbar');

            sandbox.sulu.buttons.push(ContentButtons.getButtons());
            
            app.components.addSource('sulucontent', '/bundles/sulucontent/js/components');

            Config.set('sulusearch.page.options', {
                image: false
            });

            sandbox.urlManager.setUrl(
                'page',
                function(data) {
                    return 'content/contents/<%= webspace %>/<%= locale %>/edit:<%= id %>/content';
                },
                function(data) {
                    return {
                        id: data.id,
                        webspace: data.properties.webspace_key,
                        url: data.url,
                        locale: data.locale
                    };
                },
                function(key) {
                    if (key.indexOf('page_') === 0) {
                        return 'page';
                    }
                }
            );

            /**
             * Returns the the language of the content which is displayed at the beginning.
             * It first look for the last chosen language for this webspace. If no such
             * language exists, the function looks for the last chosen language over all
             * webspaces and if that language is possible in with the given webspace. If
             * not language can be found, the default language is returned.
             *
             * @param {String} webspace The webspace key
             * @returns {string} The locale of the content
             */
            function getContentLanguage(webspace) {
                // webspace language
                var language = sandbox.sulu.getUserSetting(webspace + '.contentLanguage');
                if (!!language) {
                    return language;
                }

                // last visited language over all webspaces
                language = sandbox.sulu.getUserSetting('contentLanguage');
                if (!!language && !!Config.get('sulu-content').locales[webspace][language]) {
                    return language;
                }

                if (!Config.get('sulu-content').locales[webspace]
                    || Object.keys(Config.get('sulu-content').locales[webspace]).length === 0) {
                    app.logger.error('Webspace "' + webspace + '" has no defined locale');
                }

                // the default locale of a webspace is always on the first position
                language = Object.keys(Config.get('sulu-content').locales[webspace])[0];

                return Config.get('sulu-content').locales[webspace][language].localization;
            }

            // redirects to list with specific language
            sandbox.mvc.routes.push({
                route: 'content/contents/:webspace',
                callback: function(webspace) {
                    var language = getContentLanguage(webspace);
                    sandbox.emit('sulu.router.navigate', 'content/contents/' + webspace + '/' + language);
                }
            });

            // list all contents for a language
            sandbox.mvc.routes.push({
                route: 'content/contents/:webspace/:language',
                callback: function(webspace, language) {
                    return '<div data-aura-component="content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-display="column" data-aura-preview="false"/>';
                }
            });

            // show form for new content with a parent page
            sandbox.mvc.routes.push({
                route: 'content/contents/:webspace/:language/add::id/:content',
                callback: function(webspace, language, id, content) {
                    return '<div data-aura-component="content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-content="' + content + '" data-aura-parent="' + id + '"/>';
                }
            });

            // show form for new content
            sandbox.mvc.routes.push({
                route: 'content/contents/:webspace/:language/add/:content',
                callback: function(webspace, language, content) {
                    return '<div data-aura-component="content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-content="' + content + '"/>';
                }
            });

            // redirects to edit with specific language
            sandbox.mvc.routes.push({
                route: 'content/contents/:webspace/edit::id/:content',
                callback: function(webspace, id, content) {
                    var language = getContentLanguage(webspace);
                    sandbox.emit('sulu.router.navigate', 'content/contents/' + webspace + '/' + language + '/edit:' + id + '/' + content);
                }
            });

            // show form for editing a content
            sandbox.mvc.routes.push({
                route: 'content/contents/:webspace/:language/edit::id/:content',
                callback: function(webspace, language, id, content) {
                    return '<div data-aura-component="content@sulucontent" data-aura-webspace="' + webspace + '" data-aura-language="' + language + '" data-aura-content="' + content + '" data-aura-id="' + id + '" data-aura-preview="true"/>';
                }
            });

            // show webspace settings
            sandbox.mvc.routes.push({
                route: 'content/webspace/settings::id/:content',
                callback: function(id) {
                    return '<div data-aura-component="webspace/settings@sulucontent" data-aura-id="' + id + '" data-aura-webspace="' + id + '" />';
                }
            });

            // ckeditor
            sandbox.ckeditor.addPlugin(
                'internalLink',
                new InternalLinkPlugin(app.sandboxes.create('plugin-internal-link'))
            );
            sandbox.ckeditor.addToolbarButton('links', 'InternalLink', 'arrow-down');

            if (!!ckeditorConfig && !!ckeditorConfig.userToolbar) {
                sandbox.ckeditor.setToolbar(ckeditorConfig.userToolbar);
            }
        }
    };
});
