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

    var changeEvent = function(data, $el) {
            App.emit('sulu.preview.update', $el, data, true);
            App.emit('sulu.content.changed');
        },

        focusoutEvent = function(data, $el) {
            App.emit('sulu.preview.update', $el, data);
            App.emit('sulu.content.changed');
        };

    return function($el, options) {
        var defaults = {
                instanceName: null
            },

            subType = {
                initializeSub: function() {
                    // remove event with same name and register new one
                    App.off('husky.ckeditor.' + this.options.instanceName + '.changed', changeEvent);
                    App.on('husky.ckeditor.' + this.options.instanceName + '.changed', changeEvent);

                    // remove event with same name and register new one
                    App.off('husky.ckeditor.' + this.options.instanceName + '.focusout', focusoutEvent);
                    App.on('husky.ckeditor.' + this.options.instanceName + '.focusout', focusoutEvent);
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
