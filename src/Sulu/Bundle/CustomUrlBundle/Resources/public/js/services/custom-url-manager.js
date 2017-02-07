/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'services/husky/util'], function(_, Util) {

    'use strict';

    var instance = null,
        urlTemplate = _.template('/admin/api/webspaces/<%= webspace.key %>/custom-urls<% if (!!id) { %>/<%= id %><% } %><% if (!!ids) { %>?ids=<%= ids.join(",") %><% } %>');

    /** @constructor **/
    function CustomUrlManager() {
    }

    CustomUrlManager.prototype = {
        /**
         * Save record identified by id with given data.
         * If id is null a new record will be created.
         *
         * @param {Integer} id
         * @param {Object} data
         * @param {{key}} webspace
         */
        save: function(id, data, webspace) {
            return Util.save(this.generateUrl(webspace, id, null), !!id ? 'PUT' : 'POST', data);
        },

        /**
         * Deletes selected records from datagrid after asking for confirmation.
         *
         * @param {Integer[]} ids
         * @param {{key}} webspace
         */
        del: function(ids, webspace) {
            return Util.save(this.generateUrl(webspace, null, ids), 'DELETE');
        },

        /**
         * Generates url for given parameters.
         *
         * @param {{key}} webspace
         * @param {Integer} id
         * @param {Integer[]} ids
         */
        generateUrl: function(webspace, id, ids) {
            return urlTemplate({webspace: webspace, id: id, ids: ids});
        }
    };

    CustomUrlManager.getInstance = function() {
        if (instance === null) {
            instance = new CustomUrlManager();
        }
        return instance;
    };

    return CustomUrlManager.getInstance();
});
