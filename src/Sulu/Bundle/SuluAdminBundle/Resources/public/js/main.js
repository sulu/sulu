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
        'app-config': 'components/app-config/main',
        'cultures': 'vendor/globalize/cultures'
    }
});

require(['husky', 'app-config'], function(Husky, AppConfig) {

    'use strict';

    var language = AppConfig.getUser().locale,
        app;

    require(['text!/admin/bundles', 'text!/js/translations/sulu.' + language + '.json'], function(text, messagesText) {
        var bundles = JSON.parse(text),
            messages = JSON.parse(messagesText);

        app = new Husky({
            debug: {
                enable: true
            },
            culture: {
                name: language,
                messages: messages
            }
        });


        bundles.forEach(function(bundle) {
            app.use('/bundles/' + bundle + '/js/main.js');
        }.bind(this));

        app.use('aura_extensions/backbone-relational');
        app.use('aura_extensions/sulu-content-tabs');
        app.use('aura_extensions/sulu-extension');

        app.components.addSource('suluadmin', '/bundles/suluadmin/js/components');

        app.use(function(app) {
            window.App = app.sandboxes.create('app-sandbox');
        });

        app.start();
    });
});
