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

        var defaults = {},

            typeInterface = {
                setValue: function(data) {
                    if (data === undefined || data === '' || data === null) {
                        return;
                    }

                    this.$el.data({
                        'conditionSelection': data
                    }).trigger('data-changed');
                },

                getValue: function() {
                    return this.$el.data('conditionSelection');
                },

                needsValidation: function() {
                    return false;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'condition-selection', typeInterface);
    };
});
