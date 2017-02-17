define(['services/husky/util', 'services/husky/mediator'], function(Util, Mediator) {

    'use strict';

    var instance = null,

        getInstance = function() {
            if (instance === null) {
                instance = new TargetGroupRouter();
            }

            return instance;
        },

        navigate = function(route) {
            Mediator.emit('sulu.router.navigate', route, true, true);
        };

    /** @constructor **/
    function TargetGroupRouter() {
    }

    TargetGroupRouter.prototype = {
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

    return getInstance();
});
