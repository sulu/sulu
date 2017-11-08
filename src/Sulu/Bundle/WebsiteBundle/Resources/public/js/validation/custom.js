/*
 * This file is part of the Husky Validation.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

define([
    'type/default'
], function(Default) {

    'use strict';

    return function($el, options) {
        var defaults = {
                instanceName: null
            },

            subType = {
                setValue: function(value) {
                    if (value === null) {
                        value = {};
                    }

                    for (var prop in value) {
                        if (!value.hasOwnProperty(prop)) {
                            continue;
                        }

                        this.$el.find('#analytics-content').val(value[prop] || '');

                        this.$el.find('#analytics-position').data({
                            'selection': prop
                        }).trigger('data-changed');
                    }
                },

                getValue: function() {
                    var content = this.$el.find('#analytics-content').val();
                    var position = this.$el.find('#analytics-position').data('selection');

                    if (!content || !position) {
                        return null;
                    }

                    var returnValue = {};
                    returnValue[position[0]] = content;

                    return returnValue;
                },

                needsValidation: function() {
                    return true;
                },

                validate: function() {
                    return !!this.getValue();
                }
            };

        return new Default($el, defaults, options, 'custom', subType);
    };
});
