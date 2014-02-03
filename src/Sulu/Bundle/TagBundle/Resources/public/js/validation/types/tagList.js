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

            },

            typeInterface = {
                setValue: function(value) {
                    App.emit('husky.auto-complete-list.' + this.options.instanceName + '.set-tags', value);
                },

                getValue: function() {
                    return App.dom.data($el, 'tags');
                },

                needsValidation: function() {
                    return false;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'tagList', typeInterface);
    };
});
