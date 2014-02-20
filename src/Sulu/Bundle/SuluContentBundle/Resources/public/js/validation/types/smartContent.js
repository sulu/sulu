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

                    App.on('husky.smart-content.' + options.instanceName + '.input-retrieved', function() {
                        App.emit('sulu.preview.update', App.dom.data($el, 'mapperProperty'), App.dom.data($el, 'smart-content'));
                    }.bind(this));
                },

                setValue: function(value) {
                    App.dom.data($el, 'auraDataSource', value.config.dataSource);
                    App.dom.data($el, 'auraIncludeSubFolders', value.config.includeSubFolders);
                    App.dom.data($el, 'auraTags', value.config.tags);
                    App.dom.data($el, 'auraPreSelectedSortMethod', value.config.sortMethod);
                    if (value.config.sortBy.length > 0) {
                        App.dom.data($el, 'auraPreSelectedSortBy', value.config.sortBy[0]);
                    }
                    App.dom.data($el, 'auraLimitResult', value.config.limitResult);
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
