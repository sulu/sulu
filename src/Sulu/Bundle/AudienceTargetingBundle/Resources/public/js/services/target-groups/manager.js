define(['underscore', 'jquery', 'services/husky/util'], function(_, $, Util) {

    'use strict';

    var instance = null,

        getInstance = function() {
            if (instance === null) {
                instance = new TargetGroupManager();
            }
            return instance;
        },

        url = _.template('/admin/api/target-groups<% if (typeof id !== "undefined") { %>/<%= id %><% } %>');

    /** @constructor **/
    function TargetGroupManager() {
    }

    TargetGroupManager.prototype = {
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
        url: url
    };

    return getInstance();
});
