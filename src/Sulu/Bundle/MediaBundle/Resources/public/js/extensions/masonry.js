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
                app.sandbox.masonry = {
                    initialize: function(selector, configs) {

                    }
                }
            }
        };
    });
})();
