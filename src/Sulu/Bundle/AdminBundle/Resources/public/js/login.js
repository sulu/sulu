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

    var browserLocale = window.navigator.language.slice(0, 2).toLowerCase(),
        language = 'en';
    // get the locale for the login
    for (var i = -1, length = SULU.locales.length; ++i < length;) {
        if (SULU.locales[i] === browserLocale) {
            language = SULU.locales[i];
            break;
        }
    }


    require(['text!/admin/translations/sulu.' + language + '.json'], function(messagesText) {
        var messages = JSON.parse(messagesText),

        app = new Husky({
            debug: {
                enable: !!SULU.debug
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
