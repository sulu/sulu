require.config({
    paths: {
        suluaudiencetargeting: '../../suluaudiencetargeting/js',
        suluaudiencetargetingcss: '../../suluaudiencetargeting/css',

        'services/suluaudiencetargeting/target-group-manager': '../../suluaudiencetargeting/js/services/target-groups/manager',
        'services/suluaudiencetargeting/target-group-router': '../../suluaudiencetargeting/js/services/target-groups/router',

        'type/conditionList': '../../suluaudiencetargeting/js/validation/types/condition-list',
        'type/audienceTargetingGroups': '../../suluaudiencetargeting/js/validation/types/audienceTargetingGroups'
    }
});

define([
    'css!suluaudiencetargetingcss/main'
], function() {

    'use strict';

    return {

        name: 'Sulu Audience Targeting Bundle',

        initialize: function(app) {

            app.components.addSource('suluaudiencetargeting', '/bundles/suluaudiencetargeting/js/components');

            app.sandbox.mvc.routes.push({
                route: 'settings/target-groups',
                callback: function() {
                    return '<div data-aura-component="target-groups/list@suluaudiencetargeting"/>';
                }
            });
            app.sandbox.mvc.routes.push({
                route: 'settings/target-groups/edit::id',
                callback: function(id) {
                    return '<div data-aura-component="target-groups/edit@suluaudiencetargeting" data-aura-id="' + id + '"/>';
                }
            });
            app.sandbox.mvc.routes.push({
                route: 'settings/target-groups/add',
                callback: function() {
                    return '<div data-aura-component="target-groups/edit@suluaudiencetargeting"/>';
                }
            });
        }
    };
});
