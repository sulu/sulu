/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class Sidebar
 * @constructor
 *
 * @param {Object} [options] Configuration object
 * @param {String} [options.instanceName] The instance name of the sidebar
 * @param {String} [options.url] Url to initially load content from
 */
define([], function() {

    'use strict';

    var defaults = {
            instanceName: '',
            url: '',
            expandable: true
        },

        constants = {
            widgetContainerSelector: '#sulu-widgets',
            componentClass: 'sulu-sidebar',
            columnSelector: '.sidebar-column',
            fixedWidthClass: 'fixed',
            maxWidthClass: 'max',
            loaderClass: 'sidebar-loader',
            visibleSidebarClass: 'has-visible-sidebar',
            maxSidebarClass: 'has-max-sidebar',
            noVisibleSidebarClass: 'has-no-visible-sidebar',
            hiddenClass: 'hidden'
        },

        /**
         * trigger after initialization has finished
         *
         * @event sulu.sidebar.[INSTANCE_NAME].initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * listens on and hides the sidebar-column
         *
         * @event sulu.sidebar.[INSTANCE_NAME].hide
         */
        HIDE_COLUMN = function() {
            return createEventName.call(this, 'hide');
        },

        /**
         * listens on and shows the sidebar-column
         *
         * @event sulu.sidebar.[INSTANCE_NAME].hide
         */
        SHOW_COLUMN = function() {
            return createEventName.call(this, 'show');
        },

        /**
         * appends a widget to the sidebar
         *
         * @event sulu.sidebar.[INSTANCE_NAME].append-widget
         * @param {String} url The url to load the widget from
         */
        APPEND_WIDGET = function() {
            return createEventName.call(this, 'append-widget');
        },

        /**
         * prepends a widget to the sidebar
         *
         * @event sulu.sidebar.[INSTANCE_NAME].prepend-widget
         * @param {String} url The url to load the widget from
         */
        PREPEND_WIDGET = function() {
            return createEventName.call(this, 'prepend-widget');
        },

        /**
         * sets a widget as the only widget in the container
         *
         * @event sulu.sidebar.[INSTANCE_NAME].set-widget
         * @param {String} url The url to load the widget from
         */
        SET_WIDGET = function() {
            return createEventName.call(this, 'set-widget');
        },

        /**
         * listens on and empties the sidebar
         *
         * @event sulu.sidebar.[INSTANCE_NAME].empty
         */
        EMPTY = function() {
            return createEventName.call(this, 'empty');
        },

        /**
         * listens on and changes the width type of the column
         *
         * @event sulu.sidebar.[INSTANCE_NAME].change-width
         * @param {String} the new width-type. 'fixed' or 'max'
         */
        CHANGE_WIDTH = function() {
            return createEventName.call(this, 'change-width');
        },

        /**
         * changes the class(es) for the inner sidebar container
         * @event sulu.sidebar.[INSTANCE_NAME].add-classes
         * @param {String} the css class(es)
         */
        ADD_CLASSES = function() {
            return createEventName.call(this, 'add-classes');
        },

        /**
         * resets the class for the inner sidebar container
         * @event sulu.sidebar.[INSTANCE_NAME].reset-classes
         */
        RESET_CLASSES = function() {
            return createEventName.call(this, 'reset-classes');
        },

        createEventName = function(postfix) {
            return 'sulu.sidebar.' + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
        };

    return {

        /**
         * Initializes the component
         */
        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.widgets = [];

            this.bindCustomEvents();
            this.render();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Renderes the component
         */
        render: function() {
            this.sandbox.dom.addClass(this.$el, constants.componentClass);
            // hide sidebar at beginning
            this.hideColumn();
        },

        /**
         * Bind custom-related events for the component
         */
        bindCustomEvents: function() {
            this.sandbox.on(CHANGE_WIDTH.call(this), this.changeWidth.bind(this));
            this.sandbox.on(HIDE_COLUMN.call(this), this.hideColumn.bind(this));
            this.sandbox.on(SHOW_COLUMN.call(this), this.showColumn.bind(this));
            this.sandbox.on(SET_WIDGET.call(this), this.setWidget.bind(this));
            this.sandbox.on(APPEND_WIDGET.call(this), this.appendWidget.bind(this));
            this.sandbox.on(PREPEND_WIDGET.call(this), this.prependWidget.bind(this));
            this.sandbox.on(EMPTY.call(this), this.emptySidebar.bind(this));
            this.sandbox.on(RESET_CLASSES.call(this), this.resetClasses.bind(this));
            this.sandbox.on(ADD_CLASSES.call(this), this.addClasses.bind(this));
        },

        /**
         * Removes previously set css classes
         */
        resetClasses: function() {
            this.sandbox.dom.removeClass(this.$el);
            this.sandbox.dom.addClass(this.$el, constants.componentClass);
        },

        /**
         * Sets css classes on the inner container of the sidebar
         * @param {String} classes
         */
        addClasses: function(classes) {
            this.sandbox.dom.addClass(this.$el, classes);
        },

        /**
         * Change the width type of the column
         * @param {String} width the new width-type. 'fixed' or 'max'
         */
        changeWidth: function(width) {
            this.width = width;
            if (width === 'fixed') {
                this.changeToFixedWidth();
            } else if (width === 'max') {
                this.changeToMaxWidth();
            }
            this.sandbox.dom.trigger(this.sandbox.dom.window, 'resize');
        },

        /**
         * Change the column to fixed width
         */
        changeToFixedWidth: function() {
            var $column = this.sandbox.dom.find(constants.columnSelector),
                $parent;

            if (!this.sandbox.dom.hasClass($column, constants.fixedWidthClass)) {
                $parent = this.sandbox.dom.parent($column);

                this.sandbox.dom.removeClass($column, constants.maxWidthClass);
                this.sandbox.dom.addClass($column, constants.fixedWidthClass);

                // make sure the column is not the last child of its parent. To enable
                // other containers to take the max width
                this.sandbox.dom.detach($column);
                this.sandbox.dom.prepend($parent, $column);

                this.sandbox.dom.removeClass($parent, constants.maxSidebarClass);
            }
        },

        /**
         * Ensures that the column takes the maximum of the available space
         */
        changeToMaxWidth: function() {
            var $column = this.sandbox.dom.find(constants.columnSelector),
                $parent;

            if (!this.sandbox.dom.hasClass($column, constants.maxWidthClass)) {
                $parent = this.sandbox.dom.parent($column);

                this.sandbox.dom.removeClass($column, constants.fixedWidthClass);
                this.sandbox.dom.addClass($column, constants.maxWidthClass);

                // make sure the column is the last child of its parent. Otherwise
                // it isn't possible to take the max width
                this.sandbox.dom.detach($column);
                this.sandbox.dom.append($parent, $column);

                this.sandbox.dom.addClass($parent, constants.maxSidebarClass);
            }
        },

        /**
         * Hides the sidebar column
         */
        hideColumn: function() {
            var $column = this.sandbox.dom.find(constants.columnSelector),
                $parent = this.sandbox.dom.parent($column);

            this.changeToFixedWidth();
            this.sandbox.dom.removeClass($parent, constants.visibleSidebarClass);
            this.sandbox.dom.addClass($parent, constants.noVisibleSidebarClass);
            this.sandbox.dom.addClass($column, constants.hiddenClass);
            this.sandbox.dom.trigger(this.sandbox.dom.window, 'resize');
        },

        /**
         * Shows the sidebar column
         */
        showColumn: function() {
            var $column = this.sandbox.dom.find(constants.columnSelector),
                $parent = this.sandbox.dom.parent($column);

            this.changeWidth(this.width);

            this.sandbox.dom.removeClass($parent, constants.noVisibleSidebarClass);
            this.sandbox.dom.addClass($parent, constants.visibleSidebarClass);
            this.sandbox.dom.removeClass($column, constants.hiddenClass);
            this.sandbox.dom.trigger(this.sandbox.dom.window, 'resize');
        },

        /**
         * Appends a widget to the sidebar
         * @param url {String} the url to load the widget from
         * @param $element {Object} a dom object to insert as a widget
         */
        appendWidget: function(url, $element) {
            if (!$element) {
                var $widget;
                this.loadWidget(url).then(function(widget) {
                    $widget = this.sandbox.dom.createElement(
                        this.sandbox.util.template(
                            widget, {translate: this.sandbox.translate}
                        ));
                    this.widgets.push({
                        url: url,
                        $el: $widget
                    });
                    this.sandbox.dom.append(this.$el, $widget);
                    this.sandbox.start($widget);
                }.bind(this));
            } else {
                this.showColumn();
                this.widgets.push({
                    url: null,
                    $el: $element
                });
                this.sandbox.dom.append(this.$el, $element);
            }
        },

        /**
         * Prepends a widget to the sidebar
         * @param url {String} the url to load the widget from
         * @param $element {Object} a dom object to insert as a widget
         */
        prependWidget: function(url, $element) {
            if (!$element) {
                var $widget;
                this.loadWidget(url).then(function(widget) {
                    $widget = this.sandbox.dom.createElement(
                        this.sandbox.util.template(
                            widget, {translate: this.sandbox.translate}
                        ));
                    this.widgets.unshift({
                        url: url,
                        $el: $widget
                    });
                    this.sandbox.dom.prepend(this.$el, $widget);
                    this.sandbox.start($widget);
                }.bind(this));
            } else {
                this.showColumn();
                this.widgets.push({
                    url: null,
                    $el: $element
                });
                this.sandbox.dom.prepend(this.$el, $element);
            }
        },

        /**
         * Sets the widget to the sidebar. deletes all other widgets
         * @param url {String} the url to load the widget from
         * @param $element {Object} a dom object to insert as a widget
         */
        setWidget: function(url, $element) {
            if (!$element) {
                // only load widget if sidebar-content changes
                if (this.widgets.length !== 1 || this.widgets[0].url !== url) {
                    var $widget;
                    this.emptySidebar(false);
                    this.loadWidget(url).then(function(widget) {
                        if (widget === undefined || widget === '') {
                            this.sandbox.dom.css(this.$el, 'display', 'none');
                            return;
                        }

                        $widget = this.sandbox.dom.createElement(
                            this.sandbox.util.template(
                                widget, {translate: this.sandbox.translate}
                            ));
                        this.widgets.push({
                            url: url,
                            $el: $widget
                        });
                        this.sandbox.dom.append(this.$el, $widget);
                        this.sandbox.start(this.$el, $widget);

                        this.sandbox.dom.css(this.$el, 'display', 'block');
                    }.bind(this));
                }
            } else {
                $element = $($element);
                this.emptySidebar(true);
                this.showColumn();
                this.widgets.push({
                    url: null,
                    $el: $element
                });
                this.sandbox.dom.append(this.$el, $element);
                this.sandbox.start(this.$el);
                this.sandbox.dom.css(this.$el, 'display', 'block');
            }
        },

        /**
         * Loads the content of a widget
         * @param url {String} the url to load the widget from
         */
        loadWidget: function(url) {
            var def = this.sandbox.data.deferred();
            this.showColumn();
            this.startLoader();
            this.sandbox.util.load(url, null, 'html').then(function(widget) {
                this.stopLoader();
                def.resolve(widget);
            }.bind(this));
            return def.promise();
        },

        /**
         * Empties the sidebar
         */
        emptySidebar: function(noHide) {
            while (this.widgets.length > 0) {
                this.sandbox.stop(this.widgets[0].$el);
                this.sandbox.dom.remove(this.widgets[0].$el);
                this.widgets.splice(0, 1);
            }
            if (noHide !== true) {
                this.hideColumn();
            }
        },

        /**
         * Starts a loader for the sidebar
         */
        startLoader: function() {
            var $container = this.sandbox.dom.createElement('<div class="' + constants.loaderClass + '"/>');
            this.sandbox.dom.append(this.$el, $container);
            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: $container,
                        size: '100px',
                        color: '#e4e4e4'
                    }
                }
            ]);
        },

        /**
         * Stops the sidebar loader
         */
        stopLoader: function() {
            this.sandbox.stop(this.$find('.' + constants.loaderClass));
        }
    };
});
