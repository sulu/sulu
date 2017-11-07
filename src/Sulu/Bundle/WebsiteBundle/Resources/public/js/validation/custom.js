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

                    this.$el.find('#analytics-content-head-open').val(value.headOpen || '');
                    this.$el.find('#analytics-content-head-close').val(value.headClose || '');
                    this.$el.find('#analytics-content-body-open').val(value.bodyOpen || '');
                    this.$el.find('#analytics-content-body-close').val(value.bodyClose || '');
                },

                getValue: function() {
                    return {
                        headOpen: this.$el.find('#analytics-content-head-open').val(),
                        headClose: this.$el.find('#analytics-content-head-close').val(),
                        bodyOpen: this.$el.find('#analytics-content-body-open').val(),
                        bodyClose: this.$el.find('#analytics-content-body-close').val()
                    };
                },

                needsValidation: function() {
                    return true;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'custom', subType);
    };
});
