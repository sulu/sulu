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
        sulusnippet: '../../sulusnippet/js',

        "type/snippet": '../../sulusnippet/js/validation/type/snippet'
    }
});

define(['config'], function(Config) {
    return {

        name: "Sulu Snippet Bundle",

        initialize: function(app) {

            'use strict';

            var sandbox = app.sandbox;

            app.components.addSource('sulusnippet', '/bundles/sulusnippet/js/components');

            sandbox.urlManager.setUrl('snippet', 'snippet/snippets/<%= languageCode %>/edit:<%= id %>');

            /**
             * Returns the language of the snippets, which is displayed at the beginning.
             * At first the function looks for the last used locale over all webspaces. If
             * nothing has been found, the first locale gets used.
             *
             * @returns {String} The locale of the snippets
             */
            function getContentLanguage() {
                // last visited language over all webspaces
                var language = sandbox.sulu.getUserSetting('contentLanguage'), webspace, locales, locale;
                if (!!language) {
                    return language;
                }

                // otherwise the very first found locale is used
                locales = Config.get('sulu-content').locales;
                for (webspace in locales) {
                    if(!locales.hasOwnProperty(webspace)) {
                        continue;
                    }

                    for (locale in locales[webspace]) {
                        if(!locales[webspace].hasOwnProperty(locale)) {
                            continue;
                        }

                        if (!!locales[webspace][locale]) {
                            return locales[webspace][locale].localization;
                        }
                    }
                }
            }

            // list all contents for a language
            sandbox.mvc.routes.push({
                route: 'snippet/snippets',
                callback: function() {
                    var language = getContentLanguage();
                    sandbox.emit('sulu.router.navigate', 'snippet/snippets/' + language);
                }
            });

            // list all snippets for a language
            sandbox.mvc.routes.push({
                route: 'snippet/snippets/:language',
                callback: function(language) {
                    return '<div data-aura-component="snippet/list@sulusnippet" data-aura-language="' + language + '" data-aura-display="list"/>';
                }
            });

            // edit form
            sandbox.mvc.routes.push({
                route: 'snippet/snippets/:language/edit::id',
                callback: function(language, id) {
                    return '<div data-aura-component="snippet/form@sulusnippet" data-aura-language="' + language + '" data-aura-id="' + id + '"/>';
                }
            });

            // add form
            sandbox.mvc.routes.push({
                route: 'snippet/snippets/:language/add',
                callback: function(language) {
                    return '<div data-aura-component="snippet/form@sulusnippet" data-aura-language="' + language + '"/>';
                }
            });
        }
    };
});
