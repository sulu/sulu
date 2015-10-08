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

        'main': 'main',

        'app-config': 'components/app-config/main',
        'config': 'components/config/main',
        'widget-groups': 'components/sidebar/widget-groups',
        'cultures': 'vendor/globalize/cultures',
        'husky': 'vendor/husky/husky',
        'aura_extensions/backbone-relational': 'aura_extensions/backbone-relational',
        'aura_extensions/sulu-content': 'aura_extensions/sulu-content',
        'aura_extensions/sulu-extension': 'aura_extensions/sulu-extension',
        'aura_extensions/sulu-buttons': 'aura_extensions/sulu-buttons',
        'aura_extensions/url-manager': 'aura_extensions/url-manager',

        '__component__$app@suluadmin': 'components/app/main',
        '__component__$overlay@suluadmin': 'components/overlay/main',
        '__component__$header@suluadmin': 'components/header/main',
        '__component__$list-toolbar@suluadmin': 'components/list-toolbar/main',
        '__component__$labels@suluadmin': 'components/labels/main',
        '__component__$sidebar@suluadmin': 'components/sidebar/main',
        '__component__$data-overlay@suluadmin': 'components/data-overlay/main'
    },
    include: [
        'vendor/require-css/css',
        'app-config',
        'config',
        'aura_extensions/backbone-relational',
        'aura_extensions/sulu-content',
        'aura_extensions/sulu-extension',
        'aura_extensions/sulu-buttons',
        'aura_extensions/url-manager',
        'widget-groups',

        '__component__$app@suluadmin',
        '__component__$overlay@suluadmin',
        '__component__$header@suluadmin',
        '__component__$list-toolbar@suluadmin',
        '__component__$labels@suluadmin',
        '__component__$sidebar@suluadmin',
        '__component__$data-overlay@suluadmin'
    ],
    exclude: [
        'husky'
    ],
    map: {
        '*': {
            'css': 'vendor/require-css/css'
        }
    }
});

define('underscore', function() {
    return window._;
});

require(['husky', 'app-config'], function(Husky, AppConfig) {

    'use strict';

    var locale = AppConfig.getUser().locale,
        translations = AppConfig.getTranslations(),
        fallbackLocale = AppConfig.getFallbackLocale(),
        app;

    if (translations.indexOf(locale) === -1) {
        locale = fallbackLocale;
    }

    require(['text!/admin/bundles', 'text!/admin/translations/sulu.' + locale + '.json'], function(text, messagesText) {
        var bundles = JSON.parse(text),
            messages = JSON.parse(messagesText);

        app = new Husky({
            debug: {
                enable: AppConfig.getDebug()
            },
            culture: {
                name: locale,
                messages: messages
            }
        });

        app.use('aura_extensions/url-manager');
        app.use('aura_extensions/backbone-relational');
        app.use('aura_extensions/sulu-content');
        app.use('aura_extensions/sulu-extension');
        app.use('aura_extensions/sulu-buttons');
        app.use('aura_extensions/default-extension');
        app.use('aura_extensions/event-extension');

        bundles.forEach(function(bundle) {
            app.use('/bundles/' + bundle + '/js/main.js');
        }.bind(this));

        app.components.addSource('suluadmin', '/bundles/suluadmin/js/components');

        app.use(function(app) {
            window.App = app.sandboxes.create('app-sandbox');
        });

        app.start();

        window.app = app;
    });
});
