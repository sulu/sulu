/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backbone'], function (Backbone) {

    'use strict';

    var router;

    var AppRouter = Backbone.Router.extend({
        routes: {
            // Default
            '*actions': 'defaultAction'
        },

        defaultAction: function (action) {
            // We have no matching route, lets just log what the URL was
            console.log('No route: ', action);
        }
    });

    var initialize = function (App) {
        router = new AppRouter();

        App.Router = router;

        //load bundle routes
        require(['/app_dev.php/admin/routes']);
    };

    var navigate = function (action) {
        router.navigate(action, {trigger: true});
    };

    var route = function (route, name, callback) {
        router.route(route, name, callback);
    };

    return {
        initialize: initialize,
        navigate: navigate,
        route: route
    };
});