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
        header: function() {
            return {
                noBack: true,
                title: this.data.title,
                tabs: {
                    url: '/admin/content-navigations?alias=media',
                },
                toolbar: {
                    buttons: {
                        editCollection: {},
                        moveCollection: {},
                        deleteCollection: {}
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
            CollectionManager.loadOrNew(this.options.id, UserSettingsManager.getMediaLocale()).then(function(data) {
                promise.resolve(data);
            });
            return promise;
        },

        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            UserSettingsManager.setLastVisitedCollection(this.data.id);

            // handle data-navigation
            var url = '/admin/api/collections/' + this.data.id + '?depth=1&sortBy=title&locale=' + UserSettingsManager.getMediaLocale();
            this.sandbox.emit('husky.data-navigation.collections.set-url', url);
            this.sandbox.emit('husky.navigation.select-id', 'collections-edit', {dataNavigation: {url: url}});

            this.bindCustomEvents();
            this.bindOverlayEvents();
            this.bindManagerEvents();
        },

        bindCustomEvents: function() {
            // change the editing language
            this.sandbox.on('sulu.header.language-changed', function(locale) {
                UserSettingsManager.setMediaLocale(locale.id);
                MediaRouter.toCollection(this.data.id);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.edit-collection', function(locale) {
                OverlayManager.startEditCollectionOverlay(this.sandbox, this.data.id, UserSettingsManager.getMediaLocale());
            }.bind(this));

            this.sandbox.on('sulu.toolbar.move-collection', function(locale) {
                OverlayManager.startMoveCollectionOverlay(this.sandbox, this.data.id, UserSettingsManager.getMediaLocale());
            }.bind(this));

            this.sandbox.on('sulu.toolbar.delete-collection', function(locale) {
                this.deleteCollection();
            }.bind(this));
        },

        bindOverlayEvents: function() {
            // chose collection to move collection in collection-select overlay
            this.sandbox.on('sulu.collection-select.move-collection.selected', this.moveCollection.bind(this));
        },

        bindManagerEvents: function() {
            this.sandbox.on('sulu.medias.collection.saved', function(){
                // todo: change title of component
            }.bind(this));

            this.sandbox.on('sulu.medias.collection.deleted', function() {
                if (!!this.data._embedded.parent) {
                    MediaRouter.toCollection(this.data._embedded.parent.id);
                } else {
                    MediaRouter.toRoot();
                }
            }.bind(this));
        },

        /**
         * Deletes the current collection
         */
        deleteCollection: function() {
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!!confirmed) {
                    CollectionManager.delete(this.data.id);
                }
            }.bind(this));
        },

        /**
         * emit events to move collection
         * @param collection
         */
        moveCollection: function(parentCollection) {
            CollectionManager.move(this.data.id, parentCollection.id).then(function() {
                MediaRouter.toCollection(this.data.id);
            }.bind(this));
        }
    };
});
