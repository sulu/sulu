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

    var router,

        EVENT_CONTENT_SIZE_CHANGE = 'sulu.content.size.change',

        changeContentPaddingLeft = function(paddingLeft) {
            this.sandbox.dom.css('#content', {'margin-left': 50 + paddingLeft});

        };

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


                this.bindDomEvents();
            }
        },

        bindDomEvents: function() {
        },

        bindCustomEvents: function() {
            // listening for navigation events
            this.sandbox.on('sulu.router.navigate', function(route, trigger) {

                // default vars
                trigger = (typeof trigger !== 'undefined') ? trigger : true;

                // reset store for cleaning environment
                this.sandbox.mvc.Store.reset();

                // navigate
                router.navigate(route, {trigger: trigger});

                // move to top
                // FIXME abstract
                $(window).scrollTop(0);
            }.bind(this));

            // navigation event
            this.sandbox.on('husky.navigation.item.select', function(event) {
                this.emitNavigationEvent(event);
            }.bind(this));

            // content tabs event
            this.sandbox.on('husky.tabs.content.item.select', function(event) {
                this.emitNavigationEvent(event);
            }.bind(this));


            // return current url
            this.sandbox.on('navigation.url', function(callbackFunction) {
                callbackFunction(this.sandbox.mvc.history.fragment);
            }, this);


            // layout
            // responsive listeners
            this.sandbox.on('husky.navigation.size.changed', changeContentPaddingLeft.bind(this));
        },


        emitNavigationEvent: function(event) {

            // TODO: select right bundle / item in navigation

            if (!!event.action) {
                this.sandbox.emit('sulu.router.navigate', event.action, event.forceReload);
            }
        }

    };
});
