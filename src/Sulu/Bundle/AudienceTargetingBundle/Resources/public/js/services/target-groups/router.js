define(['services/husky/util', 'services/husky/mediator'], function(Util, Mediator) {

    'use strict';

    var navigate = function(route) {
            Mediator.emit('sulu.router.navigate', route, true, true);
        };

    return {
        toList: function() {
            navigate('settings/target-groups');
        },
        toEdit: function(id) {
            navigate('settings/target-groups/edit:' + id);
        },
        toAdd: function() {
            navigate('settings/target-groups/add');
        }
    };
});
