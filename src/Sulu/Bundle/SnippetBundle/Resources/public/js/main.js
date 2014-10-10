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
        sulusnippet: '../../sulusnippet/js'
    }
});

define({

    name: "Sulu Snippet Bundle",

    initialize: function(app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('sulusnippet', '/bundles/sulusnippet/js/components');

        function getContentLanguage() {
            // FIXME do not use user locale as default use default language of system instead
            return sandbox.sulu.getUserSetting('contentLanguage') || sandbox.sulu.user.locale;
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
                this.html('<div data-aura-component="snippet/list@sulusnippet" data-aura-language="' + language + '" data-aura-display="list"/>');
            }
        });

        // edit form
        sandbox.mvc.routes.push({
            route: 'snippet/snippets/:language/edit::id',
            callback: function(language, id) {
                this.html('<div data-aura-component="snippet/form@sulusnippet" data-aura-language="' + language + '" data-aura-id="' + id + '"/>');
            }
        });

        // add form
        sandbox.mvc.routes.push({
            route: 'snippet/snippets/:language/add',
            callback: function(language) {
                this.html('<div data-aura-component="snippet/form@sulusnippet" data-aura-language="' + language + '"/>');
            }
        });
    }
});
