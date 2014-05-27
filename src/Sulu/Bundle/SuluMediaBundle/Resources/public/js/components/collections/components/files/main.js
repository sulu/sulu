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
            activeTab: null,
            data: {},
            instanceName: 'collection'
        },

        constants = {
            dropzoneSelector: '.dropzone-container',
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container'
        };

    return {

        view: true,

        templates: ['/admin/media/template/media/collection'],

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.bindCustomEvents();
            this.render();
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function () {
            // change datagrid to small thumbnails
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-small', function () {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: false});
            }.bind(this));

            // change datagrid to big thumbnails
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-large', function () {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: true});
            }.bind(this));

            // change datagrid to table
            this.sandbox.on('sulu.list-toolbar.change.table', function () {
                this.sandbox.emit('husky.datagrid.view.change', 'table');
            }.bind(this));

            // load collections list if back icon is clicked
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.media.collections.list');
            }.bind(this));

            // if files got uploaded to the server add them to the datagrid
            this.sandbox.on('husky.dropzone.'+ this.options.instanceName +'.files-added', function(files) {
                this.addFilesToDatagrid(files);
            }.bind(this));

            // open data-source folder-overlay
            this.sandbox.on('sulu.list-toolbar.add', function() {
                this.sandbox.emit('husky.dropzone.'+ this.options.instanceName +'.open-data-source');
            }.bind(this));

           // open edit overlay on datagrid click
            this.sandbox.on('husky.datagrid.item.click', function(id) {
                this.sandbox.emit('sulu.media.collections.edit-media', id);
            }.bind(this));

            // delete clicked
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));
        },

        /**
         * Deletes all selected medias
         */
        deleteMedia: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.media.collections.delete-media', ids, function(mediaId) {
                    this.sandbox.emit('husky.datagrid.record.remove', mediaId);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Renders the component
         */
        render: function () {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/media/collection'));
            this.setHeaderInfos();
            this.startDropzone();
            this.startDatagrid();
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
            this.sandbox.emit('sulu.header.set-title-color', this.options.data.style.color);
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
                    parentTemplate: 'default',
                    template: 'changeable',
                    inHeader: true

                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/media?collection=' + this.options.data.id,
                    view: 'thumbnail',
                    pagination: false
                });
        },

        /**
         * Takes an array of files and adds them to the datagrid
         * @param files {Array} array of files
         */
        addFilesToDatagrid: function(files) {
            for (var i = -1, length = files.length; ++i < length;) {
                files[i].selected = true;
            }
            this.sandbox.emit('husky.datagrid.records.add', files);
        }
    };
});
