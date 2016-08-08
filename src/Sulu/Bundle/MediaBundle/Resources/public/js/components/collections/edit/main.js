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
    'services/sulumedia/media-router',
    'services/sulumedia/overlay-manager',
    'sulusecurity/services/security-checker'
], function(CollectionManager, UserSettingsManager, MediaRouter, OverlayManager, SecurityChecker) {

    'use strict';

    var defaults = {};

    return {

        header: function() {
            var buttons = {
                edit: {
                    options: {
                        title: 'sulu.header.edit-collection',
                        dropdownItems: {}
                    }
                }
            };

            if (SecurityChecker.hasPermission(this.data, 'edit') && !this.data.locked) {
                buttons.edit.options.dropdownItems.editCollection = {};
                buttons.moveCollection = {};
            }

            if (SecurityChecker.hasPermission(this.data, 'delete') && !this.data.locked) {
                buttons.edit.options.dropdownItems.deleteCollection = {};
            }

            if (this.sandbox.util.isEmpty(buttons.edit.options.dropdownItems)) {
                delete buttons.edit;
            }

            if (SecurityChecker.hasPermission(this.data, 'security') && !this.data.locked) {
                buttons.permissionSettings = {};
            }

            return {
                title: this.data.title,

                noBack: true,
                tabs: {
                    url: '/admin/content-navigations?alias=media',
                    componentOptions: {
                        values: this.data
                    }
                },
                toolbar: {
                    buttons: buttons,
                    languageChanger: {
                        url: '/admin/api/localizations',
                        resultKey: 'localizations',
                        titleAttribute: 'localization',
                        preSelected: UserSettingsManager.getMediaLocale()
                    }
                }
            };
        },

        /**
         * loads the collection-data into this.data. is automatically executed before component initialization
         * @returns {*}
         */
        loadComponentData: function() {
            return CollectionManager.loadOrNew(this.options.id, UserSettingsManager.getMediaLocale());
        },

        /**
         * Initialize the component
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            UserSettingsManager.setLastVisitedCollection(this.data.id);

            this.sandbox.emit('husky.navigation.select-id', 'collections-edit', {
                dataNavigation: {
                    url: '/admin/api/collections/' + this.data.id + '?depth=1&sortBy=title'
                }
            });
            this.sandbox.emit('husky.data-navigation.collections.set-locale', UserSettingsManager.getMediaLocale());
            this.updateDataNavigationAddButton();

            this.bindCustomEvents();
            this.bindOverlayEvents();
            this.bindManagerEvents();
        },

        /**
         * Updates the state of the add button in the data navigation based on the security of the current collection.
         */
        updateDataNavigationAddButton: function() {
            if (SecurityChecker.hasPermission(this.data, 'add') && !this.data.locked) {
                this.sandbox.emit('husky.data-navigation.collections.add-button.show');
            } else {
                this.sandbox.emit('husky.data-navigation.collections.add-button.hide');
            }
        },

        /**
         * Bind header-toolbar related events
         */
        bindCustomEvents: function() {
            this.sandbox.on(
                'husky.data-navigation.collections.initialized',
                this.updateDataNavigationAddButton.bind(this)
            );

            // change the editing language
            this.sandbox.on('sulu.header.language-changed', function(locale) {
                UserSettingsManager.setMediaLocale(locale.id);
                MediaRouter.toCollection(this.data.id);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.edit-collection', function() {
                OverlayManager.startEditCollectionOverlay.call(
                    this, this.data.id, UserSettingsManager.getMediaLocale()
                );
            }.bind(this));

            this.sandbox.on('sulu.toolbar.move-collection', function() {
                OverlayManager.startMoveCollectionOverlay.call(
                    this, this.data.id, UserSettingsManager.getMediaLocale()
                );
            }.bind(this));

            this.sandbox.on('sulu.toolbar.delete-collection', this.deleteCollection.bind(this));

            this.sandbox.on('sulu.toolbar.collection-permissions', function() {
                OverlayManager.startPermissionSettingsOverlay.call(
                    this,
                    this.data.id,
                    'Sulu\\Bundle\\MediaBundle\\Entity\\Collection', // todo: remove static string
                    "sulu.media.collections" // todo: remove static string
                );
            }.bind(this));

            this.sandbox.on('sulu.medias.collection.get-data', function(callback) {
                // deep copy of object
                callback(this.sandbox.util.deepCopy(this.data));
            }.bind(this));
        },

        /**
         * Bind overlay related events
         */
        bindOverlayEvents: function() {
            // chose collection to move collection in collection-select overlay
            this.sandbox.on('sulu.collection-select.move-collection.selected', this.moveCollection.bind(this));
        },

        /**
         * Bind data-management related events
         */
        bindManagerEvents: function() {
            this.sandbox.on('sulu.medias.collection.saved', function(id, collection) {
                if (!collection.locale || collection.locale === UserSettingsManager.getMediaLocale()) {
                    this.data = collection;
                    this.sandbox.emit('sulu.header.saved', this.data);
                }
            }.bind(this));

            this.sandbox.on('sulu.medias.collection.deleted', function() {
                var parentId = (!!this.data._embedded.parent) ? this.data._embedded.parent.id : null;
                this.sandbox.emit('husky.data-navigation.collections.reload');
                this.sandbox.emit('husky.data-navigation.collections.select', parentId);
                MediaRouter.toCollection(parentId);
            }.bind(this));
        },

        /**
         * Show confirmation dialog and delete collection if confirmed
         */
        deleteCollection: function() {
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!!confirmed) {
                    CollectionManager.delete(this.data.id);
                }
            }.bind(this), 'sulu.header.delete-collection');
        },

        /**
         * Move current collection into given parent collection
         * @param parentCollection
         */
        moveCollection: function(parentCollection) {
            CollectionManager.move(this.data.id, parentCollection.id, UserSettingsManager.getMediaLocale())
                .then(function() {
                    this.sandbox.emit('husky.data-navigation.collections.reload');
                    this.sandbox.emit('husky.data-navigation.collections.select', parentCollection.id);
                    MediaRouter.toCollection(this.data.id);
                }.bind(this));
        }
    };
});
