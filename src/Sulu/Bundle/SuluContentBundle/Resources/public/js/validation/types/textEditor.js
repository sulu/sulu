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
                initializeSub: function() {
                    // remove event with same name and register new one
                    App.off('husky.ckeditor.' + this.options.instanceName + '.changed');
                    App.on('husky.ckeditor.' + this.options.instanceName + '.changed', function(data, $el) {
                        App.emit('sulu.preview.update', $el, data, true);
                        App.emit('sulu.content.changed');
                    }.bind(this));

                    // remove event with same name and register new one
                    App.off('husky.ckeditor.' + this.options.instanceName + '.focusout');
                    App.on('husky.ckeditor.' + this.options.instanceName + '.focusout', function(data, $el) {
                        App.emit('sulu.preview.update', $el, data);
                        App.emit('sulu.content.changed');
                    }.bind(this));
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
