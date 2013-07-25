require.config({
    paths: {
        jquery: 'vendor/jquery/jquery',
        underscore: 'vendor/underscore/underscore',
        backbone: 'vendor/backbone/backbone',
        husky: 'vendor/husky/husky'
    }
});

require(['app'], function(App) {
    App.initialize();
});