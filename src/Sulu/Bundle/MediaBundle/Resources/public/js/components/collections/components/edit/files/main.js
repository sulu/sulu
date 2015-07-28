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
            this.sandbox.emit('husky.data-navigation.collections.set-url', url);
            this.sandbox.emit('husky.navigation.select-id', 'collections-edit', {dataNavigation: {url: url}});

            this.listView = this.sandbox.sulu.getUserSetting(constants.listViewStorageKey) || 'thumbnailSmall';

            this.bindCustomEvents();
            this.render();
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
            this.sandbox.on('sulu.toolbar.change.table', function() {
                this.sandbox.emit('husky.datagrid.view.change', 'table');
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'table');
            }.bind(this));

            // change datagrid to thumbnail small
            this.sandbox.on('sulu.toolbar.change.thumbnail-small', function() {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: false});
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailSmall');
            }.bind(this));

            // change datagrid to thumbnail large
            this.sandbox.on('sulu.toolbar.change.thumbnail-large', function() {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: true});
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailLarge');
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

            // download media
            this.sandbox.on('husky.datagrid.download-clicked', this.download.bind(this));

            // unlock the dropzone pop-up if the media-edit overlay was closed
            this.sandbox.on('sulu.media-edit.closed', function() {
                this.sandbox.emit('husky.datagrid.items.deselect');
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

            // update the data
            this.sandbox.on('sulu.media.collections.edit.updated', this.updateData.bind(this));
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
         * Updates the data and reloads the grid
         * @param data {Object} the new collection object
         */
        updateData: function(data) {
            this.options.data = data;
            this.options.locale = this.options.data.locale;
            this.sandbox.emit('husky.datagrid.url.update', {locale: this.options.locale});
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
                name: 'collections/components/overlays/collection-select@sulumedia',
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
                    template: this.sandbox.sulu.buttons.get(
                        {'edit': {
                            callback: function() {
                                this.sandbox.emit('sulu.list-toolbar.edit');
                            }.bind(this)
                        }},
                        {'delete': {
                           callback: function() {
                               this.sandbox.emit('sulu.list-toolbar.delete');
                           }.bind(this)
                        }},
                        {'settings': {
                            dropdownItems: [
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
                        }},
                        'layout'
                    )
                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/media?orderBy=media.changed&orderSort=DESC&locale=' + this.options.data.locale + '&collection=' + this.options.data.id,
                    view: listViews[this.listView].name,
                    resultKey: 'media',
                    sortable: false,
                    actionCallback: this.editMedia.bind(this),
                    viewOptions: {
                        thumbnail: listViews[this.listView].thViewOptions || {},
                        table: {
                            actionIconColumn: 'name'
                        }
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
