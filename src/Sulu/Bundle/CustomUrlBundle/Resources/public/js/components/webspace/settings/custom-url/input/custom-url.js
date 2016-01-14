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
    'type/default',
    'form/util'
], function(Default) {

    'use strict';

    return function($el, options) {
        var defaults = {},

            typeInterface = {
                setValue: function(data) {
                    this.$el.data('custom-url-data', data);
                    this.$el.trigger('data-changed');
                },

                getValue: function() {
                    return this.$el.data('custom-url-data');
                },

                needsValidation: function() {
                    // TODO validation
                    return false;
                },

                validate: function() {
                    // TODO validation
                    return true;
                }
            };

        return new Default($el, defaults, options, 'custom-url', typeInterface);
    };
});
