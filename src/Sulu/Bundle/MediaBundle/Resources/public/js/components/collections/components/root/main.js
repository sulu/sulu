/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['sulumedia/model/media'], function(Media) {

    'use strict';

    var constants = {
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container',
            listViewStorageKey: 'collectionEditListView'
        },

        defaults = {},

        listViews = {
            table: {
                itemId: 'table',
                name: 'table'
            },
            thumbnailSmall: {
                itemId: 'small-thumbnails',
                name: 'thumbnail',
                thViewOptions: {
                    large: false,
                    selectable: false
                }
            },
            thumbnailLarge: {
                itemId: 'big-thumbnails',
                name: 'thumbnail',
                thViewOptions: {
                    large: true,
                    selectable: false
                }
            }
        };

    return {

        view: true,

        header: {
            noBack: true,
            toolbar: {template: 'empty'}
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

        /**
         * Initializes the collections list
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            var url = '/admin/api/collections?sortBy=title';
            this.sandbox.emit('husky.navigation.select-id', 'collections-edit', {dataNavigation: {url: url}});

            this.listView = this.sandbox.sulu.getUserSetting(constants.listViewStorageKey) || 'thumbnailSmall';

            this.bindCustomEvents();
            this.render();

            // shows a delete success label. If a collection just got deleted
            this.sandbox.sulu.triggerDeleteSuccessLabel('labels.success.collection-deleted-desc');
        },

        bindCustomEvents: function() {
            // change datagrid to table
            this.sandbox.on('sulu.list-toolbar.change.table', function() {
                this.sandbox.emit('husky.datagrid.view.change', 'table');
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'table');
            }.bind(this));

            // change datagrid to thumbnail small
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-small', function() {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', listViews['thumbnailSmall']['thViewOptions']);
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailSmall');
            }.bind(this));

            // change datagrid to thumbnail large
            this.sandbox.on('sulu.list-toolbar.change.thumbnail-large', function() {
                this.sandbox.emit('husky.datagrid.view.change', 'thumbnail', listViews['thumbnailLarge']['thViewOptions']);
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailLarge');
            }.bind(this));

            this.sandbox.on('husky.datagrid.item.click', function(id, item) {
                this.sandbox.emit(
                    'sulu.router.navigate',
                    'media/collections/edit:' + item.collection + '/files/edit:' + id
                );

                var url = '/admin/api/collections/' + item.collection + '?depth=1&sortBy=title';
                this.sandbox.emit('husky.data-navigation.collections.set-url', url);
            }.bind(this));

            // download media
            this.sandbox.on('husky.datagrid.download-clicked', this.download.bind(this));
        },

        render: function() {
            this.setHeaderInfos();
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/files'));
            this.startDatagrid();
        },

        setHeaderInfos: function() {
            var breadcrumb = [
                {title: 'navigation.media'},
                {title: 'media.collections.title'}
            ];

            this.sandbox.emit('sulu.header.set-title', 'sulu.media.all');
            this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
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
                    template: [
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
                    url: '/admin/api/media?orderBy=media.changed&orderSort=DESC',
                    view: listViews[this.listView].name,
                    resultKey: 'media',
                    sortable: false,
                    viewOptions: {
                        table: {
                            selectItem: true,
                            fullWidth: false,
                            rowClickSelect: false
                        },
                        thumbnail: listViews[this.listView].thViewOptions || {}
                    }
                });
        },

        /**
         * Downloads a media for a given id
         * @param id
         */
        download: function(id) {
            this.getMedia(id).then(function(media) {
                this.sandbox.dom.window.location.href = media.versions[media.version].url;
            }.bind(this));
        },

        getMedia: function(id) {
            var def = this.sandbox.data.deferred(),
                media = Media.find({id: id});

            if (media !== null) {
                def.resolve(media.toJSON());

                return def;
            }

            media = new Media();
            media.set({id: id});
            media.fetch({
                success: function(media) {
                    def.resolve(media.toJSON());
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log('Error while fetching a single media');
                }.bind(this)
            });

            return def;
        }
    };
});
