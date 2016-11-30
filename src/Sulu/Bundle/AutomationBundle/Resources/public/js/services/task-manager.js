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

    var url = _.template('/admin/api/tasks<% if(!!id) { %>/<%= id %><% } %>');

    return {
        getUrl: function(entityClass, entityId) {
            return url({id: null}) + '?entity-class=' + entityClass + '&entity-id=' + entityId;
        },

        load: function(id) {
            return Util.load(url({id: id}));
        },

        save: function(data) {
            return Util.save(url({id: data.id || null}), !!data.id ? 'PUT' : 'POST', data);
        },

        deleteItem: function(id) {
            return Util.save(url({id: id}), 'DELETE');
        },

        deleteItems: function(ids) {
            return Util.save(url({id: null}) + '?ids=' + ids.join(','), 'DELETE');
        }
    };
});
