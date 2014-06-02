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
            contentMaxWidth: 920,
            contentMaxMarginLeft: 250,
            contentMaxPaddingLeft: 50,

            contentMinWidth: 510,
            contentMinMarginLeft: 10,
            contentMinPaddingLeft: 0,

            suluNavigateAMark: '[data-sulu-navigate="true"]', //a tags which match this mark will use the sulu.navigate method
            fullWidthClass: 'fullwidth',
            noPaddingClass: 'no-padding',
            fullHeightClass: 'fullheight'
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
         * raised if width height, or position of content-container changes
         * @event sulu.app.content.dimensions-changed
         * @param {object} Object containing width, and position from the left
         */
            CONTENT_DIMENSIONS_CHANGED = function() {
            return createEventName('content.dimensions-changed');
        },

        /**
         * raised if width height, or position of view port changes
         * @event sulu.app.content.dimensions-changed
         * @param {object} Object containing width, and position from the left
         */
            VIEWPORT_DIMENSIONS_CHANGED = function() {
            return createEventName('viewport.dimensions-changed');
        },

        /**
         * listens on and changes the dimensions of the content
         * @event sulu.app.content.dimensions-change
         * @param {object} Object containing width, and position from the left
         */
            CONTENT_DIMENSIONS_CHANGE = function() {
            return createEventName('content.dimensions-change');
        },

        /**
         * listens on and pass an object with the dimensions to the passed callback
         * @event sulu.app.content.get-dimensions
         * @param {function} callback The callback to pass the dimensions to
         */
            GET_CONTENT_DIMENSIONS = function() {
            return createEventName('content.get-dimensions');
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
         * listens on and resets the ui according to given state
         * @event sulu.app.reset.ui
         * @param {Object} contains states of navigation and content (small | large)
         */
            UI_RESET = function() {
            return createEventName('ui.reset');
        },

        /**
         * raised after reset of ui
         * @event sulu.app.reseted.ui
         */
            UI_RESETED = function() {
            return createEventName('ui.reseted');
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
         * sets the container in full-width mode
         * @event sulu.app.full-size
         * @param {Boolean} true for full width
         * @param {Boolean} true for full height
         */
        SET_FULL_SIZE = function() {
            return createEventName('full-size');
        },

        /**
         * Creates the event-names
         */
            createEventName = function(postFix) {
            return eventNamespace + postFix;
        },

        /**
         * Changes the left margin of the content-container
         * @param marginLeft {number} The margin to set
         */
            changeContentMarginLeft = function(marginLeft) {
            this.sandbox.dom.css(this.$el, {'margin-left': marginLeft});
        };

    return {
        name: 'Sulu App',

        /**
         * Initialize the component
         */
        initialize: function() {
            this.title = document.title;
            this.headerVisible = false;

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

                this.contentChangeInterval = null;
                this.contentDimensions = {
                    left: null,
                    width: null
                };

                this.currentRoute = null;

                this.bindCustomEvents();
                this.bindDomEvents();

                this.sandbox.emit(INITIALIZED.call(this));
            }
        },

        /**
         * Takes an action and emits a sets the matching navigation-item active
         * @param action {string}
         */
        selectNavigationItem: function(action) {
            this.sandbox.emit('husky.navigation.select-item', action, false);
        },

        /**
         * Bind DOM-related Events
         */
        bindDomEvents: function() {
            // start centralized resize-listener
            this.sandbox.dom.on(this.sandbox.dom.$window, 'resize', function() {
                this.emitContentDimensionsChangedEvent();
                this.emitViewPortDimensionsChanged();
            }.bind(this));

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
        },

        /**
         * Emits an event with the new dimensions of the viewport
         */
        emitViewPortDimensionsChanged: function() {
            var width = this.sandbox.dom.width(window),
                height = this.sandbox.dom.height(window);

            this.sandbox.emit(VIEWPORT_DIMENSIONS_CHANGED.call(this), {width: width, height: height});
        },

        /**
         * Sets the new dimensions of the content-container and
         * emits the content.dimensions-changed event
         * @force {Boolean} if true event will gets emited for sure
         */
        emitContentDimensionsChangedEvent: function(force) {
            var newContentDimensions = this.getContentDimensions();

            if (this.contentDimensions.width !== newContentDimensions.width ||
                this.contentDimensions.left !== newContentDimensions.left ||
                force === true) {

                this.sandbox.emit(CONTENT_DIMENSIONS_CHANGED.call(this), newContentDimensions);
                this.contentDimensions = newContentDimensions;
            }
        },

        /**
         * Returns an object with the current dimensions of the content container
         * @returns {{width: number, left: number}}
         */
        getContentDimensions: function() {
            return {
                width: this.sandbox.dom.width(this.$el),
                left: Math.round(
                    this.sandbox.dom.offset(this.$el).left + parseInt(this.sandbox.dom.css(this.$el, 'padding-left').replace(/[^-\d\.]/g, ''))
                )
            };
        },

        /**
         * Starts the Loader if the content is loading
         */
        startLoader: function() {
            var $element = this.sandbox.dom.createElement('<div class="sulu-app-loader">');
            this.sandbox.dom.css($element, {
                'margin-top': (this.sandbox.dom.height(this.sandbox.dom.$window) / 2 - 75) + 'px'
            });
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: $element,
                        size: '150px',
                        color: '#cacaca'
                    }
                }
            ]);
        },

        /**
         * Bind component-related events
         */
        bindCustomEvents: function() {
            // listening for navigation events
            this.sandbox.on('sulu.router.navigate', function(route, trigger, noLoader) {

                // default vars
                trigger = (typeof trigger !== 'undefined') ? trigger : true;

                if (!!trigger && this.currentRoute !== route) {
                    // FIXME - App.stop is used in global context; possibly there is a better solution
                    // and the stop event will be called
                    App.stop('#sulu-content-container');
                    App.stop('#sulu-header-container');
                    App.stop('#content > *');
                    App.stop('#preview > *');

                    // remove eventual spacing because of header
                    this.headerVisible = false;
                    this.changeTopSpacing();

                    // remove eventual full-width and full-height mode
                    this.removeFullSize();
                }

                // reset store for cleaning environment
                this.sandbox.mvc.Store.reset();

                // reset content max-width, which might was set by datagrid-list
                this.sandbox.dom.css('#content', 'max-width', '');

                // navigate
                router.navigate(route, {trigger: trigger});

                // move to top
                this.sandbox.dom.scrollTop(this.sandbox.dom.$window, 0);

                if (noLoader !== true && this.currentRoute !== route && this.currentRoute !== null) {
                    this.startLoader();
                }
                this.currentRoute = route;
            }.bind(this));

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

            // emit dimensions-changed event during transition
            this.sandbox.on('husky.navigation.size.change', function() {
                this.contentChangeInterval = setInterval(this.emitContentDimensionsChangedEvent.bind(this), 30);
            }.bind(this));

            // emit dimensions-changed event during transition
            this.sandbox.on('husky.navigation.size.changed', function() {
                clearInterval(this.contentChangeInterval);
                this.emitContentDimensionsChangedEvent();
            }.bind(this));

            // return current url
            this.sandbox.on('navigation.url', function(callbackFunction) {
                callbackFunction(this.sandbox.mvc.history.fragment);
            }, this);

            // getter event for the content-dimensions
            this.sandbox.on(GET_CONTENT_DIMENSIONS.call(this), function(callbackFunction) {
                callbackFunction(this.getContentDimensions());
            }.bind(this));

            // layout
            // responsive listeners
            this.sandbox.on('husky.navigation.size.change', changeContentMarginLeft.bind(this));

            this.sandbox.on(HAS_STARTED.call(this), function(callbackFunction) {
                callbackFunction(true);
            }.bind(this));

            this.sandbox.on(CONTENT_DIMENSIONS_CHANGE.call(this), function(properties) {
                this.changeContentStyles(properties);
            }.bind(this));

            // stop the loader if a view gets initialized
            this.sandbox.on('sulu.view.initialize', function() {
                this.sandbox.stop('.sulu-app-loader');
            }.bind(this));

            // listener for header (add top-spacing)
            this.sandbox.on('sulu.header.initialized', function() {
                this.headerVisible = true;
                this.changeTopSpacing();
            }.bind(this));

            // select right navigation-item on navigation startup
            this.sandbox.on('husky.navigation.initialized', function() {
                if (!!this.sandbox.mvc.history.fragment && this.sandbox.mvc.history.fragment.length > 0) {
                    this.selectNavigationItem(this.sandbox.mvc.history.fragment);
                }
            }.bind(this));

            // change user locale
            this.sandbox.on('husky.navigation.user-locale.changed', this.changeUserLocale.bind(this));

            // change user locale
            this.sandbox.on(CHANGE_USER_LOCALE.call(this), this.changeUserLocale.bind(this));

            // listen for full size mode
            this.sandbox.on(SET_FULL_SIZE.call(this), this.setFullSize.bind(this));

            this.sandbox.on(UI_RESET.call(this), this.resetUI.bind(this));
        },

        /**
         * Sets the container in full-width mode
         * @param fullwidth {boolean} If true set container in full-width mode
         * @param fullheight {boolean} If true set container in full-height mode
         * @param keepPaddings {boolean} If true paddings are kept
         */
        setFullSize: function(fullwidth, fullheight, keepPaddings) {
            if (fullheight === true) {
                this.sandbox.dom.addClass(this.$el, constants.fullHeightClass);
            }
            if (fullwidth === true) {
                this.sandbox.dom.addClass(this.$el, constants.fullWidthClass);
                if (keepPaddings !== true) {
                    this.sandbox.dom.addClass(this.$el, constants.noPaddingClass);
                    this.sandbox.dom.css(this.$el, {'padding-left': ''});

                }
                this.emitContentDimensionsChangedEvent(true);
                this.sandbox.dom.trigger(this.sandbox.dom.$window, 'resize');
            }
        },

        /**
         * Removes the full-width and full-height mode from the container
         */
        removeFullSize: function() {
            this.sandbox.dom.removeClass(this.$el, constants.fullHeightClass);
            this.sandbox.dom.removeClass(this.$el, constants.noPaddingClass);
            this.sandbox.dom.removeClass(this.$el, constants.fullWidthClass);
        },

        /**
         * Resets the ui according to the given states
         * @param states
         * TODO: move all resetUI handling to css - handle it with just setting CSS-classes
         */
        resetUI: function(states) {

            if (!states.content || !states.navigation) {
                this.sandbox.logger.error('restUI: state for navigation and content are required');
                return;
            } else if (states.content === 'small' && states.navigation === 'large') {
                this.sandbox.logger.error('restUI: invalid state combination');
                return;
            }

            // show navigation to be independent of currently active states
            this.sandbox.emit('husky.navigation.show');

            if (states.navigation === 'small') {

                this.sandbox.emit('husky.navigation.collapse', true);

            } else if (states.navigation === 'large') {

                // worst case navigation is in overlay mode
                // have to collapse before resize
                this.sandbox.emit('husky.navigation.collapse', false);
                this.sandbox.emit('husky.navigation.uncollapse', false);

            } else if (states.navigation === 'auto') {

                // let the navigation decide
                this.sandbox.emit('husky.navigation.collapse', false);
                this.sandbox.emit('husky.navigation.size.update');

            } else {
                this.sandbox.logger.error("resetUI: invalid state for navigation (small or large or auto)!");
                return;
            }

            if (states.content === 'large') {
                this.resetToLargeContent();
            } else if (states.content === 'small') {
                this.resetToSmallContent();
            } else if (states.content === 'auto') {
                this.restoreContentWidthProperties();

            } else {
                this.sandbox.logger.error("resetUI: invalid state for navigation (small or large)!");
                return;
            }

            this.sandbox.emit(UI_RESETED.call(this));
        },

        /**
         * Resets the content to the large state
         */
        resetToLargeContent: function() {
            this.changeContentStyles({
                width: constants.contentMaxWidth,
                left: constants.contentMaxMarginLeft,
                paddingLeft: constants.contentMaxPaddingLeft});

            this.restoreContentWidthProperties();
        },

        /**
         * Removes possible width attribute and width css property and resets max-width
         */
        restoreContentWidthProperties: function() {
            this.sandbox.dom.width(this.$el, '');
            this.sandbox.dom.css(this.$el, 'width', '');
            this.sandbox.dom.css(this.$el, 'max-width', '920px');
        },

        /**
         * Resets the content to the small state
         */
        resetToSmallContent: function() {
            this.changeContentStyles({
                width: constants.contentMinWidth,
                left: constants.contentMinMarginLeft,
                paddingLeft: constants.contentMinPaddingLeft});

            this.sandbox.dom.width(this.$el, '');
            this.sandbox.dom.css(this.$el, 'width', '');
            this.sandbox.dom.css(this.$el, 'max-width', '510px');
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
         * Takes an object with styles and applies it to the app-content
         * @param styles
         */
        changeContentStyles: function(styles) {
            //Todo: change animate to css-transition
            this.sandbox.dom.animate(this.$el, {
                width: styles.width + 'px',
                paddingLeft: styles.paddingLeft + 'px'
            }, {
                duration: 500,
                queue: false,
                progress: this.emitContentDimensionsChangedEvent.bind(this)
            });

            changeContentMarginLeft.call(this, styles.left);
        },

        /**
         * Adds spacing to the top if there is a header, removes it if there isn't any
         */
        changeTopSpacing: function() {
            if (this.headerVisible === true) {
                this.sandbox.emit('sulu.header.get-height', function(height) {
                    this.sandbox.dom.css(this.$el, {
                        'padding-top': height + 'px'
                    });
                }.bind(this));
            } else {
                this.sandbox.dom.css(this.$el, {
                    'padding-top': '0'
                });
            }
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
