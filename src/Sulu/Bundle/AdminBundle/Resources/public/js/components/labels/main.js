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

    var defaults = {
        navigationLabelsSelector: '.sulu-navigation-labels'
    },

    eventNamespace = 'sulu.labels.',

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
         * Initializes the component
         */
        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.labelId = 0;
            this.labels = {};
            this.labels.SUCCESS = {};
            this.labels.SUCCESS_ICON = {};
            this.labels.WARNING = {};
            this.labels.ERROR = {};
            this.labelsById = {};
            this.$navigationLabels = $(this.options.navigationLabelsSelector);
            this.bindCustomEvents();
        },

        /**
         * Bind custom related events
         */
        bindCustomEvents: function() {
            this.sandbox.on(SHOW_ERROR.call(this), function(description, title, id, autoVanish) {
                this.showLabel('ERROR', description, (title || 'labels.error'), id, false, autoVanish);
            }.bind(this));

            this.sandbox.on(SHOW_WARNING.call(this), function(description, title, id, autoVanish) {
                this.showLabel('WARNING', description, (title || 'labels.warning'), id, false, autoVanish);
            }.bind(this));

            this.sandbox.on(SHOW_SUCCESS.call(this), function(description, title, id, autoVanish) {
                this.showLabel('SUCCESS_ICON', description, (title || 'labels.success'), id, true, autoVanish);
            }.bind(this));

            this.sandbox.on(SHOW_LABEL.call(this), function(configs) {
                this.startLabelComponent(configs);
            }.bind(this));

            this.sandbox.on(LABELS_REMOVE.call(this), function() {
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
         * @oaram inNavigation {Boolean} iff true inserts the container in the navigation-column
         * @returns {*|HTMLElement}
         */
        createLabelContainer: function(id, inNavigation) {
            var $container = this.sandbox.dom.createElement('<div/>'),
                uniqueId;

            if (typeof id !== 'undefined') {
                uniqueId = id;
                //remove label with the same id
                this.removeLabelWithId(id);
            } else {
                this.labelId = this.labelId + 1;
                uniqueId = this.labelId;
            }

            this.sandbox.dom.attr($container, 'id', 'sulu-labels-' + uniqueId);
            this.sandbox.dom.attr($container, 'data-id', uniqueId);
            if (!!inNavigation) {
                this.$navigationLabels.prepend($container);
            } else {
                this.sandbox.dom.prepend(this.$el, $container);
            }

            return $container;
        },

        /**
         * Removes a label with a given id
         * @param id {String} The id of the label to delete
         */
        removeLabelWithId: function(id) {
            var label = this.labelsById[id];
            if (!!label) {
                delete this.labels[label.type][label.description];
                delete this.labelsById[id];
                this.sandbox.dom.remove(this.sandbox.dom.find("[data-id='"+ id +"']", this.$el));
            }
        },

        /**
         * Shows a label
         * @param type
         * @param description
         * @param title
         * @param id
         * @param inNavigation
         */
        showLabel: function(type, description, title, id, inNavigation, autoVanish) {
            id = id || ++this.labelId;
            if (!!this.labels[type][description]) {
                this.sandbox.emit('husky.label.' + this.labels[type][description] + '.refresh');
            } else {
                this.startLabelComponent({
                    type: type,
                    description: this.sandbox.translate(description),
                    title: this.sandbox.translate(title),
                    el: this.createLabelContainer(id, inNavigation),
                    instanceName: id,
                    autoVanish: autoVanish
                });
                this.labels[type][description] = id;
                this.labelsById[id] = {
                    type: type,
                    description: description
                };
                this.sandbox.once('husky.label.' + id + '.destroyed', function() {
                    this.removeLabelWithId(id);
                }.bind(this));
            }
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
