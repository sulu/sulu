(function() {

    'use strict';

    require.config({
        paths: {
            'vendor/wookmark':'../../sulumedia/js/vendor/wookmark/wookmark',
        }
    })

    define(['vendor/wookmark'], function(Wookmark) {

        return {

            name: 'masonry',

            initialize: function(app) {
                // overwrite onRefresh prototype method of wookmark

                app.sandbox.masonry = {
                    initialize: function(selector, configs) {
                        var v = new Wookmark(selector, configs);
                    },

                    refresh: function(selector, newItems){
                        // add new items and refresh layout
                    }
                };
            }
        };
    });
})();
