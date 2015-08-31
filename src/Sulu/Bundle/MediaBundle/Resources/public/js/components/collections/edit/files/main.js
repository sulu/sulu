/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/sulumedia/media-manager',
    'services/sulumedia/user-settings-manager',
    'services/sulumedia/overlay-manager'], function(MediaManager, UserSettingsManager, OverlayManager) {

    'use strict';

    var defaults = {
            instanceName: 'collection'
        },

        constants = {
            dropzoneSelector: '.dropzone-container',
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container',
        };

    return {
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

        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.bindDatagridEvents();
            this.bindDropzoneEvents();
            this.bindOverlayEvents();
            this.bindManagerEvents();
            this.bindListToolbarEvents();

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

            // toggle item buttons
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

            this.sandbox.on('sulu.medias.media.saved', function(id, media) {
                if (!!media.locale && media.locale === UserSettingsManager.getMediaLocale()) {
                    this.sandbox.emit('husky.datagrid.records.change', media);
                }
            }.bind(this));
        },

        bindOverlayEvents: function() {
            // chose collection to move media in collection-select overlay
            this.sandbox.on('sulu.media.collection-select.move-media.selected', this.moveMedia.bind(this));
        },

        bindListToolbarEvents: function() {
            // delete a media
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));
            // edit media
            this.sandbox.on('sulu.list-toolbar.edit', function() {
                //this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.lock-popup');
                this.editMedia.bind(this);
            }.bind(this));
            // move media
            this.sandbox.on('sulu.list-toolbar.media-move', function() {
                OverlayManager.startSelectCollectionOverlayMedia.bind(this, this.sandbox, [this.options.id]);
            }.bind(this));
        },

        /**
         * Renders the files tab
         */
        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/files'));
            this.startDropzone();
            this.startDatagrid();
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
                    url: '/admin/api/media?orderBy=media.changed&orderSort=DESC&locale=' + UserSettingsManager.getMediaLocale() + '&collection=' + this.options.id,
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
         * Starts the dropzone component
         */
        startDropzone: function() {
            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: this.$find(constants.dropzoneSelector),
                        url: '/admin/api/media?collection=' + this.options.id,
                        method: 'POST',
                        paramName: 'fileVersion',
                        instanceName: this.options.instanceName
                    }
                }
            ]);
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
                    mediaIds = mediaIds.concat(clickedMedia);
                    //todo: select clickedmedia in datagrid
                }

                OverlayManager.startEditMediaOverlay(this.sandbox, mediaIds, UserSettingsManager.getMediaLocale());
            }.bind(this));
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


    };
});
