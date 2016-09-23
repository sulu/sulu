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

        collaboration: function() {
            if (!this.options.id) {
                return;
            }

            return {
                id: this.options.id,
                type: 'collection'
            };
        },

        header: function() {

            return {
                noBack: !this.data.id,
                tabs: {
                    url: '/admin/content-navigations?alias=media',
                    options: {
                        getData: function() {
                            return this.sandbox.util.deepCopy(this.data);
                        }.bind(this)
                    },
                    componentOptions: {
                        values: this.data
                    }
                },
                toolbar: {
                    buttons: this.getHeaderButtons(),
                    languageChanger: {
                        url: '/admin/api/localizations',
                        resultKey: 'localizations',
                        titleAttribute: 'localization',
                        preSelected: this.options.locale
                    }
                }
            };
        },

        getHeaderButtons: function() {
            var buttons = {
                add: {
                    options: {
                        title: 'sulu.media.add-collection'
                    }
                }
            };

            if (!!this.options.id) {
                buttons.edit = {
                    options: {
                        title: 'sulu.header.edit-collection',
                            dropdownItems: {}
                    }
                };

                if (SecurityChecker.hasPermission(this.data, 'edit') && !this.data.locked) {
                    buttons.edit.options.dropdownItems.editCollection = {};
                    buttons.edit.options.dropdownItems.moveCollection = {};
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
            }

            return buttons;
        },

        /**
         * loads the collection-data into this.data. is automatically executed before component initialization
         * @returns {*}
         */
        loadComponentData: function() {
            var whenDataLoaded = $.Deferred();

            if (!!this.options.id) {
                CollectionManager.load(this.options.id, this.options.locale)
                    .then(function(data) {
                        whenDataLoaded.resolve(data);
                    })
                    .fail(function() {
                        whenDataLoaded.reject();
                        UserSettingsManager.setLastVisitedCollection(null);
                        MediaRouter.toRootCollection();
                    });
            } else {
                // Data for the "root" collection
                return {
                    title: this.sandbox.translate('sulu.media.all-collections'),
                    hasSub: true
                };
            }

            return whenDataLoaded;
        },

        /**
         * Initialize the component
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            UserSettingsManager.setLastVisitedCollection(this.data.id);

            this.bindCustomEvents();
            this.bindOverlayEvents();
            this.bindManagerEvents();
        },

        /**
         * Bind header-toolbar related events
         */
        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', this.routeToParent.bind(this));
            this.sandbox.on('sulu.toolbar.add', this.addHandler.bind(this));
            this.sandbox.on('sulu.toolbar.delete-collection', this.deleteCollection.bind(this));
            this.sandbox.on('sulu.header.language-changed', this.languageChangedHandler.bind(this));

            this.sandbox.on('sulu.media.collection-create.created', function(collection) {
                MediaRouter.toCollection(collection.id, this.options.locale);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.edit-collection', function() {
                OverlayManager.startEditCollectionOverlay.call(this, this.data.id, this.options.locale);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.move-collection', function() {
                OverlayManager.startMoveCollectionOverlay.call(this, this.data.id, this.options.locale);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.collection-permissions', function() {
                OverlayManager.startPermissionSettingsOverlay.call(
                    this,
                    this.data.id,
                    'Sulu\\Bundle\\MediaBundle\\Entity\\Collection', // todo: remove static string
                    "sulu.media.collections" // todo: remove static string
                );
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
            this.sandbox.on('sulu.medias.collection.saved', this.savedHandler.bind(this));
            this.sandbox.on('sulu.medias.collection.deleted', this.routeToParent.bind(this));
        },

        /**
         * Handler for the saved event.
         *
         * @param id The id of the collection which got saved
         * @param {Object} collection The new collection object
         */
        savedHandler: function(id, collection) {
            if (!collection.locale || collection.locale === this.options.locale) {
                this.data = collection;
                this.sandbox.emit('sulu.header.saved', this.data);
            }
        },

        /**
         * Handler for the add event. Starts the add overlay to add a collection
         */
        addHandler: function() {
            OverlayManager.startCreateCollectionOverlay.call(this, this.data);
        },

        /**
         * Routes to the parent collection
         */
        routeToParent: function() {
            if (!!this.data._embedded.parent) {
                MediaRouter.toCollection(this.data._embedded.parent.id, this.options.locale);
            } else {
                MediaRouter.toRootCollection(this.options.locale);
            }
        },

        /**
         * Handles the change of the language dropdown.
         *
         * @param {Object} locale The new locale
         */
        languageChangedHandler: function(locale) {
            UserSettingsManager.setMediaLocale(locale.id);
            if (!!this.data.id) {
                MediaRouter.toCollection(this.data.id, locale.id);
            } else {
                MediaRouter.toRootCollection(locale.id);
            }
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
         * Move current collection into given parent collection.
         *
         * @param parentCollection
         */
        moveCollection: function(parentCollection) {
            CollectionManager.move(this.data.id, parentCollection.id, this.options.locale).then(function() {
                MediaRouter.toCollection(this.data.id, this.options.locale);
            }.bind(this));
        }
    };
});
