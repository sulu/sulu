/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulumedia/collection-manager',
    'services/sulumedia/user-settings-manager',
    'services/husky/mediator'
], function(CollectionManager, UserSettingsManager, Mediator) {

    'use strict';

    var namespace = 'sulu.collection-add.',

        defaults = {
            parent: null,
            instanceName: ''
        },

        constants = {
            newFormSelector: '#collection-new'
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

        templates: [
            '/admin/media/template/collection/new'
        ],

        /**
         * Initializes the overlay component
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.openOverlay();
            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Opens the overlay to create a new collection
         */
        openOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            // start form when overlay is opened
            this.sandbox.once('husky.overlay.add-collection.opened', function() {
                this.sandbox.start(constants.newFormSelector);
                this.sandbox.form.create(constants.newFormSelector);
                $(constants.newFormSelector + ' input').first().focus();
            }.bind(this));

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.sandbox.translate('sulu.media.add-collection'),
                        instanceName: 'add-collection',
                        data: this.renderTemplate('/admin/media/template/collection/new'),
                        okCallback: this.okCallback.bind(this),
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

        /**
         * Validates the overlay form-data and saves collection if form-data is valid
         * @returns {boolean} false if form-data is not valid
         */
        okCallback: function() {
            if (this.sandbox.form.validate(constants.newFormSelector)) {
                this.addCollection();
                this.sandbox.stop();
            } else {
                return false;
            }
        },

        /**
         * Creates a new Collections with the form-data of the overlay
         * @returns {Boolean} returns false if form-data was not valid
         */
        addCollection: function() {
            var collection = this.sandbox.form.getData(constants.newFormSelector);
            collection.parent = this.options.parent;
            collection.locale = UserSettingsManager.getMediaLocale();

            CollectionManager.save(collection).done(function(collection) {
                Mediator.emit('sulu.media.collection-create.created', collection);
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
