/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'jquery', 'services/husky/util'], function(_, $, Util) {

    'use strict';

    var instance = null,
        url = _.template('/admin/api/categories<% if (!!id) { %>/<%= id %><% } %>?locale=<%= locale %>');

    /** @constructor **/
    function CategoryManager() {
    }

    CategoryManager.prototype = {
        load: function(id, locale) {
            return Util.load(url({id: id, locale: locale}));
        },
        save: function(data, locale) {
            return Util.save(url({id: data.id, locale: locale}), !!data.id ? 'PUT' : 'POST', data);
        },
        delete: function(id, locale) {
            return Util.save(url({id: id, locale: locale}), 'DELETE');
        }
    };

    CategoryManager.getInstance = function() {
        if (instance === null) {
            instance = new CategoryManager();
        }
        return instance;
    };

    return CategoryManager.getInstance();
});
