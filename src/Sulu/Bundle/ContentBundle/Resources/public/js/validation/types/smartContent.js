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

    var dataChangedHandler = function(data, $el) {
            // ignore first event
            if (!fired[$el.attr('id')]) {
                fired[$el.attr('id')] = true;
            } else {
                App.emit('sulu.preview.update', $el, data);
                App.emit('sulu.content.changed');
            }
        },
        fired = {};

    return function($el, options) {
        var defaults = {},

            subType = {
                initializeSub: function() {
                    App.off('husky.smart-content.' + options.instanceName + '.data-changed', dataChangedHandler);
                    App.on('husky.smart-content.' + options.instanceName + '.data-changed', dataChangedHandler);
                },

                setValue: function(value) {
                    var config = {};
                    if (!!value && !!value.config) {
                        config = value.config;
                    } else if (!!value) {
                        config = value;
                    }

                    if (typeof(config.dataSource) !== 'undefined' && !!config.dataSource) {
                        App.dom.data($el, 'auraDataSource', config.dataSource);
                    }
                    if (typeof(config.includeSubFolders) !== 'undefined' && !!config.includeSubFolders) {
                        App.dom.data($el, 'auraIncludeSubFolders', config.includeSubFolders);
                    }
                    if (typeof(config.tags) !== 'undefined' && !!config.tags) {
                        App.dom.data($el, 'auraTags', config.tags);
                    }
                    if (typeof(config.tagOperator) !== 'undefined' && !!config.tagOperator) {
                        App.dom.data($el, 'auraPreSelectedTagOperator', config.tagOperator);
                    }
                    if (typeof(config.categories) !== 'undefined' && !!config.categories) {
                        App.dom.data($el, 'auraCategories', config.categories);
                    }
                    if (typeof(config.categoryOperator) !== 'undefined' && !!config.categoryOperator) {
                        App.dom.data($el, 'auraPreSelectedCategoryOperator', config.categoryOperator);
                    }
                    if (typeof(config.sortMethod) !== 'undefined' && !!config.sortMethod) {
                        App.dom.data($el, 'auraPreSelectedSortMethod', config.sortMethod);
                    }
                    if ((typeof(config.sortBy) !== 'undefined') && !!config.sortBy) {
                        App.dom.data($el, 'auraPreSelectedSortBy', config.sortBy);
                    }
                    if ((typeof(config.presentAs) !== 'undefined') && !!config.presentAs) {
                        App.dom.data($el, 'auraPreSelectedPresentAs', config.presentAs);
                    }
                    if (typeof(config.limitResult) !== 'undefined' && !!config.limitResult) {
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
