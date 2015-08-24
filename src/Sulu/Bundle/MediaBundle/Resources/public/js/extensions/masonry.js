(function() {

    'use strict';

    require.config({
        paths: {
            'vendor/wookmark':'../../sulumedia/js/vendor/wookmark/wookmark',
        },
        shim: {
            'vendor/wookmark': ['jquery']
        }
    });

    // Define the window and document modules so they are available for the Wookmark plugin
    define('window', function () {
        return window;
    });

    define('document', function () {
        return document;
    });

    define(['vendor/wookmark'], function(Wookmark) {

        return {

            name: 'masonry',

            initialize: function(app) {
                // extend wookmark onRefresh function to automatically add new items
                var baseMethod = Wookmark.prototype.onRefresh;
                Wookmark.prototype.onRefresh = function () {
                    this.initItems();
                    baseMethod();
                };

                app.sandbox.masonry = {
                    initialize: function(selector, options) {
                        $(selector).wookmark(options);
                    },

                    refresh: function(selector){
                        $(selector).trigger('refreshWookmark');
                    }
                };
            }
        };
    });
})();
