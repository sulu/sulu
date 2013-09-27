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
        'cultures': 'vendor/globalize/cultures'
    }
});

require(['husky'], function(Husky) {

    'use strict';

    // TODO get language from user
    var language = 'de', app;

    require(['text!/admin/bundles', 'text!language/sulu.' + language + '.json'], function(text, messagesText) {
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

        app.components.addSource('suluadmin', '/bundles/suluadmin/js/components');

        app.start();
    });
});
