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

                    this.$el.find('#analytics-content-url').val(value.url || '');
                    this.$el.find('#analytics-content-site-id').val(value.siteId || '');
                },

                getValue: function() {
                    return {
                        url: this.$el.find('#analytics-content-url').val(),
                        siteId: this.$el.find('#analytics-content-site-id').val()
                    }
                },

                needsValidation: function() {
                    return false;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'textEditor', subType);
    };
});
