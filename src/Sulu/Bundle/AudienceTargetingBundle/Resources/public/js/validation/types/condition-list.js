define([
    'type/default'
], function(Default) {

    'use strict';

    return function($el, options) {
        var defaults = {},

            typeInterface = {
                setValue: function(condition) {

                },

                getValue: function() {
                    var rows = App.dom.find('.condition-row', $el),
                        value = [];

                     rows.each(function() {
                        var $row = $(this),
                            id = $row.find('[data-condition-id]').val();

                        value.push({
                            id: id || null,
                            type: $row.find('[data-condition-type]').data('selection')[0],
                            condition: {
                                locale: $row.find('[data-condition-name="locale"]').val()
                            }
                        });
                    });

                    return value;
                },

                needsValidation: function() {
                    return false;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'conditionList', typeInterface);
    };
});
