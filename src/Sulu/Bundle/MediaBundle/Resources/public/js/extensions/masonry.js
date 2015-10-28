(function() {

    'use strict';

    require.config({
        paths: {
            'vendor/wookmark': '../../sulumedia/js/vendor/wookmark/wookmark',
        },
        shim: {
            'vendor/wookmark': ['jquery']
        }
    });

    // Define window and document module which are needed by wookmark
    define('window', function() {
        return window;
    });

    define('document', function() {
        return document;
    });

    define(['vendor/wookmark'], function(Wookmark) {
        var dataKey = 'wookmark-instance';

        return {

            name: 'masonry',

            initialize: function(app) {
                app.sandbox.masonry = {
                    initialize: function(selector, options) {
                        var wookmark = new Wookmark(selector, options);
                        $(selector).data(dataKey, wookmark);
                    },

                    refresh: function(selector, scanItems) {
                        var wookmark = $(selector).data(dataKey);

                        if (!!wookmark) {
                            if (!!scanItems) {
                                wookmark.initItems();
                            }
                            wookmark.layout(true);
                        }
                    },

                    updateFilterClasses: function(selector) {
                        var wookmark = $(selector).data(dataKey);

                        if (!!wookmark) {
                            wookmark.updateFilterClasses();
                        }
                    },

                    filter: function(selector, filter, operator) {
                        var wookmark = $(selector).data(dataKey);

                        if (!!wookmark) {
                            wookmark.filter(filter, operator || 'or');
                        }
                    },

                    destroy: function(selector) {
                        var wookmark = $(selector).data(dataKey);

                        if (!!wookmark) {
                            $(selector).removeData(dataKey);
                            wookmark.clear();
                        }
                    }
                };
            }
        };
    });
})();
