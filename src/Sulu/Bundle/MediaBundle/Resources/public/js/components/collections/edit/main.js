/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/sulumedia/collection-manager',
    'services/sulumedia/media-manager',
    'services/sulumedia/user-settings-manager',
    'services/sulumedia/media-router',
    'services/sulumedia/overlay-manager'], function(CollectionManager, MediaManager, UserSettingsManager, MediaRouter, OverlayManager) {

    'use strict';

    var defaults = {
            data: {},
            instanceName: 'collection'
        },

        constants = {
            dropzoneSelector: '.dropzone-container',
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container',
            moveSelector: '.move-container',
        };

    return {
        view: true,

        header: function () {
            return {
                noBack: true,
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

        layout: {
            navigation: {
                collapsed: true
            },
            content: {
                width: 'max'
            }
        },

        templates: [
            '/admin/media/template/collection/files'
        ],

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

            this.bindDatagridEvents();
            this.bindDropzoneEvents();
            this.bindOverlayEvents();
            this.bindManagerEvents();
            this.bindCustomEvents();

            this.render();
        },

        /**
         * Deconstructor
         */
        destroy: function() {
            this.sandbox.stop(constants.dropzoneSelector);
        },

        bindDatagridEvents: function() {
            // change datagrid to table
            this.sandbox.on('sulu.toolbar.change.table', function() {
                UserSettingsManager.setMediaListView('table');
                this.sandbox.emit('husky.datagrid.view.change', 'table');
            }.bind(this));

            // change datagrid to masonry
            this.sandbox.on('sulu.toolbar.change.masonry', function() {
                UserSettingsManager.setMediaListView('decorators/masonry');
                this.sandbox.emit('husky.datagrid.view.change', 'decorators/masonry');
            }.bind(this));

            // download media
            this.sandbox.on('husky.datagrid.download-clicked', function(id) {
                MediaManager.loadOrNew(id).then(function(media) {
                    this.sandbox.dom.window.location.href = media.versions[media.version].url;
                }.bind(this));
            }.bind(this));

            // toggle edit button
            this.sandbox.on('husky.datagrid.number.selections', function(selectedItems) {
                var string = (selectedItems > 0) ? 'enable' : 'disable';
                this.sandbox.emit('husky.toolbar.' + this.options.instanceName + '.item.' + string, 'media-move', false);
                this.sandbox.emit('husky.toolbar.' + this.options.instanceName + '.item.' + string, 'edit', false);
                this.sandbox.emit('husky.toolbar.' + this.options.instanceName + '.item.' + string, 'delete', false);
            }.bind(this));
        },

        bindDropzoneEvents: function() {
            this.sandbox.on('husky.dropzone.' + this.options.instanceName + '.success', function(file, mediaResponse) {
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                this.sandbox.emit('husky.datagrid.records.add', [mediaResponse]);
            }, this);
        },

        bindManagerEvents: function() {
            //remove from datagrid
            this.sandbox.on('sulu.medias.media.deleted', function(id) {
                this.sandbox.emit('husky.datagrid.record.remove', id);
            }.bind(this));

            this.sandbox.on('sulu.medias.media.moved', function(id) {
                this.sandbox.emit('husky.datagrid.record.remove', id);
                this.sandbox.emit('husky.data-navigation.collections.reload');
            }.bind(this));
        },

        bindOverlayEvents: function() {
            // move media
            this.sandbox.on('sulu.media.collection-select.move-media.selected', this.moveMedia.bind(this));

            // move collection
            this.sandbox.on('sulu.media.collection-select.move-collection.selected', this.moveCollection.bind(this));

            // update datagrid if media-edit is finished
            this.sandbox.on('sulu.media.collections.save-media', this.updateGrid.bind(this));

            // unlock the dropzone pop-up if the media-edit overlay was closed
            this.sandbox.on('sulu.media-edit.closed', function() {
                //this.sandbox.emit('husky.datagrid.items.deselect');
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.unlock-popup');
            }.bind(this));
        },

        bindCustomEvents: function() {
            // change the editing language
            this.sandbox.on('sulu.header.language-changed', function(locale) {
                UserSettingsManager.setMediaLocale(locale.id);
                MediaRouter.toCollection(this.data.id);
            }.bind(this));
            // move collection overlay

            // delete a media
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));
            // edit media
            this.sandbox.on('sulu.list-toolbar.edit', this.editMedia.bind(this));
            // move media
            this.sandbox.on('sulu.list-toolbar.media-move',
                OverlayManager.startSelectCollectionOverlayMedia.bind(this, this.sandbox, [this.data.id]));
        },

        /**
         * Renders the files tab
         */
        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/files'));
            this.startDropzone();
            this.startDatagrid();
            //this.renderSelectCollection();
        },

        /**
         * Starts the dropzone component
         */
        startDropzone: function() {
            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: this.$find(constants.dropzoneSelector),
                        url: '/admin/api/media?collection=' + this.data.id,
                        method: 'POST',
                        paramName: 'fileVersion',
                        instanceName: this.options.instanceName
                    }
                }
            ]);
        },

        /**
         * Starts the list-toolbar in the header
         */
        startDatagrid: function() {
            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'media', '/admin/api/media/fields',
                {
                    el: this.$find(constants.toolbarSelector),
                    instanceName: this.options.instanceName,
                    template: this.sandbox.sulu.buttons.get({
                        edit: {
                            options: {
                                callback: function() {
                                    this.sandbox.emit('sulu.list-toolbar.edit');
                                }.bind(this)
                            }
                        },
                        deleteSelected: {
                            options: {
                                callback: function() {
                                    this.sandbox.emit('sulu.list-toolbar.delete');
                                }.bind(this)
                            }
                        },
                        settings: {
                            options: {
                                dropdownItems: [
                                    {
                                        id: 'media-move',
                                        title: this.sandbox.translate('sulu.media.move'),
                                        callback: function() {
                                            this.sandbox.emit('sulu.list-toolbar.media-move');
                                        }.bind(this)
                                    },
                                    {
                                        type: 'columnOptions'
                                    }
                                ]
                            }
                        },
                        mediaDecoratorDropdown: {}
                    })
                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/media?orderBy=media.changed&orderSort=DESC&locale=' + UserSettingsManager.getMediaLocale() + '&collection=' + this.data.id,
                    view: UserSettingsManager.getMediaListView(),
                    resultKey: 'media',
                    sortable: false,
                    actionCallback: this.editMedia.bind(this),
                    viewOptions: {
                        table: {
                            actionIconColumn: 'name'
                        }
                    }
                });
        },

        /**
         * emit events to move selected media's
         * @param collection
         */
        moveMedia: function(collection) {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                MediaManager.move(ids, collection.id);
            }.bind(this));
        },

        /**
         * Edits all selected medias
         * @param clickedMedia {Number|String} id of a media which should, besides the selected ones, also be edited (e.g. if it was clicked)
         */
        editMedia: function(clickedMedia) {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(mediaIds) {
                if (!!clickedMedia && mediaIds.indexOf(clickedMedia) === -1) {
                    mediaIds.push(clickedMedia);
                    //todo: select clickedmedia in datagrid
                }

                OverlayManager.startEditMediaOverlay(this.sandbox, mediaIds, UserSettingsManager.getMediaLocale());
            }.bind(this));
        },

        /**
         * Updates the grid
         * @param media {Object|Array} a media object or an array of media objects
         */
        updateGrid: function(media) {
            if (!!media.locale && media.locale === this.options.locale) {
                this.sandbox.emit('husky.datagrid.records.change', media);
            } else if (media.length > 0 && media[0].locale === this.options.locale) {
                this.sandbox.emit('husky.datagrid.records.change', media);
            }
        },

        /**
         * Deletes all selected medias
         */
        deleteMedia: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                    if (!!confirmed) {
                        //this.sandbox.emit('husky.datagrid.medium-loader.show');
                        MediaManager.delete(ids);
                    }
                }.bind(this));
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
