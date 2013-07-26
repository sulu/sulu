require.config({
    paths: {
        jquery: 'vendor/jquery/jquery',
        underscore: 'vendor/underscore/underscore',
        backbone: 'vendor/backbone/backbone',
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
        'husky': {
            'deps': ['jquery']
        }
    }
});

require(['app'], function(App) {
    App.initialize();
});