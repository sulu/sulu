/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class Overlay
 * @constructor
 *
 * @params {Object} [options] Configuration object
 */

define(function() {

    'use strict';

    return {

        events: {
            names: {
                initialized: { postFix: 'initialized' },
                canceled: { postFix: 'canceled'},
                confirmed: { postFix: 'confirmed'},
                show: { postFix: 'show', type: 'on'},
                showError: { postFix: 'show-error', type: 'on' },
                showWarning: { postFix: 'show-warning', type: 'on' }
            },
            namespace: 'sulu.overlay.'
        },

        /**
         * Initialize the component
         */
        initialize: function() {
            this.bindCustomEvents();

            this.events.initialized();
        },

        /**
         * Bind custom-related Events
         */
        bindCustomEvents: function() {
            this.events.show(this.startOverlay.bind(this));
            this.events.showError(this.showError.bind(this));
            this.events.showWarning(this.showWarning.bind(this));
        },

        /**
         * Handler for the show-error event
         */
        showError: function(title, message, cancelCallback, options) {
            this.startOverlay(this.sandbox.util.extend(true, {}, {
                title: this.sandbox.translate(title),
                message: this.sandbox.translate(message),
                closeCallback: cancelCallback,
                type: 'alert'
            }, options));
        },

        /**
         * Handler for the show-warning event
         */
        showWarning: function(title, message, cancelCallback, okCallback, options) {
            this.startOverlay(this.sandbox.util.extend(true, {}, {
                title: this.sandbox.translate(title),
                message: this.sandbox.translate(message),
                closeCallback: cancelCallback,
                okCallback: okCallback,
                type: 'alert'
            }, options));
        },

        /**
         * Starts the actual overlay-component
         */
        startOverlay: function(options) {
            var $element = this.sandbox.dom.createElement('<div/>'),
                defaultOptions;
            this.sandbox.dom.append(this.$el, $element);

            // default options for the overlay
            defaultOptions = {
                el: $element,
                openOnStart: true,
                removeOnClose: true,
                cancelCallback: function() {
                    this.events.canceled();
                }.bind(this),
                okCallback: function() {
                    this.events.confirmed();
                }.bind(this)
            };

            // extend the default-options with the passed ones
            options = this.sandbox.util.extend(true, {}, defaultOptions, options);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: options
                }
            ]);
        }
    };
});
