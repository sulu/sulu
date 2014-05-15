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
                {title: 'media.collections.title'},
                {title: 'this.options.data.title'}
            ]);
            this.sandbox.emit('sulu.header.set-title-color', '#' + this.options.data.color);
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
                        url: '/admin/api/media',
                        method: 'POST'
                    }
                }
            ]);
        },

        /**
         * Starts the list-toolbar in the header
         */
        startDatagrid: function () {
            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accountsFields', '/admin/api/accounts/fields',
                {
                    el: this.$find(constants.toolbarSelector),
                    instanceName: this.options.instanceName,
                    parentTemplate: 'default',
                    template: 'changeable',
                    inHeader: true

                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/accounts?flat=true',
                    view: 'thumbnail'
                });
        }
    };
});
