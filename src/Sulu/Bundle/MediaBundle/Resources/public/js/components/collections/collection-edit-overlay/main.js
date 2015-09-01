/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/sulumedia/collection-manager', 'services/sulumedia/overlay-manager'],
    function(CollectionManager, OverlayManager) {

    'use strict';

    var namespace = 'sulu.collection-edit.',

        defaults = {
            parent: null,
            instanceName: '',
        },

        constants = {
            editFormSelector: '#collection-settings'
        },

        /**
         * raised when the overlay get closed
         * @event sulu.media-edit.closed
         */
        CLOSED = function() {
            return createEventName.call(this, 'closed');
        },

        /**
         * raised when component is initialized
         * @event sulu.media-edit.closed
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
         * Initializes the collections list
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.bindEvents();
            CollectionManager.loadOrNew(this.options.collectionId, this.options.locale).then(function(collection) {
                this.data = collection;
                this.openOverlay();
            }.bind(this));

            this.sandbox.emit(INITIALIZED.call(this));
        },

        bindEvents: function() {
            this.sandbox.once('husky.overlay.edit-collection.opened', function() {
                this.sandbox.start(constants.editFormSelector);
                this.sandbox.form.create(constants.editFormSelector);
                this.sandbox.form.setData(constants.editFormSelector, this.data);

                this.sandbox.once('husky.overlay.edit-collection.language-changed', function(locale) {
                    this.languageChanged(locale);
                }.bind(this));

                this.sandbox.dom.one(constants.editFormSelector, 'keyup', function() {
                    this.sandbox.emit('husky.overlay.edit-collection.okbutton.activate');
                }.bind(this));
            }.bind(this));
        },

        /**
         * Opens a overlay for a new collection
         */
        openOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            this.$overlayContent = this.renderTemplate('/admin/media/template/collection/settings');

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.sandbox.translate('sulu.media.edit-collection'),
                        instanceName: 'edit-collection',
                        data: this.$overlayContent,
                        okCallback: function() {
                            if (this.sandbox.form.validate(constants.editFormSelector)) {
                                this.saveCollection();
                                this.sandbox.stop();
                            } else {
                                return false;
                            }
                        }.bind(this),
                        cancelCallback: function() {
                            this.sandbox.stop();
                        }.bind(this),
                        openOnStart: true,
                        removeOnClose: true,
                        closeIcon: '',
                        languageChanger: {
                            locales: this.sandbox.sulu.locales,
                            preSelected: this.options.locale
                        },
                        okInactive: true
                    }
                }
            ]);
        },

        /**
         * Adds a new collection the the list
         * @returns {Boolean} returns false if a new and unsafed collection exists
         */
        saveCollection: function() {
            var promise = $.Deferred();

            if (this.sandbox.form.validate(constants.editFormSelector)) {
                var collection = this.sandbox.form.getData(constants.editFormSelector);
                collection.locale = this.options.locale;
                collection.parent = (!!this.data._embedded.parent) ? this.data._embedded.parent.id : null;
                collection = this.sandbox.util.extend(true, {}, this.data, collection);

                CollectionManager.save(collection).then(function(collection) {
                    promise.resolve();
                }.bind(this));
            } else {
                promise.resolve();
            }

            return promise;
        },

        languageChanged: function(locale) {
            this.saveCollection().then(function() {
                this.sandbox.stop();
                OverlayManager.startEditCollectionOverlay(this.sandbox._parent, this.options.collectionId, locale);
            }.bind(this));
        },

        destroy: function() {
            this.sandbox.emit(CLOSED.call(this));
        }
    };
});
