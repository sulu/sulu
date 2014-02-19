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
                initializeSub: function(dfd) {
                    dfd.resolve();

                    this.smartContentInitialized = App.data.deferred();

                    App.on('husky.smart-content.' + options.instanceName + '.data-retrieved', function() {
                        this.smartContentInitialized.resolve();
                    }.bind(this));

                    App.on('husky.smart-content.' + options.instanceName + '.input-retrieved', function() {
                        App.emit('sulu.preview.update', App.dom.data($el, 'mapperProperty'), App.dom.data($el, 'smart-content'));
                    }.bind(this));
                },

                setValue: function(value) {
                    this.smartContentInitialized.then(function() {
                        var config = App.util.extend(true, {}, value.config);
                        if (!!config.sortBy) {
                            config.preSelectedSortBy = config.sortBy[0];
                            delete config.sortBy;
                        }
                        if (!!config.sortMethod) {
                            config.preSelectedSortMethod = config.sortMethod;
                            delete config.sortMethod;
                        }
                        App.emit('husky.smart-content.' + options.instanceName + '.set-configs', config);
                    }.bind(this));
                },

                getValue: function() {
                    return App.dom.data($el, 'smart-content');
                },

                needsValidation: function() {
                    return false;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'smartContent', subType);
    };
});
