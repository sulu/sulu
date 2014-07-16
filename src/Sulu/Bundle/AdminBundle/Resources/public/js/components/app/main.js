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

        constants = {
            suluNavigateAMark: '[data-sulu-navigate="true"]', //a tags which match this mark will use the sulu.navigate method
            fixedWidthClass: 'fixed',
            smallFixedClass: 'small-fixed',
            maxWidthClass: 'max',
            columnSelector: '.content-column',
            noLeftSpaceClass: 'no-left-space',
            noRightSpaceClass: 'no-right-space',
            noTopSpaceClass: 'no-top-space',
            shrinkIcon: 'fa-chevron-left',
            expandIcon: 'fa-chevron-right',
            noTransitionsClass: 'no-transitions'
        },

        templates = {
            shrinkable: '<div class="sulu-app-shrink"><span class="fa-chevron-left"></span></div>'
        },

        eventNamespace = 'sulu.app.',

        /**
         * raised after the initialization has finished
         * @event sulu.app.initialized
         */
            INITIALIZED = function() {
            return createEventName('initialized');
        },

        /**
         * listens on and returns true
         * @event sulu.app.content.has-started
         * @param {function} callback The callback to pass true on
         */
            HAS_STARTED = function() {
            return createEventName('has-started');
        },

        /**
         * listens on and changes the user's locale to a passe done
         * @event sulu.app.change-user-locale
         * @param {String} the locale to change to
         */
         CHANGE_USER_LOCALE = function() {
            return createEventName('change-user-locale');
         },

        /**
         * listens on and changes the width type of the column
         * @event sulu.app.change-width
         * @param {String} the new width-type. 'fixed' or 'max'
         */
        CHANGE_WIDTH = function() {
            return createEventName('change-width');
        },

        /**
         * listens on and changes the spacing of the content
         * @event sulu.app.change-spacing
         * @param {Boolean} false for no spacing left
         * @param {Boolean} false for no spacing right
         * @param {Boolean} false for no spacing top
         */
        CHANGE_SPACING = function() {
            return createEventName('change-spacing');
        },

        /**
         * listens on and displays or hides the toggle icon
         * @event sulu.app.toggle-shrinker
         * @param {Boolean} true to display, false to hide the shrinker-button
         */
        TOGGLE_SHRINKER = function() {
            return createEventName('toggle-shrinker');
        },

        /**
         * Creates the event-names
         */
            createEventName = function(postFix) {
            return eventNamespace + postFix;
        };

    return {
        name: 'Sulu App',

        /**
         * Initialize the component
         */
        initialize: function() {
            this.title = document.title;
            this.$shrinker = null;
            this.currentRoute = null;

            this.initializeRouter();
            this.render();
            this.bindCustomEvents();
            this.bindDomEvents();

            if (!!this.sandbox.mvc.history.fragment && this.sandbox.mvc.history.fragment.length > 0) {
                this.selectNavigationItem(this.sandbox.mvc.history.fragment);
            }

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Initializes the backbone router
         */
        initializeRouter: function() {
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
                    this.sandbox.mvc.Store.reset();
                    this.beforeNavigateCleanup();
                    route.callback.apply(this, arguments);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Takes an action and emits a sets the matching navigation-item active
         * @param action {string}
         */
        selectNavigationItem: function(action) {
            this.sandbox.emit('husky.navigation.select-item', action);
        },

        /**
         * Renderes dom events for the component
         */
        render: function() {
            var $column = this.sandbox.dom.find(constants.columnSelector);
            this.$shrinker = this.sandbox.dom.createElement(templates.shrinkable);
            this.sandbox.dom.hide(this.$shrinker);
            this.sandbox.dom.append($column, this.$shrinker);
        },

        /**
         * Bind DOM-related Events
         */
        bindDomEvents: function() {
            // call navigate event for marked a-tags
            this.sandbox.dom.on(this.sandbox.dom.$document, 'click', function(event) {
                // prevent the default action for the anchor tag
                this.sandbox.dom.preventDefault(event);

                var dataSuluEvent = this.sandbox.dom.attr(event.currentTarget, 'data-sulu-event');

                // if data-sulu-event attribute is set emit the attribute value as an event
                if (!!dataSuluEvent &&
                    typeof dataSuluEvent === 'string') {
                    this.sandbox.emit(dataSuluEvent);
                }

                // if valid href attribute is set navigate to it using the sulu.navigate method
                if (!!event.currentTarget.attributes.href &&
                    !!event.currentTarget.attributes.href.value &&
                    event.currentTarget.attributes.href.value !== '#') {

                    this.emitNavigationEvent({action: event.currentTarget.attributes.href.value}, true, true);
                }
            }.bind(this), 'a' + constants.suluNavigateAMark);

            this.sandbox.dom.on(this.$shrinker, 'click', this.toggleShrinkColumn.bind(this));
        },

        /**
         * Handler for the sulu.router.navigate event. Calls the backbone-router
         * @param route {String} the route to navigate to
         * @param trigger {Boolean} if trigger is true it will be actually navigated to the route. Otherwise only the borswer-url will be updated
         * @param noLoader {Boolean} if false no loader will be instantiated
         */
        navigate: function(route, trigger, noLoader) {
            // if trigger is not define make it always true to actually route to
            trigger = (typeof trigger !== 'undefined') ? trigger : true;

            // only route if route has changed
            if (this.currentRoute !== route) {
                this.currentRoute = route;

                if (noLoader !== true && this.currentRoute !== route && this.currentRoute !== null) {
                    // todo: start loader
                }

                // navigate
                router.navigate(route, {trigger: trigger});
                this.sandbox.dom.scrollTop(this.sandbox.dom.$window, 0);
            }
        },

        /**
         * Cleans things up before navigating
         */
        beforeNavigateCleanup: function() {
            this.sandbox.dom.remove('.sulu-header-background');

            // FIXME App.stop is used in global context; possibly there is a better solution
            // and the stop method will be called
            App.stop('#sulu-content-container');
            App.stop('#sulu-header-container');
            App.stop('#content > *');
            App.stop('#preview > *');
        },

        /**
         * Bind component-related events
         */
        bindCustomEvents: function() {
            // navigate
            this.sandbox.on('sulu.router.navigate', this.navigate.bind(this));

            // navigation event
            this.sandbox.on('husky.navigation.item.select', function(event) {
                this.emitNavigationEvent(event, false);

                // update title
                if (!!event.parentTitle) {
                    this.setTitlePostfix(this.sandbox.translate(event.parentTitle));
                } else if (!!event.title) {
                    this.setTitlePostfix(this.sandbox.translate(event.title));
                }
            }.bind(this));

            // content tabs event
            this.sandbox.on('husky.tabs.content.item.select', function(event) {
                this.emitNavigationEvent(event, true);
            }.bind(this));
            // content tabs event
            this.sandbox.on('husky.tabs.header.item.select', function(event) {
                this.emitNavigationEvent(event, true);
            }.bind(this));

            // return current url
            this.sandbox.on('navigation.url', function(callbackFunction) {
                callbackFunction(this.sandbox.mvc.history.fragment);
            }, this);

            this.sandbox.on(HAS_STARTED.call(this), function(callbackFunction) {
                callbackFunction(true);
            }.bind(this));

            // select right navigation-item on navigation startup
            this.sandbox.on('husky.navigation.initialized', function() {
                if (!!this.sandbox.mvc.history.fragment && this.sandbox.mvc.history.fragment.length > 0) {
                    this.selectNavigationItem(this.sandbox.mvc.history.fragment);
                }
            }.bind(this));

            // change user locale
            this.sandbox.on('husky.navigation.user-locale.changed', this.changeUserLocale.bind(this));

            // route to the form of the current user
            this.sandbox.on('husky.navigation.username.clicked', this.routeToUserForm.bind(this));

            // change user locale
            this.sandbox.on(CHANGE_USER_LOCALE.call(this), this.changeUserLocale.bind(this));

            // change the width-type of the content
            this.sandbox.on(CHANGE_WIDTH.call(this), this.changeWidth.bind(this));

            // change the width-type of the content
            this.sandbox.on(CHANGE_SPACING.call(this), this.changeSpacing.bind(this));

            // toggles the shrinker-button
            this.sandbox.on(TOGGLE_SHRINKER.call(this), this.toggleShrinker.bind(this));
        },

        /**
         * Toggles the shrinker-button
         * @param show {Boolean} if true gets displayed if false hidden
         */
        toggleShrinker: function(show) {
            if (show === true) {
                this.sandbox.dom.show(this.$shrinker);
            } else {
                this.sandbox.dom.hide(this.$shrinker);
            }
        },

        /**
         * Click-handler for the shrinker-button. Shrinks or expands the content-column
         * and hides or shows the navigation
         */
        toggleShrinkColumn: function() {
            var $column = this.sandbox.dom.find(constants.columnSelector);
            this.sandbox.dom.removeClass(this.$el, constants.noTransitionsClass);
            if (this.sandbox.dom.hasClass($column, constants.smallFixedClass)) {
                // expand
                this.sandbox.emit('husky.navigation.show');
                this.sandbox.dom.removeClass($column, constants.smallFixedClass);
                this.sandbox.dom.removeClass(this.sandbox.dom.find('span', this.$shrinker), constants.expandIcon);
                this.sandbox.dom.addClass(this.sandbox.dom.find('span', this.$shrinker), constants.shrinkIcon);
            } else {
                // shrink
                this.sandbox.emit('husky.navigation.hide');
                this.sandbox.dom.addClass($column, constants.smallFixedClass);
                this.sandbox.dom.removeClass(this.sandbox.dom.find('span', this.$shrinker), constants.shrinkIcon);
                this.sandbox.dom.addClass(this.sandbox.dom.find('span', this.$shrinker), constants.expandIcon);
            }
        },

        /**
         /**
         * changes the spacing of the content
         * @event sulu.app.change-spacing
         * @param {Boolean} leftSpacing false for no spacing left
         * @param {Boolean} rightSpacing false for no spacing right
         * @param {Boolean} topSpacing false for no spacing top
         */
        changeSpacing: function(leftSpacing, rightSpacing, topSpacing) {
            this.sandbox.dom.addClass(this.$el, constants.noTransitionsClass);
            // left space
            if (leftSpacing === false) {
                this.sandbox.dom.addClass(this.$el, constants.noLeftSpaceClass);
            } else {
                this.sandbox.dom.removeClass(this.$el, constants.noLeftSpaceClass);
            }

            // right space
            if (rightSpacing === false) {
                this.sandbox.dom.addClass(this.$el, constants.noRightSpaceClass);
            } else {
                this.sandbox.dom.removeClass(this.$el, constants.noRightSpaceClass);
            }

            // top space
            if (topSpacing === false) {
                this.sandbox.dom.addClass(this.$el, constants.noTopSpaceClass);
            } else {
                this.sandbox.dom.removeClass(this.$el, constants.noTopSpaceClass);
            }
        },

        /**
         * Changes the width of content to fixed or to max
         * @param width {String} the new type of width to apply to the content. 'fixed' or 'max'
         */
        changeWidth: function(width) {
            this.sandbox.dom.removeClass(this.$el, constants.noTransitionsClass);
            if (width === 'fixed') {
                this.changeToFixedWidth(false);
            } else if (width === 'max') {
                this.changeToMaxWidth();
            } else if (width === 'fixed-small') {
                this.changeToFixedWidth(true);
            }
        },

        /**
         * Ensures that the width of the content is fixed
         * (it just sets a css-class which contains a width property)
         * @param small {Boolean} if true small-class gets added
         */
        changeToFixedWidth: function(small) {
            var $column = this.sandbox.dom.find(constants.columnSelector);

            if (!this.sandbox.dom.hasClass($column, constants.fixedWidthClass)) {
                this.sandbox.dom.removeClass($column, constants.maxWidthClass);
                this.sandbox.dom.addClass($column, constants.fixedWidthClass);
            }
            if (small === true) {
                this.sandbox.dom.addClass($column, constants.smallFixedClass);
            } else {
                this.sandbox.dom.removeClass($column, constants.smallFixedClass);
            }
        },

        /**
         * Makes the content take the maximum of the available space
         */
        changeToMaxWidth: function() {
            var $column = this.sandbox.dom.find(constants.columnSelector);

            if (!this.sandbox.dom.hasClass($column, constants.maxWidthClass)) {
                var $parent = this.sandbox.dom.parent($column);

                this.sandbox.dom.removeClass($column, constants.smallFixedClass);
                this.sandbox.dom.removeClass($column, constants.fixedWidthClass);
                this.sandbox.dom.addClass($column, constants.maxWidthClass);

                // make sure the column is the last child of its parent. Otherwise
                // it isn't possible to take the max width
                this.sandbox.dom.detach($column);
                this.sandbox.dom.append($parent, $column);
            }
        },

        /**
         * Changes the locale of the user
         * @param locale {string} locale to change to
         */
        changeUserLocale: function(locale) {
            //Todo: don't use hardcoded url
            this.sandbox.util.ajax({
                type: 'PATCH',
                url: '/admin/api/users/' + this.options.user.id,
                contentType: 'application/json', // payload format
                dataType: 'json', // response format
                data: JSON.stringify({
                    locale: locale
                }),
                success: function() {
                    this.sandbox.dom.window.location.reload();
                }.bind(this)
            });
        },

        /**
         * Routes to the form of the user
         */
        routeToUserForm: function() {
            //Todo: don't use hardcoded url
            this.navigate('contacts/contacts/edit:'+ this.sandbox.sulu.user.id +'/details', true, false);
        },

        /**
         * Takes a postifix and updates the page title
         * @param postfix {String}
         */
        setTitlePostfix: function(postfix) {
            document.title = this.title + ' - ' + postfix;
        },

        /**
         * Emits the router.navigate event
         * @param event
         * @param {boolean} loader If true a loader will be displayed
         * @param {boolean} updateNavigation If true the navigation will be updated with the passed route
         */
        emitNavigationEvent: function(event, loader, updateNavigation) {
            if (updateNavigation === true) {
                this.selectNavigationItem(event.action);
            }
            if (!!event.action) {
                this.sandbox.emit('sulu.router.navigate', event.action, event.forceReload, loader);
            }
        }
    };
});
