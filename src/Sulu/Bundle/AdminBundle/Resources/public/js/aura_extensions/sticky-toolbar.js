/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var constants = {
            scrollContainerSelector: '.content-column > .wrapper .page',
            fixedClass: 'fixed',
            scrollMarginTop: 90,
            stickyToolbarClass: 'sticky-toolbar'
        },

        /**
         * Handles the scroll event to fix or unfix the given element.
         */
        scrollHandler = function($el, scrollTop, scrollMarginTop) {
            if (scrollTop > (scrollMarginTop || constants.scrollMarginTop)) {
                $el.addClass(constants.fixedClass);
            } else {
                $el.removeClass(constants.fixedClass);
            }
        };

    return function(app) {
        /**
         * Provides functions to enable or disable the sticky toolbar.
         *
         * @type {{enable, disable}}
         */
        app.sandbox.stickyToolbar = {
            enable: function($el, scrollMarginTop) {
                $el.addClass(constants.stickyToolbarClass);

                app.sandbox.dom.on(constants.scrollContainerSelector, 'scroll.sticky-toolbar', function() {
                    scrollHandler($el, app.sandbox.dom.scrollTop(constants.scrollContainerSelector), scrollMarginTop);
                });
            },

            disable: function($el) {
                $el.removeClass(constants.stickyToolbarClass);

                app.sandbox.dom.off(constants.scrollContainerSelector, 'scroll.sticky-toolbar');
            },

            reset: function($el) {
                $el.removeClass(constants.fixedClass);
            }
        };

        /**
         * Gets executed every time BEFORE a component gets initialized.
         */
        app.components.after('initialize', function() {
            if (!this.stickyToolbar) {
                return;
            }

            this.sandbox.stickyToolbar.enable(
                this.$el,
                (typeof this.stickyToolbar === 'number' ? this.stickyToolbar : null)
            );
        });

        /**
         * Gets executed every time BEFORE a component gets destroyed.
         */
        app.components.before('destroy', function() {
            if (!this.stickyToolbar) {
                return;
            }

            this.sandbox.stickyToolbar.disable(this.$el);
        });
    };
});
