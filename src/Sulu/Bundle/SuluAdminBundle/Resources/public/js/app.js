define(['jquery', 'underscore', 'backbone', 'husky'], function($, _, Backbone) {
    initialize = function() {
        $('#navigation').huskyNavigation({
            url: 'navigation'
        });
    };

    return {
        initialize: initialize
    }
});