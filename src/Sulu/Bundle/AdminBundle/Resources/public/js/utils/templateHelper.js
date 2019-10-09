define([], function() {
    'use strict';

    return {
        transformTemplateData: function(func) {
            return function(data) {
                return func({data: data});
            };
        }
    };
});
