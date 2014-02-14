/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class Labels
 * @constructor
 *
 */

define([], function() {

    'use strict';

    var eventNamespace = 'sulu.labels.',

    /**
     * show an error label
     *
     * @event sulu.labels.error.show
     * @param {String} description The description of the label
     * @param {String} title (optional) The title of the label
     */
    SHOW_ERROR = function() {
        return createEventName.call(this, 'error.show')
    },

    /**
     * show an error label
     *
     * @event sulu.labels.error.show
     * @param {String} description The description of the label
     * @param {String} title (optional) The title of the label
     */
    SHOW_WARNING = function() {
        return createEventName.call(this, 'warning.show')
    },

    /**
     * show an error label
     *
     * @event sulu.labels.error.show
     * @param {String} description The description of the label
     * @param {String} title (optional) The title of the label
     */
    SHOW_SUCCESS = function() {
        return createEventName.call(this, 'success.show')
    },

    /**
     * show an label and pass it your own config object
     *
     * @event sulu.labels.label.show
     * @param {Object} configs The config-object to pass to the component
     */
     SHOW_LABEL = function() {
        return createEventName.call(this, 'label.show')
     },

    createEventName = function(postFix) {
        return eventNamespace + postFix;
    };

    return {
        view: true,

        /**
         * Initialize the component
         */
        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.savedContentWidth = null;
            this.labelId = 0;
            this.resizeListener();

            this.bindDomEvents();
            this.bindCustomEvents();
        },

        /**
         * Bind DOM related events
         */
        bindDomEvents: function() {
            //todo improve responsivness
            this.sandbox.dom.on(this.sandbox.dom.window, 'resize', this.resizeListener.bind(this));
            this.sandbox.on('husky.navigation.size.change', this.navigationSizeChangeListener.bind(this));
            this.sandbox.on('husky.tabs.content.initialized', this.resizeListener.bind(this));
        },

        /**
         * Bind custom related events
         */
        bindCustomEvents: function() {
            this.sandbox.on(SHOW_ERROR.call(this), function(description, title) {
                this.showLabel('ERROR', description, title);
            }.bind(this));

            this.sandbox.on(SHOW_WARNING.call(this), function(description, title) {
                this.showLabel('WARNING', description, title);
            }.bind(this));

            this.sandbox.on(SHOW_SUCCESS.call(this), function(description, title) {
                this.showLabel('SUCCESS', description, title);
            }.bind(this));

            this.sandbox.on(SHOW_LABEL.call(this), function(configs) {
                configs['el'] = this.createLabelContainer();
                this.startLabelComponent(configs);
            }.bind(this));
        },

        /**
         * Makes sure labels-container always has the width of the content
         */
        resizeListener: function() {
            var contentWidth = this.sandbox.dom.width('main#content');

            if (this.savedContentWidth === null || this.savedContentWidth !== contentWidth) {
                this.sandbox.dom.width(this.$el, contentWidth);
                this.savedContentWidth = contentWidth;
            }
        },

        /**
         * Handles to left-margin if the navigations size changes
         * @param navSize
         */
        navigationSizeChangeListener: function(navSize) {
            this.sandbox.dom.css(this.$el, {'margin-left': navSize + 50 + 'px'});
        },

        /**
         * creates and returns containers for the labels. generates a unique id
         * @returns {*|HTMLElement}
         */
        createLabelContainer: function() {
            var container = this.sandbox.dom.createElement('<div id="sulu-labels-'+ this.labelId +'"/>');
            this.labelId = this.labelId + 1;
            this.sandbox.dom.append(this.$el, container);

            return container;
        },

        /**
         * Shows a label
         * @param type
         * @param description
         * @param title
         */
        showLabel: function(type, description, title) {
            this.startLabelComponent({
                type: type,
                description: description,
                title: title,
                el: this.createLabelContainer()
            })
        },

        /**
         * Starts the husky component
         * @param configs
         */
        startLabelComponent: function(configs) {
            this.sandbox.start([{
                name: 'label@husky',
                options: configs
            }]);
        }
    };
});
