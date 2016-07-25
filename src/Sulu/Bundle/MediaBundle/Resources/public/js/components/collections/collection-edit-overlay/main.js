/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulumedia/collection-manager'], function(CollectionManager) {

    'use strict';

    var namespace = 'sulu.collection-edit.',

        defaults = {
            instanceName: ''
        },

        constants = {
            editFormSelector: '#collection-settings'
        },

        /**
         * raised when the overlay get closed
         * @event sulu.collection-edit.closed
         */
        CLOSED = function() {
            return createEventName.call(this, 'closed');
        },

        /**
         * raised when component is initialized
         * @event sulu.collection-edit.closed
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
            '/admin/media/template/collection/settings'
        ],

        /**
         * Initializes the overlay component
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {
                locale: this.sandbox.sulu.getDefaultContentLocale()
            }, defaults, this.options);

            if (!this.options.collectionId) {
                throw new Error('collection-id is not defined');
            }

            this.bindEvents();
            CollectionManager.loadOrNew(this.options.collectionId, this.options.locale).then(function(collection) {
                this.data = collection;
                this.openOverlay();
            }.bind(this));

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Bind overlay-related events
         */
        bindEvents: function() {
            this.sandbox.once('husky.overlay.edit-collection.opened', function() {
                this.sandbox.start(constants.editFormSelector);
                this.sandbox.form.create(constants.editFormSelector);
                this.sandbox.form.setData(constants.editFormSelector, this.data);
                $(constants.editFormSelector + ' input').first().focus();

                this.sandbox.once('husky.overlay.edit-collection.language-changed', function(locale) {
                    this.languageChanged(locale);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Opens the overlay to edit the collection
         */
        openOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.sandbox.translate('sulu.media.edit-collection'),
                        instanceName: 'edit-collection',
                        data: this.renderTemplate('/admin/media/template/collection/settings'),
                        okCallback: this.okCallback.bind(this),
                        cancelCallback: function() {
                            this.sandbox.stop();
                        }.bind(this),
                        openOnStart: true,
                        removeOnClose: true,
                        closeIcon: '',
                        propagateEvents: false,
                        languageChanger: {
                            locales: this.sandbox.sulu.locales,
                            preSelected: this.options.locale
                        }
                    }
                }
            ]);
        },

        /**
         * Validates the overlay form-data and saves collection if form-data is valid
         * @returns {boolean} false if form-data is not valid
         */
        okCallback: function() {
            if (this.sandbox.form.validate(constants.editFormSelector)) {
                this.saveCollection();
                this.sandbox.stop();
            } else {
                return false;
            }
        },

        /**
         * Save collection if form-data is valid and something was changed
         * @returns promise
         */
        saveCollection: function() {
            var promise = $.Deferred();

            // validate form-data
            if (this.sandbox.form.validate(constants.editFormSelector)) {
                var formData = this.sandbox.form.getData(constants.editFormSelector),
                    collectionData = this.sandbox.util.extend(true, {}, this.data, formData);

                // check if form-data is different to source-collection
                if (JSON.stringify(this.data) !== JSON.stringify(collectionData)) {
                    collectionData.locale = this.options.locale;
                    collectionData.parent = (!!this.data._embedded.parent) ? this.data._embedded.parent.id : null;

                    CollectionManager.save(collectionData).then(function(collection) {
                        promise.resolve();
                    }.bind(this));
                } else {
                    promise.resolve();
                }
            } else {
                promise.resolve();
            }

            return promise;
        },

        /**
         * Change language of the overlay by restarting it
         * @param locale
         */
        languageChanged: function(locale) {
            this.saveCollection().then(function() {
                this.sandbox.stop(this.$find('*'));
                this.options.locale = locale;
                this.initialize();
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
