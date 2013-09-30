/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    var router;

    return {
        name: 'Sulu App',

        initialize: function() {
            if (!!this.sandbox.mvc.routes) {

                var AppRouter = this.sandbox.mvc.Router({
                    routes: {
                        // Default
                        '*actions': 'defaultAction'
                    },

                    defaultAction: function(action) {
                        // We have no matching route,
                    }
                });

                router = new AppRouter();

                this.sandbox.util._.each(this.sandbox.mvc.routes, function(route) {
                    router.route(route.route, function() {
                        route.callback.apply(this, arguments);
                    }.bind(this));
                }.bind(this));

                this.bindCustomEvents();
            }
        },

        bindCustomEvents: function() {
            // listening for navigation events
            this.sandbox.on('sulu.router.navigate', function(route) {
                router.navigate(route, {trigger: true});
            });

            // init navigation
            this.sandbox.on('navigation.item.content.show', function(event) {
                this.navigationSizeChanged(event);

                if (!!event.item.action) {
                    this.sandbox.emit('sulu.router.navigate', event.item.action);
                }
            }.bind(this));

            this.sandbox.on('navigation.size.changed', function(event) {
                this.navigationSizeChanged(event);
            }.bind(this));
        },

        navigationSizeChanged: function(event) {
            // 45px margin to navigation at start
            $('#content').css('margin-left', (event.data.navWidth + 45) + "px");
        }
    };
});