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
                    App.off('husky.datagrid.' + this.options.instanceName + '.item.select');
                    App.off('husky.datagrid.' + this.options.instanceName + '.item.deselect');

                    App.on(
                        'husky.datagrid.' + this.options.instanceName + '.item.select',
                        this.itemHandler.bind(this)
                    );
                    App.on(
                        'husky.datagrid.' + this.options.instanceName + '.item.deselect',
                        this.itemHandler.bind(this)
                    );
                },

                itemHandler: function() {
                    App.emit('sulu.preview.update', $el, this.getValue());
                    App.emit('sulu.content.changed');
                },

                setValue: function(value) {
                    App.dom.data($el, 'selected', value);
                },

                getValue: function() {
                    return App.dom.data($el, 'selected');
                },

                needsValidation: function() {
                    return false;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'categoryList', typeInterface);
    };
});
