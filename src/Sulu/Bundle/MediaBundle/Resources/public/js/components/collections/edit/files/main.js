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
    'services/sulumedia/overlay-manager',
    'sulusecurity/services/security-checker',
    'services/sulumedia/file-icons',
    'text!./files.html'
], function(
    Config,
    CollectionManager,
    MediaManager,
    MediaRouter,
    UserSettingsManager,
    OverlayManager,
    SecurityChecker,
    FileIcons,
    filesTemplate) {

    'use strict';

    var defaults = {
            instanceName: 'collection'
        },

        urls = {
            children: '/admin/api/collections<% if (!!collection) { %>/<%= collection %><% } %>?locale=<%= locale %>&sortBy=title<% if (!!collection) { %>&depth=1<% } %>',
            media: '/admin/api/media?locale=<%= locale %><% if (!!collection) { %>&collection=<%= collection %><% } %>'
        },

        constants = {
            hideToolbarClass: 'toolbar-hidden',
            dropzoneSelector: '.dropzone-container',
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container',
            childrenSelector: '.children-container',
            collectionTitleSelector: '.content-title h2'
        };

    return {

        stickyToolbar: 240,

        layout: {
            content: {
                width: 'max'
            }
        },

        /**
         * Initialize the component
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.data = this.options.getData();
            this.bindDatagridEvents();
            this.bindDropzoneEvents();
            this.bindOverlayEvents();
            this.bindManagerEvents();
            this.bindListToolbarEvents();

            this.render();

            // start edit if media was clicked in root-component
            if (!!this.options.editId) {
                OverlayManager.startEditMediaOverlay.call(this, this.options.editId, this.options.locale);
            }
        },

        /**
         * Bind datagrid related events
         */
        bindDatagridEvents: function() {
            this.sandbox.on(
                'husky.datagrid.' + this.data.id + '.children.tiles.add-clicked',
                OverlayManager.startCreateCollectionOverlay.bind(this, this.data)
            );

            // toggle item buttons
            this.sandbox.on('husky.datagrid.number.selections', function(selectedItems) {
                var string = (selectedItems > 0) ? 'enable' : 'disable';
                this.sandbox.emit('husky.toolbar.' + this.options.instanceName + '.item.' + string, 'media-move', false);
                this.sandbox.emit('husky.toolbar.' + this.options.instanceName + '.item.' + string, 'editSelected', false);
                this.sandbox.emit('husky.toolbar.' + this.options.instanceName + '.item.' + string, 'delete', false);
            }.bind(this));
        },

        /**
         * Bind dropzone related events
         */
        bindDropzoneEvents: function() {
            // add uploaded medias to datagrid
            this.sandbox.on('husky.dropzone.' + this.options.instanceName + '.success', function(file, mediaResponse) {
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                mediaResponse.type = mediaResponse.type.name;
                this.sandbox.emit('husky.datagrid.records.add', [mediaResponse]);
            }, this);
        },

        /**
         * Bind data management related events
         */
        bindManagerEvents: function() {
            // remove deleted medias from datagrid
            this.sandbox.on('sulu.medias.media.deleted', function(id) {
                this.sandbox.emit('husky.datagrid.record.remove', id);
            }.bind(this));

            // remove moved medias from datagrid
            this.sandbox.on('sulu.medias.media.moved', function(id) {
                this.sandbox.emit('husky.datagrid.record.remove', id);
            }.bind(this));

            // change saved medias in datagrid
            this.sandbox.on('sulu.medias.media.saved', function(id, media) {
                // change medias if media is saved without locale or locale is current media-locale
                if (!media.locale || media.locale === this.options.locale) {
                    this.sandbox.emit('husky.datagrid.records.change', this.sandbox.util.extend(true, {}, media, {
                        type: (!!media.type.name) ? media.type.name : media.type
                    }));
                }
            }.bind(this));
        },

        /**
         * Bind overlay related events
         */
        bindOverlayEvents: function() {
            // chose collection to move media in collection-select overlay
            this.sandbox.on('sulu.collection-select.move-media.selected', this.moveMedia.bind(this));

            // disable dropzone popup when overlay is active
            this.sandbox.on('sulu.collection-add.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.collection-edit.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.collection-select.move-collection.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.collection-select.move-media.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.media-edit.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.permission-settings.initialized', this.disableDropzone.bind(this));

            // enable dropzone popup on overlay close
            this.sandbox.on('sulu.collection-add.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.collection-edit.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.collection-select.move-collection.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.collection-select.move-media.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.media-edit.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.permission-settings.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.medias.collection.saved', this.savedHandler.bind(this));
        },

        /**
         * Bind events which are emited from the list-toolbar
         */
        bindListToolbarEvents: function() {
            // show dropzone popup
            this.sandbox.on('sulu.list-toolbar.add', function() {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.show-popup');
            }.bind(this));

            // delete a media
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));

            // edit media
            this.sandbox.on('sulu.list-toolbar.edit', this.editMedias.bind(this));

            // start collection-select overlay on move-click
            this.sandbox.on('sulu.list-toolbar.media-move', function() {
                OverlayManager.startMoveMediaOverlay.call(this, this.data.id, this.options.locale);
            }.bind(this));

            // change datagrid view to table
            this.sandbox.on('sulu.toolbar.change.table', function() {
                UserSettingsManager.setMediaListView('table');
                UserSettingsManager.setMediaListPagination('dropdown');

                this.sandbox.emit('husky.datagrid.change',
                    1,
                    UserSettingsManager.getDropdownPageSize(),
                    'table',
                    [],
                    'dropdown'
                );

                this.sandbox.stickyToolbar.reset(this.$el);
            }.bind(this));

            // change datagrid view to masonry
            this.sandbox.on('sulu.toolbar.change.masonry', function() {
                UserSettingsManager.setMediaListView('datagrid/decorators/masonry-view');
                UserSettingsManager.setMediaListPagination('infinite-scroll');

                this.sandbox.emit(
                    'husky.datagrid.change',
                    1,
                    UserSettingsManager.getInfinityPageSize(),
                    'datagrid/decorators/masonry-view',
                    null,
                    'infinite-scroll'
                );

                this.sandbox.stickyToolbar.reset(this.$el);
            }.bind(this));
        },

        savedHandler: function(id, collection) {
            if (!collection.locale || collection.locale === this.options.locale) {
                $(constants.collectionTitleSelector).text(collection.title)
            }
        },

        /**
         * Renders the files component
         */
        render: function() {
            this.sandbox.dom.html(this.$el, filesTemplate);

            if (SecurityChecker.hasPermission(this.data, 'add')) {
                this.startDropzone();
            }

            this.startChildrenTiles();
            this.startDatagrid();
        },

        /**
         * Starts the datagrid showing the children (sub-folders) of the collection
         */
        startChildrenTiles: function() {
            if (!this.data.hasSub) {
                this.$find(constants.childrenSelector).remove();
                return;
            }

            this.sandbox.start([{
                name: 'datagrid@husky',
                options: {
                    el: this.$find(constants.childrenSelector),
                    url: _.template(urls.children, {
                        collection: this.data.id,
                        locale: this.options.locale
                    }),
                    instanceName: this.data.id + '.children',
                    view: 'tiles',
                    resultKey: 'collections',
                    viewOptions: {
                        tiles: {
                            fields: {
                                description: ['mediaCount']
                            },
                            translations: {
                                addNew: 'sulu.media.add-collection'
                            }
                        }
                    },
                    pagination: false,
                    actionCallback: function(id) {
                        MediaRouter.toCollection(id, this.options.locale);
                    }.bind(this),
                    matchings: [
                        {
                            name: 'id'
                        },
                        {
                            name: 'title'
                        },
                        {
                            name: 'mediaCount',
                            type: 'count'
                        }
                    ]
                }
            }]);
        },

        /**
         * Start the list toolbar and the datagrid
         */
        startDatagrid: function() {
            var view = UserSettingsManager.getMediaListView(),
                locale = this.options.locale;


            this.sandbox.sulu.initListToolbarAndList.call(this,
                'media',
                '/admin/api/media/fields?locale=' + locale + '&sortBy=created&sortOrder=desc',
                {
                    el: this.$find(constants.toolbarSelector),
                    instanceName: this.options.instanceName,
                    template: this.sandbox.sulu.buttons.get(this.getEditButtons())
                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: _.template(urls.media, {
                        collection: this.data.id,
                        locale: locale
                    }),
                    searchFields: ['name', 'title', 'description'],
                    view: view,
                    pagination: UserSettingsManager.getMediaListPagination(),
                    resultKey: 'media',
                    actionCallback: function(clickedId) {
                        this.editMedia(clickedId);
                    }.bind(this),
                    viewOptions: {
                        table: {
                            actionIconColumn: 'name',
                            noImgIcon: function(item) {
                                return FileIcons.getByMimeType(item.mimeType);
                            },
                            badges: [
                                {
                                    column: 'title',
                                    callback: function(item, badge) {
                                        if (item.locale !== this.options.locale) {
                                            badge.title = item.locale;

                                            return badge;
                                        }
                                    }.bind(this)
                                }
                            ]
                        },
                        'datagrid/decorators/masonry-view': {
                            noImgIcon: function(item) {
                                return FileIcons.getByMimeType(item.mimeType);
                            },
                            locale: locale
                        }
                    },
                    paginationOptions: {
                        'infinite-scroll': {
                            reachedBottomMessage: 'public.reached-list-end',
                            scrollOffset: 500
                        }
                    }
                });
        },

        /**
         * Constructs and returns the buttons for the list-toolbar, dependent on the
         * permissions of the current user.
         *
         * @returns {Object} The buttons for the media edit
         */
        getEditButtons: function() {
            var settingsDropdown = [], buttons = {};

            if (!!this.data.id && SecurityChecker.hasPermission(this.data, 'add')) {
                buttons.add = {
                    options: {
                        showTitle: true,
                        title: 'sulu-media.upload-files',
                        icon: 'cloud-upload',
                        callback: function() {
                            this.sandbox.emit('sulu.list-toolbar.add');
                        }.bind(this)
                    }
                };
            }

            if (SecurityChecker.hasPermission(this.data, 'edit')) {
                buttons.editSelected = {
                    options: {
                        callback: function() {
                            this.sandbox.emit('sulu.list-toolbar.edit');
                        }.bind(this)
                    }
                };
            }

            if (SecurityChecker.hasPermission(this.data, 'delete')) {
                buttons.deleteSelected = {
                    options: {
                        callback: function() {
                            this.sandbox.emit('sulu.list-toolbar.delete');
                        }.bind(this)
                    }
                };
            }

            if (!!this.data.id && SecurityChecker.hasPermission(this.data, 'edit')) {
                settingsDropdown.push({
                    id: 'media-move',
                    title: this.sandbox.translate('sulu.media.move'),
                    callback: function() {
                        this.sandbox.emit('sulu.list-toolbar.media-move');
                    }.bind(this)
                });
            }

            settingsDropdown.push({
                type: 'columnOptions'
            });

            buttons.settings = {
                options: {
                    dropdownItems: settingsDropdown
                }
            };

            buttons.mediaDecoratorDropdown = {};

            return buttons;
        },

        /**
         * Starts the dropzone component
         */
        startDropzone: function() {
            if (!!this.data.id) {
                this.sandbox.start([
                    {
                        name: 'dropzone@husky',
                        options: {
                            el: this.$find(constants.dropzoneSelector),
                            maxFilesize: Config.get('sulu-media').maxFilesize,
                            url: '/admin/api/media?collection=' + this.data.id
                            + '&locale=' + this.options.locale,
                            method: 'POST',
                            paramName: 'fileVersion',
                            overlayContainer: '.content-column',
                            instanceName: this.options.instanceName
                        }
                    }
                ]);
            }
        },

        /**
         * Move selected medias to given collection
         * @param collection
         */
        moveMedia: function(collection) {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
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
        editMedias: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(mediaIds) {
                OverlayManager.startEditMediaOverlay.call(this, mediaIds, this.options.locale);
            }.bind(this));
        },

        /**
         * Edit given media
         */
        editMedia: function(mediaId) {
            OverlayManager.startEditMediaOverlay.call(this, [mediaId], this.options.locale);
        },

        /**
         * Show confimation dialog and delete all selected medias if confirmed
         */
        deleteMedia: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                    if (!!confirmed) {
                        MediaManager.delete(ids);
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Disable dropzone-popup on drag-over
         */
        disableDropzone: function() {
            this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.disable');
        },

        /**
         * Enable dropzone popup on drag-over
         */
        enableDropzone: function() {
            this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.enable');
        }
    };
});
