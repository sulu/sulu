/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalmodel'], function (RelationalModel) {

    'use strict';

    /**
     * Returns the proper url for a localized filter
     * @param urlRoot
     * @param id
     * @param locale
     * @returns {string}
     */
    function getUrl(urlRoot, id, locale) {
        return urlRoot + (id !== undefined ? '/' + id : '') + '?locale=' + locale;
    }

    return new RelationalModel({

        urlRoot: '/admin/api/filters',

        /**
         * Saves a localized filter
         * @param locale
         * @param options
         * @returns {*}
         */
        saveLocale: function (locale, options) {
            options = _.defaults(
                (options || {}),
                {
                    url: getUrl(this.urlRoot, this.get('id'), locale)
                }
            );

            return this.save.call(this, null, options);
        },

        /**
         * Loads a localized filter
         * @param locale
         * @param options
         * @returns {*}
         */
        fetchLocale: function (locale, options) {
            options = _.defaults((options || {}),
                {
                    url: getUrl(this.urlRoot, this.get('id'), locale)
                }
            );

            return this.fetch.call(this, options);
        },

        /**
         * Defaults
         * @returns {{name: string, context: string, conjunction: string, conditionGroups: Array}}
         */
        defaults: function () {
            return {
                name: '',
                context: '',
                conjunction: '',
                conditionGroups: []
            };
        }
    });
});
