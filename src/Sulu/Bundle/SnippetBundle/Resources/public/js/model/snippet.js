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
        urlRoot: '/admin/api/snippets',

        fullFetch: function(language, options) {
            options = _.defaults(
                (options || {}),
                {
                    url: this.urlRoot
                        + (this.get('id') !== undefined ? '/' + this.get('id') : '') + '?language=' + language
                }
            );

            return this.fetch.call(this, options);
        },

        fullSave: function(language, state, attributes, options, force) {
            options = _.defaults(
                (options || {}),
                {
                    url: this.urlRoot
                        + (this.get('id') !== undefined ? '/' + this.get('id') : '')
                        + '?language=' + language
                        + (!!state ? '&state=' + state : '')
                        + (!!force ? '&force=true' : '')
                }
            );

            return this.save.call(this, attributes, options);
        }
    });
});
