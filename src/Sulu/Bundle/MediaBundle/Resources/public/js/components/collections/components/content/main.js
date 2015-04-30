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

    var namespace = 'sulu.media.collections-edit.',

        /**
         * sets the locale
         * @event sulu.media.collections.collection-list
         * @param {String} the new locale
         */
        SET_LOCALE = function() {
            return createEventName.call(this, 'set-locale');
        },

        /**
         * sets the locale
         * @event sulu.media.collections.collection-list
         * @param {Function} the callback to pass the locale to
         */
        GET_LOCALE = function() {
            return createEventName.call(this, 'get-locale');
        },

        /** returns normalized event names */
        createEventName = function(postFix) {
            return namespace + postFix;
        };

    return {

        header: function() {
            // init locale
            this.locale = this.sandbox.sulu.user.locale;

            return {
                tabs: {
                    url: '/admin/content-navigations?alias=media'
                },
                toolbar: {
                    template: [
                        {
                            id: 'settings',
                            icon: 'gear',
                            position: 30,
                            items: [
                                {
                                    id: 'collection-move',
                                    title: this.sandbox.translate('sulu.collection.move'),
                                    callback: this.startMoveCollectionOverlay.bind(this)
                                },
                                {
                                    id: 'delete',
                                    title: this.sandbox.translate('sulu.collections.delete-collection'),
                                    callback: this.deleteCollection.bind(this)
                                }
                            ]
                        }
                    ],
                    parentTemplate: 'save',
                    languageChanger: {
                        url: '/admin/api/localizations',
                        resultKey: 'localizations',
                        titleAttribute: 'localization',
                        preSelected: this.locale
                    }
                },
                noBack: true
            };
        },

        /**
         * Deletes the current collection
         */
        deleteCollection: function() {
            this.sandbox.emit('sulu.media.collections.delete-collection', this.options.id);
        },

        /**
         * starts overlay for collection media
         */
        startMoveCollectionOverlay: function() {
            this.sandbox.emit('sulu.media.collection-select.move-collection.open');
        },

        /**
         * emit events to move collection
         * @param collection
         */
        moveCollection: function(collection) {
            this.sandbox.emit('sulu.media.collections.move', this.options.id, collection,
                function() {
                    var url = '/admin/api/collections/' + this.options.id + '?depth=1&sortBy=title';

                    this.sandbox.emit('husky.data-navigation.collections.set-url', url);
                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.collection-move-desc', 'labels.success');
                }.bind(this)
            );

            this.sandbox.emit('sulu.media.collection-select.move-collection.restart');
            this.sandbox.emit('sulu.media.collection-select.move-collection.close');
        },

        initialize: function() {
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on(SET_LOCALE.call(this), this.setLocale.bind(this));
            this.sandbox.on(GET_LOCALE.call(this), this.getLocale.bind(this));

            // move collection overlay
            this.sandbox.on('sulu.media.collection-select.move-collection.selected', this.moveCollection.bind(this));
        },

        setLocale: function(locale) {
            this.locale = locale;
        },

        getLocale: function(callback) {
            callback(this.locale);
        }
    };
});
