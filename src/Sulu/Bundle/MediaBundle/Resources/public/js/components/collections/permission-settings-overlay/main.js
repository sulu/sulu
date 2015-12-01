/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var namespace = 'sulu.permission-settings.',

        defaults = {
            instanceName: '',
            type: '',
            securityContext: ''
        },

        constants = {
            permissionContentClass: 'permission-content'
        },

        /**
         * raised when the overlay get closed
         * @event sulu.collection-add.closed
         */
        CLOSED = function() {
            return createEventName.call(this, 'closed');
        },

        /**
         * raised when component is initialized
         * @event sulu.collection-add.closed
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /** returns normalized event names */
        createEventName = function(postFix) {
            return namespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {

        /**
         * Initializes the overlay component
         */
        initialize: function() {
            if (!this.options.id) {
                throw new Error('id is not defined');
            }

            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.bindEvents();
            this.openOverlay();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Bind overlay-related events
         */
        bindEvents: function() {
            this.sandbox.once('husky.overlay.permission-settings.opened', function() {
                this.startPermissionContent();
            }.bind(this));
        },

        /**
         * Opens the overlay to create a new collection
         */
        openOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.sandbox.translate('security.roles.permissions'),
                        instanceName: 'permission-settings',
                        data: '<div class="' + constants.permissionContentClass + '"/>',
                        okCallback: this.savePermissionSettings.bind(this),
                        cancelCallback: function() {
                            this.sandbox.stop();
                        }.bind(this),
                        openOnStart: true,
                        removeOnClose: true,
                        propagateEvents: false
                    }
                }
            ]);
        },

        startPermissionContent: function() {
            this.sandbox.start([
                {
                    name: 'permission-tab@sulusecurity',
                    options: {
                        el: '.' + constants.permissionContentClass,
                        id: this.options.id,
                        type: this.options.type,
                        securityContext: this.options.securityContext,
                        inOverlay: true
                    }
                }
            ]);
        },

        /**
         * Creates a new Collections with the form-data of the overlay
         * @returns {Boolean} returns false if form-data was not valid
         */
        savePermissionSettings: function() {
            this.sandbox.emit('sulu.permission-tab.save');

            this.sandbox.once('sulu.permission-tab.saved', function() {
                this.sandbox.stop();
            }.bind(this));

            this.sandbox.once('sulu.permission-tab.error', function() {
                this.sandbox.stop();
            }.bind(this));
        },

        /**
         * Called when component gets destroyed
         */
        destroy: function() {
            this.sandbox.emit(CLOSED.call(this));
        }
    };
});
