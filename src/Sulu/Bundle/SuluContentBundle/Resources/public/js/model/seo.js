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

        initialize: function(options) {
            this.options = options || {};
        },

        url: function() {
            return this.urlRoot + '/' + this.get('id') + '/seo?webspace=' + this.options.webspaceKey + '&language=' + this.options.languageCode;
        },

        defaults: function() {
            return {
            };
        }
    });
});
