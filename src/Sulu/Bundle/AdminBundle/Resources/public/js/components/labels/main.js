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
     * @param {String} id (optional) The id of the label
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
     * @param {String} id (optional) The id of the label
     */
    SHOW_SUCCESS = function() {
        return createEventName.call(this, 'success.show')
    },

    /**
     * show an label and pass it your own config object
     *
     * @event sulu.labels.label.show
     * @param {Object} configs The config-object to pass to the component
     * @param {String} id (optional) The id of the label
     */
     SHOW_LABEL = function() {
        return createEventName.call(this, 'label.show')
     },

    /**
     * removes all displayed labels
     *
     * @event sulu.labels.remove
     */
    LABELS_REMOVE = function() {
        return createEventName.call(this, 'remove')
    },

   /**
    * removes label with a given id
    *
    * @event sulu.labels.label.remove
    * @param {String} id The id of the label
    */
    LABEL_REMOVE = function() {
        return createEventName.call(this, 'label.remove')
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
            this.savedContentWidth = null;
            this.labelId = 0;
            this.resizeListener();

            this.bindDomEvents();
            this.bindCustomEvents();

            setTimeout(function() {
                this.sandbox.emit('sulu.labels.error.show', 'remove me later', 'Test error');
            }.bind(this), 300)
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
            this.sandbox.on(SHOW_ERROR.call(this), function(description, title, id) {
                this.showLabel('ERROR', description, title, id);
            }.bind(this));

            this.sandbox.on(SHOW_WARNING.call(this), function(description, title, id) {
                this.showLabel('WARNING', description, title, id);
            }.bind(this));

            this.sandbox.on(SHOW_SUCCESS.call(this), function(description, title, id) {
                this.showLabel('SUCCESS', description, title, id);
            }.bind(this));

            this.sandbox.on(SHOW_LABEL.call(this), function(configs, id) {
                configs['el'] = this.createLabelContainer(id);
                this.startLabelComponent(configs);
            }.bind(this));

            this.sandbox.on(LABELS_REMOVE.call(this), function() {
                this.removeLabels();
            }.bind(this));

            this.sandbox.on('sulu.router.navigate', function() {
                this.removeLabels();
            }.bind(this));

            this.sandbox.on(LABEL_REMOVE.call(this), function(id) {
                this.removeLabelWithId(id);
            }.bind(this));
        },

        /**
         * Removes all displayed labels
         */
        removeLabels: function() {
            this.sandbox.dom.html(this.$el, '');
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
        createLabelContainer: function(id) {
            var container = this.sandbox.dom.createElement('<div/>'),
                uniqueId;

            if (typeof id !== 'undefined') {
                uniqueId = id;
                //remove label with the same id
                this.removeLabelWithId(id);
            } else {
                this.labelId = this.labelId + 1;
                uniqueId = this.labelId;
            }

            this.sandbox.dom.attr(container, 'id', 'sulu-labels-' + uniqueId);
            this.sandbox.dom.attr(container, 'data-id', uniqueId);
            this.sandbox.dom.append(this.$el, container);

            return container;
        },

        /**
         * Removes a label with a given id
         * @param id {String} The id of the label to delete
         */
        removeLabelWithId: function(id) {
            this.sandbox.dom.remove(this.sandbox.dom.find("[data-id='"+ id +"']", this.$el));
        },

        /**
         * Shows a label
         * @param type
         * @param description
         * @param title
         */
        showLabel: function(type, description, title, id) {
            this.startLabelComponent({
                type: type,
                description: description,
                title: title,
                el: this.createLabelContainer(id)
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
