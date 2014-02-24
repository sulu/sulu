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
                    App.on('husky.smart-content.' + options.instanceName + '.input-retrieved', function() {
                        App.emit('sulu.preview.update', App.dom.data($el, 'mapperProperty'), App.dom.data($el, 'smart-content'));
                    }.bind(this));
                },

                setValue: function(value) {

                    var config = value.config;

                    if (typeof(config.dataSource) !== 'undefined') {
                        App.dom.data($el, 'auraDataSource', config.dataSource);
                    }
                    if (typeof(config.includeSubFolders) !== 'undefined') {
                        App.dom.data($el, 'auraIncludeSubFolders', config.includeSubFolders);
                    }
                    if (typeof(config.tags) !== 'undefined') {
                        App.dom.data($el, 'auraTags', config.tags);
                    }
                    if (typeof(config.sortMethod) !== 'undefined') {
                        App.dom.data($el, 'auraPreSelectedSortMethod', config.sortMethod);
                    }
                    if ((typeof(config.sortBy) !== 'undefined') && config.sortBy.length > 0) {
                        App.dom.data($el, 'auraPreSelectedSortBy', config.sortBy[0]);
                    }
                    if (typeof(config.limitResult) !== 'undefined') {
                        App.dom.data($el, 'auraLimitResult', config.limitResult);
                    }
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
