/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalmodel'
], function(RelationalModel) {

    'use strict';

    return new RelationalModel({
        urlRoot: '/admin/api/nodes',

        /**
         * @deprecated Use ContentManager::save instead
         */
        fullSave: function(webspace, language, parent, type, attributes, options, force, action) {
            options = _.defaults(
                (options || {}),
                {
                    url: this.urlRoot + (this.get('id') !== undefined ? '/' + this.get('id') : '')
                        + '?webspace=' + webspace
                        + '&language=' + language
                        + (!!type ? '&type=' + type : '')
                        + (!!parent ? '&parent=' + parent : '')
                        + (!!force ? '&force=' + force : '')
                        + (!!action ? '&action=' + action : '')
                });

            return this.save.call(this, attributes, options);
        },

        fullFetch: function(webspace, language, breadcrumb, options) {
            options = _.defaults((options || {}), {url: this.urlRoot + (this.get('id') !== undefined ? '/' + this.get('id') : '') + '?webspace=' + webspace + '&language=' + language + '&breadcrumb=' + !!breadcrumb});

            return this.fetch.call(this, options);
        },

        fullDestroy: function(webspace, language, force, options) {
            var id = this.get('id');

            if (!id) {
                throw new Error('The model cannot be destroyed without an ID');
            }

            options = _.defaults(
                (options || {}),
                {
                    url: this.urlRoot + '/' + id
                        + '?webspace=' + webspace
                        + '&language=' + language
                        + '&force=' + force
                }
            );

            return this.destroy.call(this, options);
        },

        defaults: function() {
            return {};
        }
    });
});
