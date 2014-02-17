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
                setValue: function(value) {
                    var config = App.util.extend(true, {}, value.config);
                    if (!!config.sortBy) {
                        config.preSelectedSortBy = config.sortBy[0];
                        delete config.sortBy;
                    }
                    if (!!config.sortMethod) {
                        config.preSelectedSortMethod = config.sortMethod;
                        delete config.sortMethod;
                    }
                    config.tagsAutoCompleteUrl = '/admin/api/tags';
                    App.emit('husky.smart-content.' + options.instanceName + '.external-configs', config);
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
