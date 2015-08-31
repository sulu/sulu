/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/sulumedia/collection-manager',
    'services/sulumedia/user-settings-manager',
    'services/sulumedia/media-router',
    'services/sulumedia/overlay-manager'], function(CollectionManager, UserSettingsManager, MediaRouter, OverlayManager) {

    'use strict';

    var defaults = {};

    return {
        view: true,

        header: function () {
            return {
                noBack: true,
                tabs: {
                    url: '/admin/content-navigations?alias=media',
                },
                toolbar: {
                    buttons: {
                        settings: {
                            options: {
                                dropdownItems: [
                                    {
                                        id: 'collection-edit',
                                        title: this.sandbox.translate('sulu.collection.edit'), //todo: add translations
                                        //callback: this.startMoveCollectionOverlay.bind(this) // todo: implement edit collection
                                    },
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
                        }
                    },
                    languageChanger: {
                        url: '/admin/api/localizations',
                        resultKey: 'localizations',
                        titleAttribute: 'localization',
                        preSelected: UserSettingsManager.getMediaLocale()
                    }
                }
            };
        },

        loadComponentData: function() {
            var promise = this.sandbox.data.deferred();
            CollectionManager.loadOrNew(this.options.id).then(function(data) {
                promise.resolve(data);
            });
            return promise;
        },

        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            UserSettingsManager.setLastVisitedCollection(this.data.id);

            // handle data-navigation
            var url = '/admin/api/collections/' + this.data.id + '?depth=1&sortBy=title';
            this.sandbox.emit('husky.data-navigation.collections.set-url', url);
            this.sandbox.emit('husky.navigation.select-id', 'collections-edit', {dataNavigation: {url: url}});

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            // change the editing language
            this.sandbox.on('sulu.header.language-changed', function(locale) {
                UserSettingsManager.setMediaLocale(locale.id);
                MediaRouter.toCollection(this.data.id);
            }.bind(this));
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
        }
    };
});
