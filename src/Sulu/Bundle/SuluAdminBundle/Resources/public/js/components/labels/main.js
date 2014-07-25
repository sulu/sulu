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
     * error label event
     *
     * @event sulu.labels.error.show
     */
    SHOW_ERROR = function() {
        return createEventName.call(this, 'error.show');
    },

    /**
     * wrning label event
     *
     * @event sulu.labels.warning.show
     */
    SHOW_WARNING = function() {
        return createEventName.call(this, 'warning.show');
    },

    /**
     * success label event
     *
     * @event sulu.labels.success.show
     */
    SHOW_SUCCESS = function() {
        return createEventName.call(this, 'success.show');
    },

    /**
     * label event
     *
     * @event sulu.labels.label.show
     */
     SHOW_LABEL = function() {
        return createEventName.call(this, 'label.show');
     },

    /**
     * remove displayed labels event
     *
     * @event sulu.labels.remove
     */
    LABELS_REMOVE = function() {
        return createEventName.call(this, 'remove');
    },

   /**
    * remove label with a given id event
    *
    * @event sulu.labels.label.remove
    */
    LABEL_REMOVE = function() {
        return createEventName.call(this, 'label.remove');
    },

    createEventName = function(postFix) {
        return eventNamespace + postFix;
    };

    return {

        /**
         * Waits for the App-Component to start,
         * then continues with the initialization
         */
        initialize: function() {
            this.appStarted = false;
            this.sandbox.emit('sulu.app.has-started', function(hasStarted) {
                this.appStarted = hasStarted;
            }.bind(this));

            //if app-component is already started continue right ahead
            if (this.appStarted === true) {
                this.startComponent();
            } else {
                this.sandbox.on('sulu.app.initialized', this.startComponent.bind(this));
            }
        },

        /**
         * Starts the component
         */
        startComponent: function() {
            this.labelId = 0;

            this.bindCustomEvents();
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
                configs.el = this.createLabelContainer(id);
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
         * @oaram id
         */
        showLabel: function(type, description, title, id) {
            this.startLabelComponent({
                type: type,
                description: this.sandbox.translate(description),
                title: this.sandbox.translate(title),
                el: this.createLabelContainer(id)
            });
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
