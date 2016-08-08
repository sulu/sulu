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
            navigationCollapsedClass: 'navigation-collapsed',
            smallFixedClass: 'small-fixed',
            initialLoaderClass: 'initial-loader',
            maxWidthClass: 'max',
            columnSelector: '.content-column',
            noLeftSpaceClass: 'no-left-space',
            noRightSpaceClass: 'no-right-space',
            noTopSpaceClass: 'no-top-space',
            noTransitionsClass: 'no-transitions',
            versionHistoryUrl: 'https://github.com/sulu-cmf/sulu-standard/releases',
            changeLanguageUrl: '/admin/security/profile/language'
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
         * raised before
         * @event sulu.app.before-navigate
         */
        BEFORE_NAVIGATE = function() {
            return createEventName('before-navigate');
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
         * listens on and shrinks or expands the content-column
         * @event sulu.app.toggle-column
         * @param {Boolean} true to shrink, false to expand the content-column
         */
        TOGGLE_COLUMN = function() {
            return createEventName('toggle-column');
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

            this.initializeRouter();
            this.bindCustomEvents();
            this.bindDomEvents();

            if (!!this.sandbox.mvc.history.fragment && this.sandbox.mvc.history.fragment.length > 0) {
                this.selectNavigationItem(this.sandbox.mvc.history.fragment);
            }

            this.sandbox.emit(INITIALIZED.call(this));

            this.sandbox.util.ajaxError(function(event, request) {
                switch (request.status) {
                    case 401:
                        window.location.replace('/admin/login');
                        break;
                    case 403:
                        this.sandbox.emit(
                            'sulu.labels.error.show',
                            'public.forbidden',
                            'public.forbidden.description',
                            ''
                        );
                        break;
                }
            }.bind(this));
        },

        /**
         * Extract an error message (or messages) from the response
         *
         * @param {object} request
         * @return {string}
         */
        extractErrorMessage: function(request) {
            var message = [request.status];

            // if response is symfony JSON exception
            if (request.responseJSON !== undefined) {
                var response = request.responseJSON;

                this.sandbox.util.each(response, function(index) {
                    var exception = response[index];

                    if (exception.message !== undefined) {
                        message.push(exception.message);
                    }
                });
            }

            return message.join(", ");
        },

        /**
         * Initializes the backbone router
         */
        initializeRouter: function() {
            var AppRouter = this.sandbox.mvc.Router();
            router = new AppRouter();

            // Dashboard
            this.sandbox.mvc.routes.push({
                route: '',
                callback: function() {
                    return '<div class="sulu-dashboard" data-aura-component="dashboard@suluadmin"/>';
                }
            });

            this.sandbox.util._.each(this.sandbox.mvc.routes, function(route) {
                router.route(route.route, function() {
                    this.routeCallback.call(this, route, arguments);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Cleans up and calls the callback of a route. If it receives content
         * through the route-callback add it to the dom
         * @param route {Object} backbone route
         * @param routeArgs the arguments to pass to the route-callback
         */
        routeCallback: function(route, routeArgs) {
            this.sandbox.mvc.Store.reset();
            this.beforeNavigateCleanup(route);
            var content = route.callback.apply(this, routeArgs);
            if (!!content) {
                this.selectNavigationItem(this.sandbox.mvc.history.fragment);
                content = this.sandbox.dom.createElement(content);
                this.sandbox.dom.html('#content', content);
                this.sandbox.start('#content', {reset: true});
            }
        },

        /**
         * Takes an action and emits a sets the matching navigation-item active
         * @param action {string}
         */
        selectNavigationItem: function(action) {
            this.sandbox.emit('husky.navigation.select-item', action);
        },

        /**
         * Bind DOM-related Events
         */
        bindDomEvents: function() {
            // call navigate event for marked a-tags
            this.sandbox.dom.on(this.sandbox.dom.$document, 'click', function(event) {
                // prevent the default action for the anchor tag
                this.sandbox.dom.preventDefault(event);

                var dataSuluEvent = this.sandbox.dom.attr(event.currentTarget, 'data-sulu-event'),
                    eventArgs = this.sandbox.dom.data(event.currentTarget, 'eventArgs');

                // if data-sulu-event attribute is set emit the attribute value as an event
                if (!!dataSuluEvent && typeof dataSuluEvent === 'string') {
                    this.sandbox.emit(dataSuluEvent, eventArgs);
                }

                // if valid href attribute is set navigate to it using the sulu.navigate method
                if (!!event.currentTarget.attributes.href && !!event.currentTarget.attributes.href.value &&
                    event.currentTarget.attributes.href.value !== '#') {

                    this.emitNavigationEvent({action: event.currentTarget.attributes.href.value});
                }
            }.bind(this), 'a' + constants.suluNavigateAMark);
        },

        /**
         * Handler for the sulu.router.navigate event. Calls the backbone-router
         * @param route {String} the route to navigate to
         * @param trigger {Boolean} if trigger is true it will be actually navigated to the route. Otherwise only the browser-url will be updated
         * @param forceReload {Boolean} force page to reload
         */
        navigate: function(route, trigger, forceReload) {
            this.sandbox.emit(BEFORE_NAVIGATE.call(this));

            // if trigger is not define make it always true to actually route to
            trigger = (typeof trigger !== 'undefined') ? trigger : true;

            forceReload = forceReload === true;

            if (forceReload) {
                this.sandbox.mvc.history.fragment = null;
            }

            // navigate
            router.navigate(route, {trigger: trigger});
            this.sandbox.dom.scrollTop(this.sandbox.dom.$window, 0);
        },

        /**
         * Cleans things up before navigating
         */
        beforeNavigateCleanup: function() {
            this.sandbox.stop('.sulu-header');
            this.sandbox.stop('#content > *');
            this.sandbox.stop('#sidebar > *');
            app.cleanUp();
        },

        /**
         * Bind component-related events
         */
        bindCustomEvents: function() {
            // navigate
            this.sandbox.on('sulu.router.navigate', this.navigate.bind(this));

            // navigation event
            this.sandbox.on('husky.navigation.item.select', function(event) {
                this.emitNavigationEvent(event);

                // update title
                if (!!event.parentTitle) {
                    this.setTitlePostfix(this.sandbox.translate(event.parentTitle));
                } else if (!!event.title) {
                    this.setTitlePostfix(this.sandbox.translate(event.title));
                }
            }.bind(this));

            this.sandbox.on('husky.navigation.collapsed', function() {
                this.$find('.navigation-container').addClass(constants.navigationCollapsedClass);
            }.bind(this));

            this.sandbox.on('husky.navigation.uncollapsed', function() {
                this.$find('.navigation-container').removeClass(constants.navigationCollapsedClass);
            }.bind(this));

            this.sandbox.on('husky.navigation.header.clicked', function() {
                this.navigate('', true, false);
            }.bind(this));

            this.sandbox.on('husky.data-navigation.selected', function(item) {
                if (!!item && !!item._links && !!item._links.admin) {
                    this.sandbox.emit('sulu.router.navigate', item._links.admin.href, true, false);
                }
            }.bind(this));

            // content tabs event
            this.sandbox.on('husky.tabs.header.item.select', function(event) {
                this.emitNavigationEvent(event);
            }.bind(this));

            this.sandbox.on(HAS_STARTED.call(this), function(callbackFunction) {
                callbackFunction(true);
            }.bind(this));

            // select right navigation-item on navigation startup
            this.sandbox.on('husky.navigation.initialized', function() {
                this.sandbox.dom.remove('.' + constants.initialLoaderClass);
                if (!!this.sandbox.mvc.history.fragment && this.sandbox.mvc.history.fragment.length > 0) {
                    this.selectNavigationItem(this.sandbox.mvc.history.fragment);
                }
            }.bind(this));

            this.sandbox.on('husky.navigation.version-history.clicked', function() {
                window.open(constants.versionHistoryUrl, '_blank');
            }.bind(this));

            this.sandbox.on('husky.navigation.user-locale.changed', this.changeUserLocale.bind(this));

            this.sandbox.on('husky.navigation.username.clicked', this.routeToUserForm.bind(this));

            this.sandbox.on(CHANGE_USER_LOCALE.call(this), this.changeUserLocale.bind(this));

            this.sandbox.on(CHANGE_WIDTH.call(this), this.changeWidth.bind(this));

            this.sandbox.on(CHANGE_SPACING.call(this), this.changeSpacing.bind(this));

            this.sandbox.on(TOGGLE_COLUMN.call(this), this.toggleColumn.bind(this));
        },

        /**
         * Shrinks or expands the content-column depending on the passed parameter
         * @param shrink {Boolean} if true shrinks, if false expands the content-column
         */
        toggleColumn: function(shrink) {
            var $column = this.sandbox.dom.find(constants.columnSelector);
            this.sandbox.dom.removeClass($column, constants.noTransitionsClass);
            this.sandbox.dom.on($column, 'transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd', function() {
                this.sandbox.dom.trigger(this.sandbox.dom.window, 'resize');
            }.bind(this));
            if (!!shrink) {
                this.sandbox.emit('husky.navigation.hide');
                this.sandbox.dom.addClass($column, constants.smallFixedClass);
            } else {
                this.sandbox.emit('husky.navigation.show');
                this.sandbox.dom.removeClass($column, constants.smallFixedClass);
            }
        },

        /**
         * changes the spacing of the content
         * @event sulu.app.change-spacing
         * @param {Boolean} leftSpacing false for no spacing left
         * @param {Boolean} rightSpacing false for no spacing right
         * @param {Boolean} topSpacing false for no spacing top
         */
        changeSpacing: function(leftSpacing, rightSpacing, topSpacing) {
            var $column = this.sandbox.dom.find(constants.columnSelector);
            this.sandbox.dom.addClass($column, constants.noTransitionsClass);
            // left space
            if (leftSpacing === false) {
                this.sandbox.dom.addClass($column, constants.noLeftSpaceClass);
            } else if (leftSpacing === true) {
                this.sandbox.dom.removeClass($column, constants.noLeftSpaceClass);
            }

            // right space
            if (rightSpacing === false) {
                this.sandbox.dom.addClass($column, constants.noRightSpaceClass);
            } else if (rightSpacing === true) {
                this.sandbox.dom.removeClass($column, constants.noRightSpaceClass);
            }

            // top space
            if (topSpacing === false) {
                this.sandbox.dom.addClass($column, constants.noTopSpaceClass);
            } else if (topSpacing === true) {
                this.sandbox.dom.removeClass($column, constants.noTopSpaceClass);
            }
        },

        /**
         * Changes the width of content to fixed or to max
         * @param width {String} the new type of width to apply to the content. 'fixed' or 'max'
         * @param reset {Boolean} iff true resets the fixed-small class
         */
        changeWidth: function(width, reset) {
            var $column = this.sandbox.dom.find(constants.columnSelector);
            this.sandbox.dom.removeClass($column, constants.noTransitionsClass);
            if (reset === true) {
                this.sandbox.dom.removeClass($column, constants.smallFixedClass);
            }
            if (width === 'fixed') {
                this.changeToFixedWidth(false);
            } else if (width === 'max') {
                this.changeToMaxWidth();
            } else if (width === 'fixed-small') {
                this.changeToFixedWidth(true);
            }
            this.sandbox.dom.trigger(this.sandbox.dom.window, 'resize');
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
            }
        },

        /**
         * Makes the content take the maximum of the available space
         */
        changeToMaxWidth: function() {
            var $column = this.sandbox.dom.find(constants.columnSelector);

            if (!this.sandbox.dom.hasClass($column, constants.maxWidthClass)) {
                this.sandbox.dom.removeClass($column, constants.fixedWidthClass);
                this.sandbox.dom.addClass($column, constants.maxWidthClass);
            }
        },

        /**
         * Changes the locale of the user
         * @param locale {string} locale to change to
         */
        changeUserLocale: function(locale) {
            this.sandbox.util.ajax({
                type: 'PUT',
                url: constants.changeLanguageUrl,
                contentType: 'application/json',
                dataType: 'json',
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
            this.navigate('contacts/contacts/edit:' + this.sandbox.sulu.user.contact.id + '/details', true, false, false);
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
         */
        emitNavigationEvent: function(event) {
            if (!!event.action) {
                this.sandbox.emit('sulu.router.navigate', event.action, event.forceReload);
            }
        }
    };
});
