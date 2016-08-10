/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'jquery', 'services/husky/util', 'services/husky/mediator'], function(_, $, Util, Mediator) {

    'use strict';

    var instance = null,
        url = _.template('/admin/api/categories<% if (!!id) { %>/<%= id %><% } %>?locale=<%= locale %>');

    /** @constructor **/
    function CategoryManager() {
    }

    CategoryManager.prototype = {
        load: function(id, locale) {
            return $.ajax(url({id: id, locale: locale}))
        },
        save: function(data, locale) {
            return $.ajax(url({id: data.id, locale: locale}), {method: !!data.id ? 'PUT' : 'POST', data: data});
        },
        delete: function(id, locale) {
            return $.ajax(url({id: id, locale: locale}), {method: 'DELETE'});
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
