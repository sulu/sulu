require.config({
    paths: {
        jquery: 'vendor/jquery/jquery',
        underscore: 'vendor/underscore/underscore',
        backbone: 'vendor/backbone/backbone'
    }
});

require(['app'], function(App) {
    App.initialize();
});