/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Overlay for media-link plugin.
 *
 * @class media-selection/overlay
 * @constructor
 */
define([
    'underscore',
    'config',
    'services/sulumedia/user-settings-manager',
    'text!./skeleton.html'
], function(_, Config, UserSettingsManager, skeletonTemplate) {

    'use strict';

    var fields = [
        {
            name: 'id',
            translation: 'public.id',
            disabled: true,
            default: false,
            sortable: true
        },
        {
            name: 'thumbnails',
            translation: 'media.media.thumbnails',
            disabled: false,
            default: true,
            sortable: true,
            type: 'thumbnails'
        },
        {
            name: 'title',
            translation: 'public.title',
            disabled: false,
            default: false,
            sortable: true,
            type: 'title'
        },
        {
            name: 'size',
            translation: 'media.media.size',
            disabled: false,
            default: true,
            sortable: true,
            type: 'bytes'
        }
    ];

    return {

        defaults: {
            options: {
                preselected: [],
                singleSelect: false,
                removeable: true,
                instanceName: null,
                types: null,
                removeOnClose: false,
                openOnStart: false,
                saveCallback: function(label) {
                },
                removeCallback: function() {
                }
            },

            templates: {
                skeleton: skeletonTemplate,
                uploadUrl: '/admin/api/media?collection=<%= id %>&locale=<%= locale %><% if (!!types) {%>&types=<%= types %><% } %>'
            },

            translations: {
                title: 'sulu-media.selection.overlay.title',
                save: 'sulu-media.selection.overlay.save',
                remove: 'public.remove',
                uploadInfo: 'media-selection.list-toolbar.upload-info',
                selectedTitle: 'sulu-media.selection.overlay.selected-title',
                allMedias: 'media-selection.overlay.all-medias',
                noData: 'navigation.media.collections.empty',
                navigationTitle: 'navigation.media.collections',
                search: 'navigation.media.collections.search'
            }
        },

        events: {
            names: {
                setItems: {
                    postFix: 'set-items',
                    type: 'on'
                },
                open: {
                    postFix: 'open',
                    type: 'on'
                }
            },
            namespace: 'sulu.media-selection-overlay.'
        },

        loadedItems: {},

        initialize: function() {
            this.initializeDialog();
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.data-navigation.' + this.options.instanceName + '.selected', this.dataNavigationSelectHandler.bind(this));

            if (!!this.options.removeOnClose) {
                this.sandbox.on('husky.overlay.' + this.options.instanceName + '.closed', function() {
                    this.sandbox.stop();
                }.bind(this));
            }

            // if files got uploaded to the server add them to the datagrid
            this.sandbox.on(
                'husky.dropzone.' + this.options.instanceName + '.files-added',
                function(files) {
                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                    if (!!this.options.singleSelect) {
                        this.setItems([files[0]]);
                        this.save();

                        return this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.close');
                    }

                    this.addFilesToDatagrid.call(this, files);
                }.bind(this)
            );

            // open data-source folder-overlay
            this.sandbox.on('sulu.toolbar.' + this.options.instanceName + '.add', function() {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.open-data-source');
                }.bind(this)
            );

            this.sandbox.on(
                'husky.overlay.dropzone-' + this.options.instanceName + '.opened',
                function() {
                    this.$el.find('.single-media-selection').addClass('dropzone-overlay-opened');
                }.bind(this)
            );

            this.sandbox.on(
                'husky.overlay.dropzone-' + this.options.instanceName + '.closed',
                function() {
                    this.$el.find('.single-media-selection').removeClass('dropzone-overlay-opened');
                }.bind(this)
            );

            // change datagrid view to table
            this.sandbox.on('sulu.toolbar.change.table', function() {
                UserSettingsManager.setMediaListView('table');
                UserSettingsManager.setMediaListPagination('dropdown');

                this.sandbox.emit(
                    'husky.datagrid.' + this.options.instanceName + '.change',
                    1,
                    UserSettingsManager.getDropdownPageSize(),
                    'table',
                    [],
                    'dropdown'
                );
            }.bind(this));

            // change datagrid view to masonry
            this.sandbox.on('sulu.toolbar.change.masonry', function() {
                UserSettingsManager.setMediaListView('datagrid/decorators/masonry-view');
                UserSettingsManager.setMediaListPagination('infinite-scroll');

                this.sandbox.emit(
                    'husky.datagrid.' + this.options.instanceName + '.change',
                    1,
                    UserSettingsManager.getInfinityPageSize(),
                    'datagrid/decorators/masonry-view',
                    null,
                    'infinite-scroll'
                );
            }.bind(this));

            this.events.setItems(this.setItems.bind(this));
            this.events.open(function() {
                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.open');
            }.bind(this));

            this.sandbox.on('husky.datagrid.' + this.options.instanceName + '.item.select', function(id, item) {
                if (!this.addItem(item)) {
                    return;
                }

                this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '-selected.record.add', item);
            }.bind(this));

            this.sandbox.on('husky.datagrid.' + this.options.instanceName + '.item.deselect', function(id) {
                this.removeItem(id);

                this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '-selected.record.remove', id);
            }.bind(this));

            this.sandbox.on('husky.datagrid.' + this.options.instanceName + '.loaded', function(data) {
                _.each(data._embedded.media, function(item) {
                    this.loadedItems[item.id] = item;
                }.bind(this));
            }.bind(this));
        },

        save: function() {
            this.options.saveCallback(this.getData());
        },

        getData: function() {
            return _.map(this.items, function(item) {
                if (!this.loadedItems || !this.loadedItems[item.id]) {
                    return item;
                }

                return this.loadedItems[item.id];
            }.bind(this));
        },

        setItems: function(items) {
            this.items = items;

            var ids = _.map(this.items, function(item) {
                return parseInt(item.id);
            });

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.selected.update', ids);
            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '-selected.url.update', {
                ids: ids.join(','),
                limit: ids.length
            });
        },

        addItem: function(item) {
            if (this.has(item.id)) {
                return false;
            }

            this.items.push(item);

            return true;
        },

        removeItem: function(id) {
            this.items = _.filter(this.items, function(item) {
                return item.id !== id;
            });
        },

        has: function(id) {
            return !!_.filter(this.items, function(item) {
                return item.id === id;
            }).length;
        },

        dataNavigationSelectHandler: function(collection) {
            var id, title = this.sandbox.translate('media-selection.overlay.all-medias');

            if (collection) {
                id = collection.id;
                title = collection.title;

                this.sandbox.emit('husky.toolbar.' + this.options.instanceName + '.item.show', 'add');
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.enable');

                this.hideSelected();
            } else {
                this.sandbox.emit('husky.toolbar.' + this.options.instanceName + '.item.hide', 'add');
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.disable');

                this.showSelected();
            }

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.url.update', {
                collection: id,
                page: 1
            });

            this.changeUploadCollection(id);
            this.$el.find('.list-title').text(title);
        },

        hideSelected: function() {
            this.$el.find('.selected-container').hide();

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '-selected.masonry.refresh');
        },

        showSelected: function() {
            this.$el.find('.selected-container').show();

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '-selected.masonry.refresh');
        },

        changeUploadCollection: function(id) {
            this.sandbox.emit(
                'husky.dropzone.' + this.options.instanceName + '.change-url', this.templates.uploadUrl({
                    id: id,
                    locale: this.options.locale,
                    types: this.options.types
                })
            );
        },

        addFilesToDatagrid: function(files) {
            for (var i = -1, length = files.length; ++i < length;) {
                files[i].selected = true;
            }

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.records.add', files);
            this.sandbox.emit('husky.data-navigation.' + this.options.instanceName + '.collections.reload');
        },

        initializeDialog: function() {
            var $element = this.sandbox.dom.createElement('<div class="overlay-container"/>');
            this.sandbox.dom.append(this.$el, $element);

            var buttons = [
                {
                    type: 'cancel',
                    align: 'left'
                }
            ];

            if (!!this.options.removeable) {
                buttons.push({
                    text: this.translations.remove,
                    align: 'center',
                    classes: 'just-text',
                    callback: function() {
                        this.options.removeCallback();
                        this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.close');
                    }.bind(this)
                });
            }

            if (!this.options.singleSelect) {
                buttons.push({
                    type: 'ok',
                    text: this.translations.save,
                    align: 'right'
                });
            }

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: this.options.openOnStart,
                        removeOnClose: this.options.removeOnClose,
                        el: $element,
                        container: this.$el,
                        skin: 'wide',
                        cssClass: 'single-media-selection',
                        instanceName: this.options.instanceName,
                        slides: [
                            {
                                title: this.translations.title,
                                data: this.templates.skeleton(
                                    {title: this.translations.allMedias, selectedTitle: this.translations.selectedTitle}
                                ),
                                buttons: buttons,
                                okCallback: function() {
                                    this.save();
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]).then(function() {
                this.setItems(this.options.preselected);

                if (!!this.options.singleSelect) {
                    return this.initializeFormComponents();
                }

                this.sandbox.once('husky.overlay.' + this.options.instanceName + '.opened', function() {
                    this.initializeFormComponents();
                }.bind(this));
            }.bind(this));
        },

        initializeFormComponents: function() {
            this.sandbox.start(
                [
                    {
                        name: 'data-navigation@husky',
                        options: {
                            el: this.$el.find('.navigation-container'),
                            resultKey: 'collections',
                            showAddButton: false,
                            instanceName: this.options.instanceName,
                            rootUrl: '/admin/api/collections?sortBy=title',
                            url: '/admin/api/collections?sortBy=title',
                            nameKey: 'title',
                            globalEvents: false,
                            translates: {
                                noData: this.translations.noData,
                                title: this.translations.navigationTitle,
                                addButton: '',
                                search: this.translations.search
                            }
                        }
                    },
                    {
                        name: 'dropzone@husky',
                        options: {
                            el: this.$el.find('.dropzone-container'),
                            maxFilesize: Config.get('sulu-media').maxFilesize,
                            url: '/admin/api/media?locale=' + this.options.locale,
                            method: 'POST',
                            paramName: 'fileVersion',
                            instanceName: this.options.instanceName,
                            dropzoneEnabled: false,
                            cancelUploadOnOverlayClick: true
                        }
                    }
                ]
            );

            if (!!this.items.length || !this.options.singleSelect) {

                var ids = _.map(this.items, function(item) {
                    return item.id;
                });

                this.sandbox.start([
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: this.$el.find('.selected-datagrid-container'),
                            url: [
                                '/admin/api/media?locale=', this.options.locale,
                                '&fields=id,thumbnails,title,size',
                                '&orderBy=media.created&orderSort=desc&ids=',
                                ids.join(','),
                                '&limit=', ids.length
                            ].join(''),
                            matchings: fields,
                            view: 'datagrid/decorators/masonry-view',
                            resultKey: 'media',
                            instanceName: this.options.instanceName + '-selected',
                            viewSpacingBottom: 180,
                            selectedCounter: false,
                            pagination: false,
                            viewOptions: {
                                'datagrid/decorators/masonry-view': {
                                    selectable: false,
                                    locale: this.options.locale
                                }
                            }
                        }
                    }
                ]);

                this.sandbox.once('husky.datagrid.' + this.options.instanceName + '-selected.loaded', function(data) {
                    if (this.options.singleSelect && data.total === 0) {
                        // the selected value is not valid - selected datagrid should never be displayed
                        return this.$el.find('.selected-container').remove();
                    }

                    this.showSelected();
                }.bind(this));
            }

            this.sandbox.sulu.initListToolbarAndList.call(
                this,
                'mediaOverlay',
                fields,
                {
                    el: this.$el.find('.list-toolbar-container'),
                    showTitleAsTooltip: false,
                    instanceName: this.options.instanceName,
                    hasSearch: false,
                    template: this.sandbox.sulu.buttons.get({
                        add: {
                            options: {
                                id: 'add',
                                title: this.translations.uploadInfo,
                                hidden: true,
                                callback: function() {
                                    this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.open-data-source');
                                }.bind(this)
                            }
                        },
                        mediaDecoratorDropdown: {
                            options: {
                                id: 'change',
                                dropdownOptions: {
                                    markSelected: true
                                }
                            }
                        }
                    })
                },
                {
                    el: this.$el.find('.list-datagrid-container'),
                    url: [
                        '/admin/api/media?locale=', this.options.locale, '&orderBy=media.created&orderSort=desc'
                    ].join(''),
                    view: UserSettingsManager.getMediaListView(),
                    pagination: UserSettingsManager.getMediaListPagination(),
                    resultKey: 'media',
                    instanceName: this.options.instanceName,
                    viewSpacingBottom: 180,
                    selectedCounter: false,
                    preselected: _.map(this.items, function(item) {
                        return parseInt(item.id);
                    }),
                    actionCallback: !!this.options.singleSelect ? function(id, item) {
                        this.setItems([item]);
                        this.save();
                    }.bind(this) : null,
                    viewOptions: {
                        table: {
                            actionIcon: 'check',
                            actionIconColumn: !!this.options.singleSelect ? 'title' : null,
                            selectItem: !this.options.singleSelect ? {type: 'checkbox', inFirstCell: false} : false,
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
                            selectable: !this.options.singleSelect,
                            locale: this.options.locale,
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
                        }
                    },
                    paginationOptions: {
                        'infinite-scroll': {
                            reachedBottomMessage: 'public.reached-list-end',
                            scrollContainer: '.list-container',
                            scrollOffset: 500
                        }
                    }
                }
            );
        }
    };
});
