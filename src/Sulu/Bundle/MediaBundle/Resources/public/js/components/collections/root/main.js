/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([ 'services/sulumedia/media-manager',
        'services/sulumedia/user-settings-manager',
        'services/sulumedia/media-router'], function(MediaManager, UserSettingsManager, MediaRouter) {

    'use strict';

    var constants = {
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container',
        },

        defaults = {};

    return {

        view: true,

        header: {
            noBack: true,
            toolbar: {
                template: 'empty',
                languageChanger: {
                    url: '/admin/api/localizations',
                    resultKey: 'localizations',
                    titleAttribute: 'localization',
                    preSelected: UserSettingsManager.getMediaLocale()
                }
            }
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

            //var url = '/admin/api/collections?sortBy=title';
            //this.sandbox.emit('husky.navigation.select-id', 'collections-edit', {dataNavigation: {url: url}});

            this.bindCustomEvents();
            this.render();

            // shows a delete success label. If a collection just got deleted
            //this.sandbox.sulu.triggerDeleteSuccessLabel('labels.success.collection-deleted-desc');
        },

        bindCustomEvents: function() {
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

            // language change
            this.sandbox.on('sulu.header.language-changed', function(locale) {
                UserSettingsManager.setMediaLocale(locale.id);
                this.sandbox.emit('husky.datagrid.url.update', {locale: locale.id});
            }.bind(this));
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/files'));
            this.startDatagrid();
        },

        actionCallback: function(id, item) {
            this.sandbox.sulu.viewStates['media-file-edit-id'] = id;
            MediaRouter.toCollection(item.collection);

            // var url = '/admin/api/collections/' + item.collection + '?depth=1&sortBy=title';
            // this.sandbox.emit('husky.data-navigation.collections.set-url', url);
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
                    url: '/admin/api/media?orderBy=media.changed&orderSort=desc&locale=' + UserSettingsManager.getMediaLocale(),
                    view: UserSettingsManager.getMediaListView(),
                    resultKey: 'media',
                    sortable: false,
                    actionCallback: this.actionCallback.bind(this),
                    viewOptions: {
                        table: {
                            selectItem: true,
                            actionIconColumn: 'name'
                        },
                        'decorators/masonry': {
                            selectable: false
                        }
                    }
                });
        }
    };
});
