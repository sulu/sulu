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
], function(Default, Util) {

    'use strict';

    return function($el, options) {
        var defaults = {},

            subType = {
                initializeSub: function() {
                    App.on('husky.ckeditor.changed', function(data, $el) {
                        App.emit('sulu.preview.update', $el, data, true);
                        App.emit('sulu.content.changed');
                    }.bind(this));
                    App.on('husky.ckeditor.focusout', function(data, $el) {
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
