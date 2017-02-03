/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'config',
    'services/sulumedia/user-settings-manager',
    'sulusecurity/services/security-checker',
    'services/sulumedia/file-icons',
    'text!./skeleton.html'
], function(
    Config,
    UserSettingsManager,
    SecurityChecker,
    FileIcons,
    skeletonTemplate
) {
    'use strict';

    var constants = {
        childrenSelector: '.children-container',
        toolbarSelector: '.list-toolbar-container',
        datagridSelector: '.datagrid-container',
        dropzoneSelector: '.dropzone-container'
    };

    return {
        events: {
            names: {
                folderClicked: {
                    postFix: 'folder.clicked'
                },
                folderBreadcrumbClicked: {
                    postFix: 'folder.breadcrumb-clicked'
                },
                folderAddClicked: {
                    postFix: 'folder.add-clicked'
                },
                assetClicked: {
                    postFix: 'asset.clicked'
                },
                assetAdded: {
                    postFix: 'asset.added'
                },
                assetRemoved: {
                    postFix: 'asset.removed'
                },
                assetEditClicked: {
                    postFix: 'asset.edit-clicked'
                },
                assetDeleteClicked: {
                    postFix: 'asset.delete-clicked'
                },
                assetMoveClicked: {
                    postFix: 'asset.move-clicked'
                }
            },
            namespace: 'sulu.collection-view.'
        },

        defaults: {
            options: {
                data: null,
                dropzoneOverlayContainer: '.content-column',
                assetActions: [],
                assetTypes: [],
                assetSelectOnClick: false,
                assetSingleSelect: false,
                assetShowActionIcon: true,
                assetPreselected: [],
                assetHasEdit: true,
                assetHasDelete: true,
                assetHasMove: true,
                assetHasSelectedCounter: true,
                parentContainer: null
            },
            templates: {
                skeleton: skeletonTemplate,
                childrenUrl: '/admin/api/collections<% if (!!collection) { %>/<%= collection %><% } %>?locale=<%= locale %>&sortBy=title<% if (!!collection) { %>&depth=1<% } %>',
                mediaUrl: '/admin/api/media?locale=<%= locale %><% if (!!types) { %>&types=<%= types %><%}%><% if (!!collection) { %>&collection=<%= collection %><% } %>',
                uploadUrl: '/admin/api/media?collection=<%= id %>&locale=<%= locale %>'
            }
        },

        initialize: function() {
            this.data = this.options.data;

            this.render();

            this.bindDatagridEvents();
            this.bindDropzoneEvents();
            this.bindListToolbarEvents();
            this.bindBreadcrumbEvents();
        },

        render: function () {
            var whenChildrenLoaded, whenDatagridLoaded;

            this.sandbox.dom.html(this.$el, this.templates.skeleton({
                title: this.data.title
            }));

            this.sandbox.start([{
                name: 'breadcrumbs@suluadmin',
                options: {
                    el: this.$el.find('.sulu-breadcrumb'),
                    breadcrumbs: this.getBreadcrumb(),
                    instanceName: this.options.instanceName
                }
            }]);

            whenChildrenLoaded = this.startChildrenTiles();
            whenDatagridLoaded = this.startDatagrid();

            $.when(whenChildrenLoaded, whenDatagridLoaded).done(function () {
                var scrollAt = this.$find(constants.toolbarSelector).position().top
                    - this.$find(constants.toolbarSelector).height();
                this.sandbox.stickyToolbar.enable(this.$el, scrollAt);
            }.bind(this));

            if (SecurityChecker.hasPermission(this.data, 'add')) {
                this.startDropzone();
            }
        },

        bindDatagridEvents: function() {
            this.sandbox.on(
                'husky.datagrid.' + this.data.id + '.children.tiles.add-clicked',
                this.events.folderAddClicked
            );

            this.sandbox.on(
                'husky.datagrid.' + this.options.instanceName + '.number.selections',
                function(selectedItems) {
                    var method = (selectedItems > 0) ? 'enable' : 'disable',
                        prefix = 'husky.toolbar.' + this.options.instanceName + '.item.';

                    this.sandbox.emit(prefix + method, 'media-move', false);
                    this.sandbox.emit(prefix + method, 'editSelected', false);
                    this.sandbox.emit(prefix + method, 'deleteSelected', false);
                }.bind(this)
            );

            this.sandbox.on('sulu.list-toolbar.add', function() {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.show-popup');
            }.bind(this));

            this.sandbox.on(
                'husky.datagrid.' + this.options.instanceName + '.item.select',
                this.events.assetAdded
            );

            this.sandbox.on(
                'husky.datagrid.' + this.options.instanceName + '.item.deselect',
                this.events.assetRemoved
            );
        },

        bindDropzoneEvents: function()
        {
            this.sandbox.on('husky.dropzone.' + this.options.instanceName + '.success', function(file, mediaResponse) {
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                mediaResponse.type = mediaResponse.type.name;
                this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.records.add', [mediaResponse]);
            }, this);

            // disable dropzone popup when overlay is active
            this.sandbox.on('sulu.collection-add.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.collection-edit.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.collection-select.move-collection.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.collection-select.move-media.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.media-edit.initialized', this.disableDropzone.bind(this));
            this.sandbox.on('sulu.permission-settings.initialized', this.disableDropzone.bind(this));

            // enable dropzone popup on overlay close
            this.sandbox.on('sulu.collection-add.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.collection-edit.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.collection-select.move-collection.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.collection-select.move-media.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.media-edit.closed', this.enableDropzone.bind(this));
            this.sandbox.on('sulu.permission-settings.closed', this.enableDropzone.bind(this));
        },

        bindListToolbarEvents: function() {
            var changeEvent = 'husky.datagrid.' + this.options.instanceName + '.change';

            this.sandbox.on('sulu.toolbar.change.table', function() {
                UserSettingsManager.setMediaListView('table');
                UserSettingsManager.setMediaListPagination('dropdown');

                this.sandbox.emit(
                    changeEvent,
                    1,
                    UserSettingsManager.getDropdownPageSize(),
                    'table',
                    [],
                    'dropdown'
                );

                this.sandbox.stickyToolbar.reset(this.$el);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.change.masonry', function() {
                UserSettingsManager.setMediaListView('datagrid/decorators/masonry-view');
                UserSettingsManager.setMediaListPagination('infinite-scroll');

                this.sandbox.emit(
                    changeEvent,
                    1,
                    UserSettingsManager.getInfinityPageSize(),
                    'datagrid/decorators/masonry-view',
                    null,
                    'infinite-scroll'
                );

                this.sandbox.stickyToolbar.reset(this.$el);
            }.bind(this));
        },

        bindBreadcrumbEvents: function() {
            this.sandbox.on(
                'sulu.breadcrumbs.' + this.options.instanceName + '.breadcrumb-clicked',
                this.events.folderBreadcrumbClicked
            );
        },

        /**
         * Starts the datagrid showing the children (sub-folders) of the collection
         */
        startChildrenTiles: function() {
            var whenTilesInitialized = $.Deferred();

            if (!this.data.hasSub) {
                this.$find(constants.childrenSelector).remove();
                whenTilesInitialized.resolve();
                return;
            }

            this.sandbox.start([{
                name: 'datagrid@husky',
                options: {
                    el: this.$find(constants.childrenSelector),
                    url: this.templates.childrenUrl({
                        collection: this.data.id,
                        locale: this.options.locale
                    }),
                    instanceName: this.data.id + '.children',
                    view: 'tiles',
                    resultKey: 'collections',
                    viewOptions: {
                        tiles: {
                            fields: {
                                description: ['objectCount']
                            },
                            translations: {
                                addNew: 'sulu.media.add-collection'
                            }
                        }
                    },
                    pagination: false,
                    actionCallback: function(id) {
                        this.events.folderClicked(id);
                    }.bind(this),
                    matchings: [
                        {
                            name: 'id'
                        },
                        {
                            name: 'title'
                        },
                        {
                            name: 'objectCount',
                            type: 'count'
                        }
                    ]
                }
            }]);

            this.sandbox.on('husky.datagrid.' + this.data.id + '.children.view.rendered', function() {
                whenTilesInitialized.resolve();
            });

            return whenTilesInitialized;
        },

        /**
         * Start the list toolbar and the datagrid
         */
        startDatagrid: function() {
            var whenDatagridInitialized = $.Deferred(),

                paginationOptions = {
                    'infinite-scroll': {
                        reachedBottomMessage: 'public.reached-list-end',
                        scrollOffset: 500
                    }
                };

            if (!!this.options.parentContainer) {
                paginationOptions['infinite-scroll'].scrollContainer = this.options.parentContainer;
            }

            this.sandbox.sulu.initListToolbarAndList.call(this,
                'media',
                '/admin/api/media/fields?locale=' + this.options.locale + '&sortBy=created&sortOrder=desc',
                {
                    el: this.$find(constants.toolbarSelector),
                    instanceName: this.options.instanceName,
                    template: this.sandbox.sulu.buttons.get(this.getEditButtons())
                },
                {
                    el: this.$find(constants.datagridSelector),
                    instanceName: this.options.instanceName,
                    url: this.templates.mediaUrl({
                        collection: this.data.id,
                        locale: this.options.locale,
                        types: this.options.assetTypes.join(',')
                    }),
                    searchFields: ['name', 'title', 'description'],
                    selectedCounter: this.options.assetHasSelectedCounter,
                    view: UserSettingsManager.getMediaListView(),
                    pagination: UserSettingsManager.getMediaListPagination(),
                    resultKey: 'media',
                    preselected: this.options.assetPreselected,
                    actionCallback: function(id, item) {
                        this.events.assetClicked(id, item);
                    }.bind(this),
                    viewOptions: {
                        table: {
                            actionIcon: !!this.options.assetShowActionIcon ?
                                (!!this.options.assetSelectOnClick ? 'check' : 'pencil') : null,
                            actionIconColumn: !!this.options.assetSelectOnClick ? null : 'name',
                            selectItem: !this.options.assetSingleSelect ? {type: 'checkbox', inFirstCell: false} : false,
                            noImgIcon: function(item) {
                                return FileIcons.getByMimeType(item.mimeType);
                            },
                            badges: [
                                {
                                    column: 'title',
                                    callback: function(item, badge) {
                                        if (item.locale !== this.options.locale) {
                                            badge.title = item.locale;

                                            return badge;
                                        }
                                    }.bind(this)
                                }
                            ]
                        },
                        'datagrid/decorators/masonry-view': {
                            selectOnAction: !!this.options.assetSelectOnClick,
                            selectable: !this.options.assetSingleSelect,
                            unselectOnBackgroundClick: false,
                            noImgIcon: function(item) {
                                return FileIcons.getByMimeType(item.mimeType);
                            },
                            actionIcons: this.options.assetActions,
                            locale: this.options.locale
                        }
                    },
                    paginationOptions: paginationOptions
                });

            this.sandbox.on('husky.datagrid.' + this.options.instanceName + '.view.rendered', function() {
                whenDatagridInitialized.resolve();
            });

            return whenDatagridInitialized;
        },

        /**
         * Starts the dropzone component
         */
        startDropzone: function() {
            if (!!this.data.id) {
                this.sandbox.start([
                    {
                        name: 'dropzone@husky',
                        options: {
                            el: this.$find(constants.dropzoneSelector),
                            maxFilesize: Config.get('sulu-media').maxFilesize,
                            url: this.templates.uploadUrl({
                                id: this.data.id,
                                locale: this.options.locale
                            }),
                            method: 'POST',
                            paramName: 'fileVersion',
                            overlayContainer: this.options.dropzoneOverlayContainer,
                            instanceName: this.options.instanceName
                        }
                    }
                ]);
            }
        },

        /**
         * Constructs and returns the buttons for the list-toolbar, dependent on the
         * permissions of the current user.
         *
         * @returns {Object} The buttons for the media edit
         */
        getEditButtons: function() {
            var settingsDropdown = [], buttons = {};

            if (!!this.data.id && SecurityChecker.hasPermission(this.data, 'add')) {
                buttons.add = {
                    options: {
                        showTitle: true,
                        title: 'sulu-media.upload-files',
                        icon: 'cloud-upload',
                        callback: function() {
                            this.sandbox.emit('sulu.list-toolbar.add');
                        }.bind(this)
                    }
                };
            }

            if (!!this.options.assetHasEdit && SecurityChecker.hasPermission(this.data, 'edit')) {
                buttons.editSelected = {
                    options: {
                        callback: function() {
                            this.sandbox.emit(
                                'husky.datagrid.' + this.options.instanceName + '.items.get-selected',
                                this.events.assetEditClicked
                            );
                        }.bind(this)
                    }
                };
            }

            if (!!this.options.assetHasDelete && SecurityChecker.hasPermission(this.data, 'delete')) {
                buttons.deleteSelected = {
                    options: {
                        callback: function() {
                            this.sandbox.emit(
                                'husky.datagrid.' + this.options.instanceName + '.items.get-selected',
                                this.events.assetDeleteClicked
                            );
                        }.bind(this)
                    }
                };
            }

            if (!!this.options.assetHasMove && !!this.data.id && SecurityChecker.hasPermission(this.data, 'edit')) {
                settingsDropdown.push({
                    id: 'media-move',
                    title: this.sandbox.translate('sulu.media.move'),
                    callback: function() {
                        this.sandbox.emit(
                            'husky.datagrid.' + this.options.instanceName + '.items.get-selected',
                            this.events.assetMoveClicked
                        );
                    }.bind(this)
                });
            }

            settingsDropdown.push({
                type: 'columnOptions'
            });

            buttons.settings = {
                options: {
                    dropdownItems: settingsDropdown
                }
            };

            buttons.mediaDecoratorDropdown = {};

            return buttons;
        },


        /**
         * Creates and returns the breadcrumb object, from the current data.
         *
         * @returns {Object} the breadcrumb data
         */
        getBreadcrumb: function() {
            if (!this.data.id) {
                return [];
            }

            var breadcrumbs = [{
                title: 'sulu.media.all-collections',
                icon: 'fa-folder',
                data: {}
            }];

            this.data._embedded.breadcrumb.forEach(function(breadcrumb) {
                breadcrumbs.push({
                    title: breadcrumb.title,
                    icon: 'fa-folder',
                    data: {
                        id: breadcrumb.id
                    }
                });
            }.bind(this));

            return breadcrumbs;
        },

        /**
         * Disable dropzone-popup on drag-over
         */
        disableDropzone: function() {
            this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.disable');
        },

        /**
         * Enable dropzone popup on drag-over
         */
        enableDropzone: function() {
            this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.enable');
        }
    };
});
