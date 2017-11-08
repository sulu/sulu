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
                setValue: function(content) {
                    if (content === null) {
                        content = {};
                    }

                    if (!content.hasOwnProperty('value') || !content.hasOwnProperty('position')) {
                        return;
                    }

                    this.$el.find('#analytics-content-value').val(content.value || '');

                    this.$el.find('#analytics-content-position').data({
                        'selection': content.position
                    }).trigger('data-changed');
                },

                getValue: function() {
                    var value = this.$el.find('#analytics-content-value').val();
                    var position = this.$el.find('#analytics-content-position').data('selection');

                    if (!value || !position) {
                        return null;
                    }

                    return {
                        position: position[0],
                        value: value
                    };
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
