/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    waitSeconds: 0,
    paths: {
        suluadmin: '../../suluadmin/js',
        'cultures': 'vendor/globalize/cultures',
        'husky': 'vendor/husky/husky',
        'aura_extensions/backbone-relational': 'aura_extensions/backbone-relational'
    },
    include: [
        'aura_extensions/backbone-relational'
    ],
    exclude: [
        'husky'
    ]
});

require(['husky'], function(Husky) {

    'use strict';

    var language = (!!window.navigator.language && window.navigator.language.indexOf('de') >= 0) ? 'de' : 'en';

    require(['text!/admin/translations/sulu.' + language + '.json'], function(messagesText) {
        var messages = JSON.parse(messagesText),

        app = new Husky({
            debug: {
                enable: true
            },
            culture: {
                name: language,
                messages: messages
            }
        });

        app.use('aura_extensions/backbone-relational');
        app.components.addSource('suluadmin', '/bundles/suluadmin/js/components');
        app.start();
        window.app = app;
    });
});
