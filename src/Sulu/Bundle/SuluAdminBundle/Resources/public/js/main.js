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
        text: 'vendor/requirejs/text',
        jquery: 'vendor/jquery/jquery',
        underscore: 'vendor/underscore/underscore',
        backbone: 'vendor/backbone/backbone',
        backbonerelational: 'vendor/backbone/backbone-relational',
        husky: 'vendor/husky/husky'
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
        }
    }
});

require(['app'], function(App) {

    'use strict';

    App.initialize();
});