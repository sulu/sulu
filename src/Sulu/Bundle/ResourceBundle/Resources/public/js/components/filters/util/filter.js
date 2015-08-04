/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    return {

        /**
         * Returns a setting key for filters
         *
         * @param context
         *
         * @returns {string}
         */
        getFilterSettingKey: function(context) {
            return context + 'Filter';
        },

        /**
         * Returns a setting value for filters
         *
         * @param filter
         *
         * @returns {{id: *, name: *, context: *}}
         */
        getFilterSettingValue: function(filter) {
            return {
                id: filter.id + '',
                name: filter.name,
                context: filter.context
            }
        }
    };
});
