/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'config',
    'services/sulumedia/collection-manager',
    'services/sulumedia/media-manager',
    'services/sulumedia/media-router',
    'services/sulumedia/user-settings-manager',
    'services/sulumedia/overlay-manager'
], function(
    Config,
    CollectionManager,
    MediaManager,
    MediaRouter,
    UserSettingsManager,
    OverlayManager
) {

    'use strict';

    var defaults = {
            instanceName: 'collection'
        },

        constants = {
            hideToolbarClass: 'toolbar-hidden',
            datagridSelector: '.datagrid-container',
            collectionTitleSelector: '.content-title h2'
        };

    return {

        layout: {
            content: {
                width: 'max'
            }
        },

        /**
         * Initialize the component
         */
        initialize: function() {
            var $collectionView = $('<div class="collection-view"/>');

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.data = this.options.getData();

            this.$el.append($collectionView);

            this.sandbox.start([{
                name: 'collection-view@sulumedia',
                options: {
                    el: $collectionView,
                    data: this.data,
                    locale: this.options.locale,
                    instanceName: this.options.instanceName,
                    assetActions: [
                        'fa-pencil',
                        {
                            icon: 'fa-crop',
                            type: 'image',
                            action: function(media) {
                                this.editMedia(media.id, true);
                            }.bind(this)
                        }
                    ]
                }
            }]);

            this.bindCollectionViewEvents();
            this.bindManagerEvents();
            this.bindOverlayEvents();

            if (this.options.editId) {
                this.editMedia(this.options.editId);
            }
        },

        bindCollectionViewEvents: function() {
            this.sandbox.on('sulu.collection-view.' + this.options.instanceName + '.folder.clicked', function(id) {
                MediaRouter.toCollection(id, this.options.locale);
            }.bind(this));

            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.folder.add-clicked',
                OverlayManager.startCreateCollectionOverlay.bind(this, this.data)
            );

            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.asset.edit-clicked',
                this.editMedias,
                this
            );

            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.asset.delete-clicked',
                this.deleteMedia,
                this
            );

            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.asset.move-clicked',
                function() {
                    OverlayManager.startMoveMediaOverlay.call(this, this.data.id, this.options.locale);
                }.bind(this)
            );

            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.asset.clicked',
                this.editMedia,
                this
            );
            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.folder.breadcrumb-clicked',
                this.breadcrumbClickHandler,
                this
            );
        },

        /**
         * Bind data management related events
         */
        bindManagerEvents: function() {
            // change saved medias in datagrid
            this.sandbox.on('sulu.medias.media.saved', function(id, media) {
                // change medias if media is saved without locale or locale is current media-locale
                if (!media.locale || media.locale === this.options.locale) {
                    this.sandbox.emit(
                        'husky.datagrid.' + this.options.instanceName + '.records.change',
                        this.sandbox.util.extend(true, {}, media, {
                            type: (!!media.type.name) ? media.type.name : media.type
                        })
                    );
                }
            }.bind(this));

            this.sandbox.on('sulu.medias.media.deleted', function(id) {
                this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.record.remove', id);
            }.bind(this));


            this.sandbox.on('sulu.medias.media.moved', function(id) {
                this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.record.remove', id);
            }.bind(this));
        },

        /**
         * Bind overlay related events
         */
        bindOverlayEvents: function() {
            this.sandbox.on('sulu.collection-select.move-media.selected', this.moveMedia, this);

            this.sandbox.on('sulu.medias.collection.saved', this.savedHandler, this);
        },

        savedHandler: function(id, collection) {
            if (!collection.locale || collection.locale === this.options.locale) {
                $(constants.collectionTitleSelector).text(collection.title)
            }
        },

        /**
         * Move selected medias to given collection
         * @param collection
         */
        moveMedia: function(collection) {
            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.items.get-selected', function(ids) {
                MediaManager.move(ids, collection.id, this.options.locale);

                // If the media was moved to a child collection, update the children tiles
                this.sandbox.emit('husky.datagrid.' + this.data.id + '.children.records.get',
                    function(childCollections) {
                        childCollections.forEach(function(childCollection) {
                            // update the mediaCount property of a child collection in the datagrid
                            // if media got moved to it
                            if (childCollection.id === collection.id) {
                                this.sandbox.emit('husky.datagrid.' + this.data.id + '.children.records.change',
                                    _.extend(childCollection, {
                                        mediaCount: childCollection.mediaCount + ids.length
                                    })
                                );
                            }
                        }.bind(this));
                    }.bind(this)
                );
            }.bind(this));
        },

        /**
         * Edits all selected medias
         */
        editMedias: function(mediaIds) {
            OverlayManager.startEditMediaOverlay.call(this, mediaIds, this.options.locale);
        },

        /**
         * Edit given media
         */
        editMedia: function(mediaId, crop) {
            var startingSlide = 'edit';
            if (crop === true) {
                startingSlide = 'crop';
            }
            OverlayManager.startEditMediaOverlay.call(this, [mediaId], this.options.locale, startingSlide);
        },

        /**
         * Show confimation dialog and delete all selected medias if confirmed
         */
        deleteMedia: function(mediaIds) {
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!!confirmed) {
                    MediaManager.delete(mediaIds);
                }
            }.bind(this));
        },

        /**
         * Handles the click on a breadcrumb item.
         *
         * @param {Object} crumb The data of the clicked breadcrumb item.
         */
        breadcrumbClickHandler: function(crumb) {
            if (!!crumb.data.id) {
                MediaRouter.toCollection(crumb.data.id, this.options.locale);
            } else {
                MediaRouter.toRootCollection(this.options.locale);
            }
        }
    };
});
