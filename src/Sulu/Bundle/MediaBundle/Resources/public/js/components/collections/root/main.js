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
                name: 'table',
                viewOptions: {}
            },
            thumbnailSmall: {
                name: 'thumbnail',
                viewOptions: {
                    large: false,
                    selectable: false
                }
            },
            thumbnailLarge: {
                name: 'thumbnail',
                viewOptions: {
                    large: true,
                    selectable: false
                }
            },
            masonry: {
                name: 'decorators/masonry',
                viewOptions: {}
            }
        };

    return {

        view: true,

        header: function() {
            // init locale
            this.locale = this.options.locale;

            return {
                noBack: true,
                toolbar: {
                    template: 'empty',
                    languageChanger: {
                        url: '/admin/api/localizations',
                        resultKey: 'localizations',
                        titleAttribute: 'localization',
                        preSelected: this.locale
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
            /**
             * Change current view of the datagrid to given viewKey
             * viewKey must be specified in listViews-Object
             */
            var changeDatagridView = function(viewKey) {
                this.sandbox.emit('husky.datagrid.view.change', listViews[viewKey].name, listViews[viewKey]['viewOptions']);
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, viewKey);
            }.bind(this);

            // change datagrid to table
            this.sandbox.on('sulu.toolbar.change.table', function() {
                changeDatagridView('table');
            }.bind(this));

            // change datagrid to thumbnail small
            this.sandbox.on('sulu.toolbar.change.thumbnail-small', function() {
                changeDatagridView('thumbnailSmall');
            }.bind(this));

            // change datagrid to thumbnail large
            this.sandbox.on('sulu.toolbar.change.thumbnail-large', function() {
                changeDatagridView('thumbnailLarge');
            }.bind(this));

            // change datagrid to masonry
            this.sandbox.on('sulu.toolbar.change.masonry', function() {
                changeDatagridView('masonry');
            }.bind(this));

            // download media
            this.sandbox.on('husky.datagrid.download-clicked', this.download.bind(this));

            // language change
            this.sandbox.on('sulu.header.language-changed', this.changeLanguage.bind(this));
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/files'));
            this.startDatagrid();
        },

        actionCallback: function(id, item) {
            this.sandbox.sulu.viewStates['media-file-edit-id'] = id;
            this.sandbox.emit(
                'sulu.router.navigate',
                'media/collections/edit:' + item.collection + '/files'
            );

            var url = '/admin/api/collections/' + item.collection + '?depth=1&sortBy=title';
            this.sandbox.emit('husky.data-navigation.collections.set-url', url);
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
                        mediaDecoratorDropdown: {}
                    })
                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/media?orderBy=media.changed&orderSort=desc&locale=' + this.locale,
                    view: listViews[this.listView].name,
                    resultKey: 'media',
                    sortable: false,
                    actionCallback: this.actionCallback.bind(this),
                    viewOptions: {
                        table: {
                            selectItem: true,
                            actionIconColumn: 'name'
                        },
                        thumbnail: listViews[this.listView].viewOptions || {}
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
        },

        /**
         * Changes the editing language
         * @param locale {object} the new locale to display
         */
        changeLanguage: function(locale) {
            this.locale = locale.id;
            this.sandbox.emit('sulu.media.collections.set-locale', this.locale);
            this.sandbox.emit('husky.datagrid.url.update', {locale: this.locale});
        }
    };
});
