define([
    'type/default'
], function(Default) {

    'use strict';

    var getRows = function ($el) {
        return App.dom.find('.condition-row', $el);
    };

    return function($el, options) {
        var defaults = {},

            typeInterface = {
                setValue: function(conditions) {
                    // this is handled in the component itself
                },

                getValue: function() {
                    var rows = getRows($el),
                        value = [];

                    rows.each(function() {
                        var $row = $(this),
                            id = $row.find('[data-condition-id]').val(),
                            type = $row.find('[data-condition-type]').data('selection')[0],
                            condition = {};

                        if (!type) {
                            return;
                        }

                        $row.find('[data-condition-name]').each(function(index, element) {
                            var $element = $(element), fieldValue = null;

                            if ($element.data('selection') && $element.data('selection').length > 0) {
                                fieldValue = $element.data('selection')[0];
                            } else {
                                fieldValue = $element.data('singleInternalLink') || $element.val();
                            }
                            condition[$element.attr('data-condition-name')] = fieldValue;
                        });

                        value.push({
                            id: id || null,
                            type: type,
                            condition: condition
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
