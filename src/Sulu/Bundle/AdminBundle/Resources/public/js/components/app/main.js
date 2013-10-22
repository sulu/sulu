/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

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

                    defaultAction: function() {
                        // We have no matching route
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
                // reset store for cleaning environment
                this.sandbox.mvc.Store.reset();

                // navigate
                router.navigate(route, {trigger: true});

                // move to top
                // FIXME abstract
                $(window).scrollTop(0);
            }.bind(this));

            // init navigation
            this.sandbox.on('navigation.item.content.show', function(event) {
                if (!!event.item.action) {
                    this.sandbox.emit('sulu.router.navigate', event.item.action);
                }
            }.bind(this));


            // return current url
            this.sandbox.on('navigation.url', function(callbackFunction) {
                callbackFunction(this.sandbox.mvc.history.fragment);
            }, this);
        }

    };
});
