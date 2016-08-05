/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulumedia/media-manager',
    'services/sulumedia/user-settings-manager',
    'services/sulumedia/media-router',
    'services/sulumedia/file-icons',
    'config'
], function(MediaManager, UserSettingsManager, MediaRouter, FileIcons, Config) {

    'use strict';

    var constants = {
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container'
        },

        defaults = {};

    return {

        stickyToolbar: true,

        header: {
            title: 'sulu.media.all',

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
         * Initialize the component
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.sandbox.emit('husky.navigation.select-id', 'collections-edit');
            this.sandbox.emit('husky.data-navigation.collections.set-locale', UserSettingsManager.getMediaLocale());
            this.updateDataNavigationAddButton();

            this.bindCustomEvents();
            this.render();
        },

        /**
         * Set the data-navigation url
         */
        updateDataNavigationAddButton: function() {
            var permissions = Config.get('sulu-media').permissions;

            if (!!permissions.add) {
                this.sandbox.emit('husky.data-navigation.collections.add-button.show');
            } else {
                this.sandbox.emit('husky.data-navigation.collections.add-button.hide');
            }
        },

        /**
         * Bind component related events
         */
        bindCustomEvents: function() {
            // change datagrid view to table
            this.sandbox.on('sulu.toolbar.change.table', function() {
                UserSettingsManager.setMediaListView('table');
                UserSettingsManager.setMediaListPagination('dropdown');

                // this isn't a perfect strategy because datagrid is rerendered on all three events
                // todo: find a better strategy to change pagination and view-decorator and load first page
                this.sandbox.emit('husky.datagrid.change.page', 1, UserSettingsManager.getDropdownPageSize());
                this.sandbox.once('husky.datagrid.updated', function() {
                    this.sandbox.emit('husky.datagrid.view.change', 'table');
                    this.sandbox.emit('husky.datagrid.pagination.change', 'dropdown');
                }.bind(this));

                this.sandbox.stickyToolbar.reset(this.$el);
            }.bind(this));

            // change datagrid view to masonry
            this.sandbox.on('sulu.toolbar.change.masonry', function() {
                UserSettingsManager.setMediaListView('datagrid/decorators/masonry-view');
                UserSettingsManager.setMediaListPagination('infinite-scroll');

                // this isn't a perfect strategy because datagrid is rerendered on all three events
                // todo: find a better strategy to change pagination and view-decorator and load first page
                this.sandbox.emit('husky.datagrid.change.page', 1, UserSettingsManager.getInfinityPageSize());
                this.sandbox.once('husky.datagrid.updated', function() {
                    this.sandbox.emit('husky.datagrid.view.change', 'datagrid/decorators/masonry-view');
                    this.sandbox.emit('husky.datagrid.pagination.change', 'infinite-scroll');
                }.bind(this));

                this.sandbox.stickyToolbar.reset(this.$el);
            }.bind(this));

            // language change
            this.sandbox.on('sulu.header.language-changed', function(locale) {
                UserSettingsManager.setMediaLocale(locale.id);
                this.sandbox.emit('husky.datagrid.url.update', {locale: locale.id});
            }.bind(this));
        },

        /**
         * Render the component
         */
        render: function() {
            this.sandbox.dom.html(
                this.$el,
                this.renderTemplate('/admin/media/template/collection/files')
            );
            this.startDatagrid();
        },

        /**
         * Handles an item click in datagrid. Opens the collection of the clicked media and save the
         * clicked media to viewStates to trigger media-edit overlay when collection-component is loaded
         * @param id
         * @param item
         */
        actionCallback: function(id, item) {
            this.sandbox.sulu.viewStates['media-file-edit-id'] = id;
            MediaRouter.toCollection(item.collection);
        },

        /**
         * Starts the list-toolbar and the datagrid
         */
        startDatagrid: function() {
            // init list-toolbar and datagrid
            var locale = UserSettingsManager.getMediaLocale();
            this.sandbox.sulu.initListToolbarAndList.call(this,
                'media',
                '/admin/api/media/fields?locale=' + locale,
                {
                    el: this.$find(constants.toolbarSelector),
                    instanceName: this.options.instanceName,
                    template: this.sandbox.sulu.buttons.get({
                        mediaDecoratorDropdown: {}
                    })
                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/media?orderBy=media.created&orderSort=desc&locale=' + locale,
                    searchFields: ['name', 'title', 'description'],
                    view: UserSettingsManager.getMediaListView(),
                    pagination: UserSettingsManager.getMediaListPagination(),
                    resultKey: 'media',
                    actionCallback: this.actionCallback.bind(this),
                    viewOptions: {
                        table: {
                            selectItem: true,
                            actionIconColumn: 'name',
                            noImgIcon: function(item) {
                                return FileIcons.getByMimeType(item.mimeType);
                            },
                            badges: [
                                {
                                    column: 'title',
                                    callback: function(item, badge) {
                                        if (item.locale !== UserSettingsManager.getMediaLocale()) {
                                            badge.title = item.locale;

                                            return badge;
                                        }
                                    }.bind(this)
                                }
                            ],
                            emptyIcon: 'fa-file-o'
                        },
                        'datagrid/decorators/masonry-view': {
                            selectable: false,
                            noImgIcon: function(item) {
                                return FileIcons.getByMimeType(item.mimeType);
                            },
                            emptyIcon: 'fa-file-o',
                            locale: locale
                        }
                    },
                    paginationOptions: {
                        'infinite-scroll': {
                            reachedBottomMessage: 'public.reached-list-end',
                            scrollOffset: 500
                        }
                    }
                }
            );
        }
    };
});
