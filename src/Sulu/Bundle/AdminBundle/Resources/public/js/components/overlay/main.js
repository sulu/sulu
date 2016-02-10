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

    var createEventName = function(postfix) {
            return 'sulu.overlay.' + postfix;
        },

        /**
         * trigger after initialization has finished
         *
         * @event sulu.overlay.initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * triggered in the cancel-default-callback
         *
         * @event sulu.overlay.canceled
         */
        CANCELED = function() {
            return createEventName.call(this, 'canceled');
        },

        /**
         * triggered in the ok-default-callback
         *
         * @event sulu.overlay.canceled
         */
        CONFIRMED = function() {
            return createEventName.call(this, 'confirmed');
        },

        /**
         * listens on and shows an error overlay
         *
         * @event sulu.overlay.show-error
         * @param {String} title of the the overlay
         * @param {String} message of the overlay
         * @param {String} callback for cancel-button
         * @param {Object} overlay-options object - optional
         */
        SHOW_ERROR = function() {
            return createEventName.call(this, 'show-error');
        },

        /**
         * listens on and shows an warning overlay (ok/cancel)
         *
         * @event sulu.overlay.show-warning
         * @param {String} title of the the overlay
         * @param {String} message of the overlay
         * @param {String} callback for cancel-button
         * @param {String} callback for ok-button
         * @param {Object} overlay-options object - optional
         */
        SHOW_WARNING = function() {
            return createEventName.call(this, 'show-warning');
        };

    return {

        /**
         * Initialize the component
         */
        initialize: function() {
            this.bindCustomEvents();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Bind custom-related Events
         */
        bindCustomEvents: function() {
            this.sandbox.on(SHOW_ERROR.call(this), this.showError.bind(this));
            this.sandbox.on(SHOW_WARNING.call(this), this.showWarning.bind(this));
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
                cancelCallback: function() {
                    this.sandbox.emit(CANCELED.call(this));
                }.bind(this),
                okCallback: function() {
                    this.sandbox.emit(CONFIRMED.call(this));
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
