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

        tabs = {
            FILES: 'files',
            SETTINGS: 'settings'
        },

        constants = {
            dropzoneSelector: '.dropzone-container',
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container',
            settingsFormId: 'collection-settings'
        };

    return {

        view: true,

        templates: [
            '/admin/media/template/media/collection-files',
            '/admin/media/template/media/collection-settings'
        ],

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.saved = true;

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
            }.bind(this));

            // change datagrid to thumbnail small
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-small', function () {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: false});
            }.bind(this));

            // change datagrid to thumbnail large
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-large', function () {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', {large: true});
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

            // reload datagrid of media has changed
            this.sandbox.on('sulu.media.collections.media-saved', function() {
                this.sandbox.emit('husky.datagrid.update');
            }.bind(this));

            // delete a media
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));

            // save button clicked
            this.sandbox.on('sulu.header.toolbar.save', this.saveSettings.bind(this));

            // delete the collection
            this.sandbox.on('sulu.header.toolbar.delete', this.deleteCollection.bind(this));
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
         * Deletes the current collection
         */
        deleteCollection: function() {
            this.sandbox.emit('sulu.media.collections.delete-collection', this.options.data.id, function() {
                this.sandbox.sulu.unlockDeleteSuccessLabel();
                this.sandbox.emit('sulu.media.collections.collection-list');
            }.bind(this));
        },

        /**
         * Renders the component
         */
        render: function () {
            this.setHeaderInfos();
            if (this.options.activeTab === tabs.FILES) {
                this.renderFiles();
            } else if (this.options.activeTab === tabs.SETTINGS)  {
                this.renderSettings();
            }
        },

        /**
         * Renderes the files tab
         */
        renderFiles: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/media/collection-files'));
            this.startDropzone();
            this.startDatagrid();
        },

        /**
         * Renderes the files tab
         */
        renderSettings: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/media/collection-settings'));
            this.options.data.color = this.options.data.style.color;
            this.sandbox.form.create('#' + constants.settingsFormId);
            this.sandbox.form.setData('#' + constants.settingsFormId, this.options.data);
            this.startSettingsToolbar();
            this.bindSettingsDomEvents();
        },

        /**
         * Binds dom events concerning the settings tab
         */
        bindSettingsDomEvents: function() {
            // activate save-button on key input
            this.sandbox.dom.on(this.$el, 'keyup', function() {
                if (this.saved === true) {
                    this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', false);
                    this.saved = false;
                }
            }.bind(this));
        },

        /**
         * Starts the Toolbar for the settings-tab
         */
        startSettingsToolbar: function() {
            this.sandbox.emit('sulu.header.set-toolbar', {template: 'default'});
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
         * Saves the settings-tab
         */
        saveSettings: function() {
            if (this.sandbox.form.validate('#' + constants.settingsFormId)) {
                var data = this.sandbox.form.getData('#' + constants.settingsFormId);
                data.style = {color: data.color};
                this.options.data = this.sandbox.util.extend(true, {}, this.options.data, data);
                this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');
                this.sandbox.once('sulu.media.collections.collection-changed', this.savedCallback.bind(this));
                this.sandbox.emit('sulu.media.collections.save-collection', this.options.data);
            }
        },

        /**
         * Method which gets called after the save-process has finished
         */
        savedCallback: function() {
            this.setHeaderInfos();
            this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', true, true);
            this.saved = true;
            this.sandbox.emit('sulu.labels.success.show', 'labels.success.collection-save-desc', 'labels.success');
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
