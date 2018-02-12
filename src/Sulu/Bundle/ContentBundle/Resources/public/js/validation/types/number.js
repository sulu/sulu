/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

define([
    'type/default',
], function(Default) {

    'use strict';

    return function($el, options) {
        var defaults = {
                regExp: /^([\d]*\.?[\d]*)$/
            },

            typeInterface = {
                setValue: function(data) {
                    this.$el.data({
                        value: data
                    }).trigger('data-changed');
                },

                getValue: function() {
                    return this.$el.find('input').val();
                },

                validate: function() {
                    var val = this.getValue();
                    if (val === '') {
                        return true;
                    }

                    return this.options.regExp.test(val);
                },

                needsValidation: function() {
                    var val = this.getValue();
                    return val !== '';
                }
            };

        return new Default($el, defaults, options, 'number', typeInterface);
    };
});