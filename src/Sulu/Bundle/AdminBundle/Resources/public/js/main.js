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

        'app-config': 'components/app-config/main',
        'cultures': 'vendor/globalize/cultures',
        'husky': 'vendor/husky/husky',
        'aura_extensions/backbone-relational': 'aura_extensions/backbone-relational',
        'aura_extensions/sulu-content-tabs': 'aura_extensions/sulu-content-tabs',
        'aura_extensions/sulu-extension': 'aura_extensions/sulu-extension',
        'aura_extensions/iban': 'aura_extensions/iban',

        'vendor/iban-converter':'vendor/iban-converter/iban',
        'type/iban-input': 'components/input-type/iban-input',

        '__component__$app@suluadmin': 'components/app/main',
        '__component__$content-tabs@suluadmin': 'components/content-tabs/main',
        '__component__$overlay@suluadmin': 'components/overlay/main',
        '__component__$header@suluadmin': 'components/header/main',
        '__component__$list-toolbar@suluadmin': 'components/list-toolbar/main',
        '__component__$labels@suluadmin': 'components/labels/main',
        '__component__$grid-group@suluadmin': 'components/grid-group/main',
        '__component__$sidebar@suluadmin': 'components/sidebar/main'
    },
    shim: {
        'vendor/iban-converter': {
            exports: 'IBAN'
        }
    },
    include: [
        'app-config',
        'aura_extensions/backbone-relational',
        'aura_extensions/sulu-content',
        'aura_extensions/sulu-extension',
        'aura_extensions/iban',

        'vendor/iban-converter',

        '__component__$app@suluadmin',
        '__component__$app@suluadmin',
        '__component__$content-tabs@suluadmin',
        '__component__$overlay@suluadmin',
        '__component__$header@suluadmin',
        '__component__$list-toolbar@suluadmin',
        '__component__$labels@suluadmin',
        '__component__$grid-group@suluadmin',
        '__component__$sidebar@suluadmin'
    ],
    exclude: [
        'husky'
    ]
});

require(['husky', 'app-config'], function(Husky, AppConfig) {

    'use strict';

    var language = AppConfig.getUser().locale,
        app;

    require(['text!/admin/bundles', 'text!/admin/translations/sulu.' + language + '.json'], function(text, messagesText) {
        var bundles = JSON.parse(text),
            messages = JSON.parse(messagesText);

        app = new Husky({
            debug: {
                enable: AppConfig.getDebug()
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
        app.use('aura_extensions/sulu-content');
        app.use('aura_extensions/sulu-extension');
        app.use('aura_extensions/iban');

        app.components.addSource('suluadmin', '/bundles/suluadmin/js/components');

        app.use(function(app) {
            window.App = app.sandboxes.create('app-sandbox');
        });

        app.start();

        window.app = app;
    });
});
