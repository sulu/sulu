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

            dataChangedHandler = function(data) {
                App.emit('sulu.preview.update', $el, data);
                App.emit('sulu.content.changed');
            },

            subType = {
                initializeSub: function() {
                    var dataChangedEvent = 'husky.toggler.' + options.instanceName + '.changed';

                    App.off(dataChangedEvent, dataChangedHandler);
                    App.on(dataChangedEvent, dataChangedHandler);
                },

                setValue: function(value) {
                    App.dom.data($el, 'checked', value);
                },

                getValue: function() {
                    return !!App.dom.data($el, 'checked');
                },

                needsValidation: function() {
                    return false;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'toggler', subType);
    };
});
