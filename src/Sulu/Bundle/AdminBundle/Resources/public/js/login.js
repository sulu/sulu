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

        'main': 'login',

        'cultures': 'vendor/globalize/cultures',
        'aura_extensions/backbone-relational': 'aura_extensions/backbone-relational',
        'husky': 'vendor/husky/husky',
        '__component__$login@suluadmin': 'components/login/main'
    },
    include: [
        'aura_extensions/backbone-relational',
        '__component__$login@suluadmin'
    ],
    exclude: [
        'husky'
    ],
    urlArgs: 'v=develop'
});

define('underscore', function() {
    return window._;
});

require(['husky'], function(Husky) {

    'use strict';

    var locale,
        translations = SULU.translations,
        fallbackLocale = SULU.fallbackLocale;

    // detect browser locale (ie, ff, chrome fallbacks)
    locale = window.navigator.languages ? window.navigator.languages[0] : null;
    locale = locale || window.navigator.language || window.navigator.browserLanguage || window.navigator.userLanguage;

    // select only language
    locale = locale.slice(0, 2).toLowerCase();
    if (translations.indexOf(locale) === -1) {
        locale = fallbackLocale;
    }

    require([
        'text!/admin/translations/sulu.' + locale + '.json',
        'text!/admin/translations/sulu.' + fallbackLocale + '.json'
    ], function(messagesText, defaultMessagesText) {
        var messages = JSON.parse(messagesText),
            defaultMessages = JSON.parse(defaultMessagesText);

        var app = new Husky({
            debug: {
                enable: !!SULU.debug
            },
            culture: {
                name: locale,
                messages: messages,
                defaultMessages: defaultMessages
            }
        });

        app.use('aura_extensions/backbone-relational');
        app.components.addSource('suluadmin', '/bundles/suluadmin/js/components');
        app.start();
        window.app = app;
    });
});
