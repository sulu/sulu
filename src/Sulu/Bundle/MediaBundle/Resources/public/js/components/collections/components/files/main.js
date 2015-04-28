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

    var defaults = {
            data: {},
            instanceName: 'collection'
        },

        listViews = {
            table: {
                itemId: 'table',
                name: 'table'
            },
            thumbnailSmall: {
                itemId: 'small-thumbnails',
                name: 'thumbnail',
                thViewOptions: {
                    large: false
                }
            },
            thumbnailLarge: {
                itemId: 'big-thumbnails',
                name: 'thumbnail',
                thViewOptions: {
                    large: true
                }
            }
        },

        constants = {
            dropzoneSelector: '.dropzone-container',
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container',
            moveSelector: '.move-container',
            listViewStorageKey: 'collectionEditListView'
        };

    return {

        view: true,

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

        /**
         * Initializes the collections list
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            var url = '/admin/api/collections/' + this.options.data.id + '?depth=1&sortBy=title';
            this.sandbox.emit('husky.navigation.select-id', 'collections-edit', {dataNavigation: {url: url}});

            this.listView = this.sandbox.sulu.getUserSetting(constants.listViewStorageKey) || 'thumbnailSmall';

            this.bindCustomEvents();
            this.render();

            // shows a delete success label. If a collection just got deleted
            this.sandbox.sulu.triggerDeleteSuccessLabel('labels.success.collection-deleted-desc');
        },

        /**
         * Deconstructor
         */
        remove: function() {
            this.sandbox.stop(constants.dropzoneSelector);
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function() {
            // change datagrid to table
            this.sandbox.on('sulu.list-toolbar.change.table', function() {
                this.sandbox.emit('husky.datagrid.view.change', 'table');
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'table');
            }.bind(this));

            // change datagrid to thumbnail small
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-small', function() {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: false});
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailSmall');
            }.bind(this));

            // change datagrid to thumbnail large
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-large', function() {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: true});
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailLarge');
            }.bind(this));

            // load collections list if back icon is clicked
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.media.collections.list');
            }.bind(this));

            // if files got uploaded to the server add them to the datagrid
            this.sandbox.on('husky.dropzone.' + this.options.instanceName + '.files-added', function(files) {
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                this.addFilesToDatagrid(files);
            }.bind(this));

            // open data-source folder-overlay
            this.sandbox.on('sulu.list-toolbar.add', function() {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.open-data-source');
            }.bind(this));

            // premark the current view in the toolbar
            this.sandbox.on('husky.toolbar.' + this.options.instanceName + '.initialized', function() {
                this.sandbox.emit('sulu.header.toolbar.item.mark', listViews[this.listView].itemId);
            }.bind(this));

            // open edit overlay on datagrid click
            this.sandbox.on('husky.datagrid.item.click', this.editMedia.bind(this));

            // download media
            this.sandbox.on('husky.datagrid.download-clicked', this.download.bind(this));

            // unlock the dropzone pop-up if the media-edit overlay was closed
            this.sandbox.on('sulu.media-edit.closed', function() {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.unlock-popup');
            }.bind(this));

            // update datagrid if media-edit is finished
            this.sandbox.on('sulu.media.collections.save-media', this.updateGrid.bind(this));

            // delete a media
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));

            // toggle edit button
            this.sandbox.on('husky.datagrid.number.selections', this.toggleEditButton.bind(this));

            // edit media
            this.sandbox.on('sulu.list-toolbar.edit', this.editMedia.bind(this));

            // move
            this.sandbox.on('sulu.media.collection-select.move-media.selected', this.moveMedia.bind(this));

            // change the editing language
            this.sandbox.on('sulu.header.toolbar.language-changed', this.changeLanguage.bind(this));
        },

        /**
         * Changes the editing language
         * @param locale {string} the new locale to edit the collection in
         */
        changeLanguage: function(locale) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'language');
            this.sandbox.emit(
                'sulu.media.collections.reload-collection',
                this.options.data.id, {locale: locale.localization, breadcrumb: 'true'},
                function(collection) {
                    this.options.data = collection;
                    this.setHeaderInfos();
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'language', false);
                    this.sandbox.emit('husky.datagrid.url.update', {locale: this.options.data.locale});
                    this.options.locale = this.options.data.locale;
                }.bind(this)
            );
            this.sandbox.emit('sulu.media.collections-edit.set-locale', locale.localization);
        },

        /**
         * Updates the grid
         * @param media {Object|Array} a media object or an array of media objects
         */
        updateGrid: function(media) {
            var update = false;
            if (!!media.locale && media.locale === this.options.locale) {
                update = true;
            } else if (media.length > 0 && media[0].locale === this.options.locale) {
                update = true;
            }
            if (update === true) {
                this.sandbox.emit('husky.datagrid.records.change', media);
            }
        },

        /**
         * Downloads a media for a given id
         * @param id
         */
        download: function(id) {
            this.sandbox.emit('sulu.media.collections.download-media', id);
        },

        /**
         * Deletes all selected medias
         */
        deleteMedia: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.media.collections.delete-media', ids,
                    function() {
                        this.sandbox.emit('husky.datagrid.medium-loader.show');
                    }.bind(this),
                    function(mediaId, finished) {
                        if (finished === true) {
                            this.sandbox.emit('husky.datagrid.medium-loader.hide');
                        }
                        this.sandbox.emit('husky.datagrid.record.remove', mediaId);
                        this.sandbox.emit('husky.data-navigation.collections.reload');
                    }.bind(this)
                );
            }.bind(this));
        },

        /**
         * Renders the files tab
         */
        render: function() {
            this.setHeaderInfos();
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/files'));
            this.startDropzone();
            this.startDatagrid();
            this.renderSelectCollection();
        },

        /**
         * Edits all selected medias
         * @param additionalMedia {Number|String} id of a media which should, besides the selected ones, also be edited (e.g. if it was clicked)
         */
        editMedia: function(additionalMedia) {
            // show a loading icon
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'edit');
            // stop loading icon if editing of the media has started
            this.sandbox.once('sulu.media-edit.edit', function() {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'edit', false);
            }.bind(this));

            this.sandbox.emit('husky.datagrid.items.get-selected', function(selected) {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.lock-popup');
                // add additional media to the edit-list, but only if its not already contained
                if (!!additionalMedia && selected.indexOf(additionalMedia) === -1) {
                    selected.push(additionalMedia);
                }
                this.sandbox.emit('sulu.media.collections.edit-media', selected);
            }.bind(this));
        },

        /**
         * Sets all the Info contained in the header
         * like breadcrumb or title
         */
        setHeaderInfos: function() {
            var breadcrumb = [
                {title: 'navigation.media'},
                {
                    title: 'media.collections.title',
                    event: 'sulu.media.collections.breadcrumb-navigate.root'
                }
            ], i, len, data = this.options.data._embedded.breadcrumb || [];

            for (i = 0, len = data.length; i < len; i++) {
                breadcrumb.push({
                    title: data[i].title,
                    event: 'sulu.media.collections.breadcrumb-navigate',
                    eventArgs: data[i]
                });
            }

            breadcrumb.push({title: this.options.data.title});

            this.sandbox.emit('sulu.header.set-title', this.options.data.title);
            this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
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
                        url: '/admin/api/media?collection=' + this.options.data.id,
                        method: 'POST',
                        paramName: 'fileVersion',
                        instanceName: this.options.instanceName
                    }
                }
            ]);
        },

        /**
         * render move media overlay
         */
        renderSelectCollection: function() {
            this.sandbox.start([{
                name: 'collections/components/collection-select@sulumedia',
                options: {
                    el: this.$find(constants.moveSelector),
                    instanceName: 'move-media',
                    title: this.sandbox.translate('sulu.media.move.overlay-title'),
                    disableIds: [this.options.data.id]
                }
            }]);
        },

        /**
         * starts overlay for move media
         */
        startMoveMediaOverlay: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.selectedIds = ids;
                this.sandbox.emit('sulu.media.collection-select.move-media.open');
            }.bind(this));
        },

        /**
         * emit events to move selected media's
         * @param collection
         */
        moveMedia: function(collection) {
            var left = this.selectedIds.length;

            this.sandbox.emit('sulu.media.collections.move-media', this.selectedIds, collection,
                function(mediaId) {
                    this.sandbox.emit('husky.datagrid.record.remove', mediaId);

                    left--;
                    if(left === 0){
                        this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-move-desc', 'labels.success');
                    }
                }.bind(this)
            );

            this.sandbox.emit('sulu.media.collection-select.move-media.close');
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
                    parentTemplate: 'defaultEditable',
                    template: [
                        {
                            id: 'settings',
                            icon: 'gear',
                            position: 30,
                            items: [
                                {
                                    id: 'media-move',
                                    title: this.sandbox.translate('sulu.media.move'),
                                    callback: function() {
                                        this.startMoveMediaOverlay();
                                    }.bind(this)
                                },
                                {
                                    type: 'columnOptions'
                                }
                            ]
                        },
                        {
                            id: 'change',
                            icon: 'th-large',
                            itemsOption: {
                                markable: true
                            },
                            items: [
                                {
                                    id: 'small-thumbnails',
                                    title: this.sandbox.translate('sulu.list-toolbar.small-thumbnails'),
                                    callback: function() {
                                        this.sandbox.emit('sulu.list-toolbar.change.thumbnail-small');
                                    }.bind(this)
                                },
                                {
                                    id: 'big-thumbnails',
                                    title: this.sandbox.translate('sulu.list-toolbar.big-thumbnails'),
                                    callback: function() {
                                        this.sandbox.emit('sulu.list-toolbar.change.thumbnail-large');
                                    }.bind(this)
                                },
                                {
                                    id: 'table',
                                    title: this.sandbox.translate('sulu.list-toolbar.table'),
                                    callback: function() {
                                        this.sandbox.emit('sulu.list-toolbar.change.table');
                                    }.bind(this)
                                }
                            ]
                        }
                    ],
                    inHeader: false
                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/media?orderBy=media.changed&orderSort=DESC&locale=' + this.options.data.locale + '&collection=' + this.options.data.id,
                    view: listViews[this.listView].name,
                    resultKey: 'media',
                    sortable: false,
                    viewOptions: {
                        table: {
                            fullWidth: false,
                            rowClickSelect: true
                        },
                        thumbnail: listViews[this.listView].thViewOptions || {}
                    }
                });
        },

        /**
         * Enables or dsiables the edit button
         * @param selectedElements {Number} number of selected elements
         */
        toggleEditButton: function(selectedElements) {
            var enable = selectedElements > 0;
            this.sandbox.emit('sulu.list-toolbar.' + this.options.instanceName + '.edit.state-change', enable);
            this.sandbox.emit(
                'husky.toolbar.' + this.options.instanceName + '.item.' + (!!enable ? 'enable' : 'disable'),
                'media-move',
                false
            );
        },

        /**
         * Takes an array of files and adds them to the datagrid
         * @param files {Array} array of files
         */
        addFilesToDatagrid: function(files) {
            for (var i = -1, length = files.length; ++i < length;) {
                files[i].selected = true;
            }
            this.sandbox.emit('husky.datagrid.records.add', files, this.scrollToBottom.bind(this));
            this.sandbox.emit('husky.data-navigation.collections.reload');
        },

        /**
         * Scrolls the whole form the the bottom
         */
        scrollToBottom: function() {
            this.sandbox.dom.scrollAnimate(this.sandbox.dom.height(this.sandbox.dom.$document), 'body');
        }
    };
});
