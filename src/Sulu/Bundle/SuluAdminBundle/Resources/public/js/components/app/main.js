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

                this.sandbox.emit(INITIALIZED.call(this));
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
            this.sandbox.dom.on(this.sandbox.dom.$window, 'resize', function(){
                this.emitContentDimensionsChangedEvent();
            }.bind(this));
        },

        /**
         * Sets the new dimensions of the content-container and
         * emits the content.dimensions-changed event
         */
        emitContentDimensionsChangedEvent: function() {
            var newContentDimensions = this.getContentDimensions();

            if (this.contentDimensions.width !== newContentDimensions.width ||
                this.contentDimensions.left !== newContentDimensions.left) {

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
            }
        },

        /**
         * Starts the Loader if the content is loading
         */
        startLoader: function() {
            var $element = this.sandbox.dom.createElement('<div class="sulu-app-loader">');
            this.sandbox.dom.css($element, {
                'margin-top': (this.sandbox.dom.height(this.sandbox.dom.$window)/2 - 75) + 'px'
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

                if (!!trigger && this.currentRoute !== route && this.currentRoute !== null) {
                    // FIXME - edit toolbar does not get removed and because of that the dom element will be removed
                    // and the stop event will be called
                    this.sandbox.stop('#edit-toolbar');
                    this.sandbox.stop('#content > *');
                    this.sandbox.stop('#preview > *');
                }

                // reset store for cleaning environment
                this.sandbox.mvc.Store.reset();

                // reset content max-width, which might was set by datagrid-list
                this.sandbox.dom.css('#content','max-width','');

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

            // select right navigation-item on navigation startup
            this.sandbox.on('husky.navigation.initialized', function() {
                if (!!this.sandbox.mvc.history.fragment && this.sandbox.mvc.history.fragment.length > 0) {
                    this.selectNavigationItem(this.sandbox.mvc.history.fragment);
                }
            }.bind(this));

            // change user locale
            this.sandbox.on('husky.navigation.user-locale.changed', function(locale) {
                this.changeUserLocale(locale);
            }.bind(this));
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
