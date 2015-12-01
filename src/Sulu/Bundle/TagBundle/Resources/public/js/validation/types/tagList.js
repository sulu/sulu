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

            typeInterface = {
                initializeSub: function() {
                    App.off('husky.auto-complete-list.' + this.options.instanceName + '.item-added');
                    App.off('husky.auto-complete-list.' + this.options.instanceName + '.item-deleted');

                    App.on(
                        'husky.auto-complete-list.' + this.options.instanceName + '.item-added',
                        this.itemHandler.bind(this)
                    );
                    App.on(
                        'husky.auto-complete-list.' + this.options.instanceName + '.item-deleted',
                        this.itemHandler.bind(this)
                    );
                },

                itemHandler: function() {
                    App.emit('sulu.preview.update', $el, this.getValue());
                    App.emit('sulu.content.changed');
                },

                setValue: function(value) {
                    App.dom.data($el, 'auraItems', value);
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
