define(['underscore', 'jquery', 'services/husky/util'], function(_, $, Util) {

    'use strict';

    var url = _.template('/admin/api/target-groups<% if (typeof id !== "undefined") { %>/<%= id %><% } %>');

    return {
        load: function(id) {
            return Util.load(url({id: id}));
        },
        save: function(data) {
            return Util.save(url({id: data.id}), !!data.id ? 'PUT' : 'POST', data);
        },
        delete: function(id) {
            return Util.save(url({id: id}), 'DELETE');
        },
        deleteMultiple: function(ids) {
            return Util.save(url() + '?ids=' + ids.join(','), 'DELETE');
        },
        getUrl: url
    };
});
