(function() {

    'use strict';

    require.config({
        paths: {
            'vendor/wookmark':'../../sulumedia/js/vendor/wookmark/wookmark',
        },
        shim: {
            'vendor/wookmark': ['jquery']
        }
    })

    define(['vendor/wookmark'], function(Wookmark) {

        return {

            name: 'masonry',

            initialize: function(app) {
                // overwrite wookmark onRefresh function to addItems on function call
                Wookmark.prototype.onRefresh = function () {
                    this.initItems();
                    this.itemHeightsDirty = true;
                    this.layout();
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
