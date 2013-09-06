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
        text: 'vendor/requirejs-text/text',
        jquery: 'vendor/jquery/jquery',
        underscore: 'vendor/underscore/underscore',
        backbone: 'vendor/backbone/backbone',
        backbonerelational: 'vendor/backbone-relational/backbone-relational',
        husky: 'vendor/husky/dist/husky',
        parsley: 'vendor/parsleyjs/parsley'
    },
    shim: {
        'underscore': {
            exports: '_'
        },
        'backbone': {
            deps: ['underscore', 'jquery'],
            exports: 'Backbone'
        },
        'backbonerelational': {
            deps: ['backbone']
        },
        'husky': {
            deps: ['jquery']
        },
        'parsley': {
            deps: ['jquery']
        }
    }
});

require(['app'], function(App) {

    'use strict';

    App.initialize();
});
