/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require(['husky'], function (Husky) {

    'use strict';

    var app = new Husky({debug: { enable: true }});


    require(['text!/admin/bundles'], function (text) {
        var bundles = JSON.parse(text);

        bundles.forEach(function (bundle) {
            app.use('/bundles/' + bundle + '/js/main.js');
        }.bind(this));

        app.start().then(function () {
            app.logger.log('Aura started...');

            if (!!app.sandbox.mvc.routes) {

                var AppRouter = app.sandbox.mvc.Router({
                    routes: {
                        // Default
                        '*actions': 'defaultAction'
                    },

                    defaultAction: function (action) {
                        // We have no matching route,
                        // lets just log what the URL was
                        app.logger.log('No route: ', action);
                    }
                }), router = new AppRouter();

                app.sandbox.util._.each(app.sandbox.mvc.routes, function (route) {
                    router.route(route.route, function () {
                        app.sandbox.start(this);
                    }.bind(route.components));
                });
            }
        });
    });

});
