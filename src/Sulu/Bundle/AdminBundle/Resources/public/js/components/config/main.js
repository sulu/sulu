/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var config = {};

    return {

        /**
         * Adds a config for a key
         * @param key
         * @param moduleConfig
         */
        set: function(key, moduleConfig) {
            if(!!key && typeof(key) === 'string') {
                config[key] = moduleConfig;
            }
        },

        /**
         * Returns configuration for key
         * @param key should be a string which starts with the js-bundle-name and is seperated by dots
         * e.g. 'suluproduct.components.autocomplete.default'
         * @returns {*} configuration object
         */
        get: function(key) {
            if (!!key && typeof(key) === 'string' && config.hasOwnProperty(key)) {
                var value = config[key], empty;
                if (typeof value === 'object') {
                    empty = (value instanceof Array) ? [] : {};
                    value = jQuery.extend(true, empty, value);
                }
                return value;
            }
            return null;
        }
    };
});
