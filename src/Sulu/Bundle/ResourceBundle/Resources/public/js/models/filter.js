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

    function getUrl(urlRoot, id, locale) {
        return urlRoot + (id !== undefined ? '/' + id : '') + '?locale=' + locale;
    }

    return new RelationalModel({

        urlRoot: '/admin/api/filters',

        saveLocale: function (locale, options) {
            options = _.defaults(
                (options || {}),
                {
                    url: getUrl(this.urlRoot, this.get('id'), locale)
                }
            );

            return this.save.call(this, null, options);
        },

        fetchLocale: function (locale, options) {
            options = _.defaults((options || {}),
                {
                    url: getUrl(this.urlRoot, this.get('id'), locale)
                }
            );

            return this.fetch.call(this, options);
        },

        defaults: function () {
            return {
                name: '',
                entityName: '',
                conjunction: '',
                conditionGroups: []
            };
        }
    });
});
