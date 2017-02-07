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
    'config',
    'type/default'
], function(Config, Default) {

    'use strict';

    return function($el, options) {
        var defaults = {},

            fullValidator = /^(\/[a-z0-9][a-z0-9-_]*)+$/,
            leafValidator = /^[a-z0-9][a-z0-9-_]*$/,

            getInputType = function() {
                if (!!this.options.inputType) {
                    return this.options.inputType;
                }

                return Config.get('sulu_content.webspace_input_types')[this.options.webspaceKey];
            },

            subType = {
                setValue: function(value) {
                    App.dom.data($el, 'value', value).trigger('data-changed');
                },

                getValue: function() {
                    return App.dom.data($el, 'value');
                },

                needsValidation: function() {
                    return this.$el.find('input').length > 0;
                },

                validate: function() {
                    if (!this.needsValidation()) {
                        return true;
                    }

                    var val = this.getValue(),
                        part = App.dom.data($el, 'part');

                    if (getInputType.call(this) === 'leaf' && !leafValidator.test(part)) {
                        return false;
                    }

                    return part.length > 0 && val !== '/' && fullValidator.test(val);
                }
            };

        return new Default($el, defaults, options, 'resourceLocator', subType);
    };
});
