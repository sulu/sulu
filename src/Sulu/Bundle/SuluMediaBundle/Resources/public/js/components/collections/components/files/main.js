/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function () {

    'use strict';

    var defaults = {
            data: {},
            instanceName: 'collection'
        },

        listViews = {
            table: {
                name: 'table'
            },
            thumbnailSmall: {
                name: 'thumbnail',
                thViewOptions: {
                    large: false
                }
            },
            thumbnailLarge: {
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
            listViewStorageKey: 'collectionEditListView'
        };

    return {

        view: true,

        layout: {
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
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.listView = this.sandbox.sulu.getUserSetting(constants.listViewStorageKey) || 'thumbnailSmall';

            this.bindCustomEvents();
            this.render();
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function () {
            // change datagrid to table
            this.sandbox.on('sulu.list-toolbar.change.table', function () {
                this.sandbox.emit('husky.datagrid.view.change', 'table');
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'table');
            }.bind(this));

            // change datagrid to thumbnail small
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-small', function () {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: false});
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailSmall');
            }.bind(this));

            // change datagrid to thumbnail large
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-large', function () {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: true});
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailLarge');
            }.bind(this));

            // load collections list if back icon is clicked
            this.sandbox.on('sulu.header.back', function () {
                this.sandbox.emit('sulu.media.collections.list');
            }.bind(this));

            // if files got uploaded to the server add them to the datagrid
            this.sandbox.on('husky.dropzone.' + this.options.instanceName + '.files-added', function (files) {
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                this.addFilesToDatagrid(files);
            }.bind(this));

            // open data-source folder-overlay
            this.sandbox.on('sulu.list-toolbar.add', function () {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.open-data-source');
            }.bind(this));

            // open edit overlay on datagrid click
            this.sandbox.on('husky.datagrid.item.click', this.editMedia.bind(this));

            // unlock the dropzone pop-up if the media-edit overlay was closed
            this.sandbox.on('sulu.media-edit.closed', function () {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.unlock-popup');
            }.bind(this));

            // update datagrid if media-edit is finished
            this.sandbox.on('sulu.media.collections.save-media', function (media) {
                this.sandbox.emit('husky.datagrid.records.change', media);
            }.bind(this));

            // delete a media
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));

            // toggle edit button
            this.sandbox.on('husky.datagrid.number.selections', this.toggleEditButton.bind(this));

            // edit media
            this.sandbox.on('sulu.list-toolbar.edit', this.editMedia.bind(this));
        },

        /**
         * Deletes all selected medias
         */
        deleteMedia: function () {
            this.sandbox.emit('husky.datagrid.items.get-selected', function (ids) {
                this.sandbox.emit('sulu.media.collections.delete-media', ids, function (mediaId) {
                    this.sandbox.emit('husky.datagrid.record.remove', mediaId);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Renderes the files tab
         */
        render: function () {
            this.setHeaderInfos();
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/files'));
            this.startDropzone();
            this.startDatagrid();
        },

        /**
         * Edits all selected medias
         * @param additionalMedia {Number|String} id of a media which should, besides the selected ones, also be edited (e.g. if it was clicked)
         */
        editMedia: function (additionalMedia) {
            // show a loading icon
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'edit');
            // stop loading icon if editing of the media has started
            this.sandbox.once('sulu.media-edit.edit', function () {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'edit', false);
            }.bind(this));

            this.sandbox.emit('husky.datagrid.items.get-selected', function (selected) {
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
        setHeaderInfos: function () {
            this.sandbox.emit('sulu.header.set-title', this.options.data.title);
            this.sandbox.emit('sulu.header.set-breadcrumb', [
                {title: 'navigation.media'},
                {title: 'media.collections.title', event: 'sulu.media.collections.list'},
                {title: this.options.data.title}
            ]);
        },

        /**
         * Starts the dropzone component
         */
        startDropzone: function () {
            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: this.$find(constants.dropzoneSelector),
                        url: '/admin/api/media?collection%5Bid%5D=' + this.options.data.id,
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
        startDatagrid: function () {
            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'mediaFields', '/admin/api/media/fields',
                {
                    el: this.$find(constants.toolbarSelector),
                    instanceName: this.options.instanceName,
                    parentTemplate: 'defaultEditable',
                    template: 'changeable',
                    inHeader: true

                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/media?collection=' + this.options.data.id,
                    view: listViews[this.listView].name,
                    resultKey: 'media',
                    pagination: false,
                    viewOptions: {
                        table: {
                            fullWidth: false
                        },
                        thumbnail: listViews[this.listView].thViewOptions || {}
                    }
                });
        },

        /**
         * Enables or dsiables the edit button
         * @param selectedElements {Number} number of selected elements
         */
        toggleEditButton: function (selectedElements) {
            var enable = selectedElements > 0;
            this.sandbox.emit('sulu.list-toolbar.' + this.options.instanceName + '.edit.state-change', enable);
        },

        /**
         * Takes an array of files and adds them to the datagrid
         * @param files {Array} array of files
         */
        addFilesToDatagrid: function (files) {
            for (var i = -1, length = files.length; ++i < length;) {
                files[i].selected = true;
            }
            this.sandbox.emit('husky.datagrid.records.add', files, this.scrollToBottom.bind(this));
        },

        /**
         * Scrolls the whole form the the bottom
         */
        scrollToBottom: function () {
            this.sandbox.dom.scrollAnimate(this.sandbox.dom.height(this.sandbox.dom.$document), 'body');
        }
    };
});
