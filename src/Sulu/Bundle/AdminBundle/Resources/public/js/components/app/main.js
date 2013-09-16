/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define({
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
            }), router = new AppRouter();

            this.sandbox.util._.each(this.sandbox.mvc.routes, function(route) {
                router.route(route.route, function() {
                    route.callback.apply(this, arguments);
                }.bind(this));
            }.bind(this));

            // listening for navigation events
            this.sandbox.on('sulu.router.navigate', function(route) {
                router.navigate(route, {trigger: true});
            });

            // init navigation
            this.sandbox.on('navigation.item.content.show', function(event) {
                // FIXME abstract?
                // 45px margin to navigation at start
                $('.demo-container').css('margin-left', (event.data.navWidth + 45) + "px");
                // mid div has margin-left 25px at start
                $('#headerbar-mid').css('margin-left', (event.data.navWidth - 275 + 45) + "px");
                // navigation width is 300px at start
                $('#headerbar-right').css('width', (220 - (event.data.navWidth - 300 + 45)) + "px");

                this.sandbox.emit('sulu.router.navigate', event.item.get('action'));
            }.bind(this));
        }
    },

    startComponent: function(component) {
        this.sandbox.start([component]); //FIXME reset true
    }
});