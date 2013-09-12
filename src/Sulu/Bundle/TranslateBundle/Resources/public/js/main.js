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

    name: "Sulu Translate Bundle",

    initialize: function (app) {
        var sandbox = app.sandbox;

        app.components.addSource('sulutranslate', '/bundles/sulutranslate/js/aura_components');

        // list all translation packages
        sandbox.mvc.routes.push({
                route: 'settings/translate',
                components: [
                    {
                        name: 'package/list@sulutranslate',
                        options: { el: '#content' }
                    }
                ]
            }
        );

        // show form for new translation package
        sandbox.mvc.routes.push({
                route: 'settings/translate/add',
                components: [
                    {
                        name: 'package/form@sulutranslate',
                        options: { el: '#content' }
                    }
                ]
            }
        );

        // show form for editing a translation package
        sandbox.mvc.routes.push({
                route: 'settings/translate/edit::id/settings',
                components: [
                    {
                        name: 'package/form@sulutranslate',
                        options: { el: '#content' }
                    }
                ]
            }
        );

        // show form for editing codes
        sandbox.mvc.routes.push({
                route: 'settings/translate/edit::id/details',
                components: [
                    {
                        name: 'translation/form@sulutranslate',
                        options: { el: '#content' }
                    }
                ]
            }
        );


    }

});
