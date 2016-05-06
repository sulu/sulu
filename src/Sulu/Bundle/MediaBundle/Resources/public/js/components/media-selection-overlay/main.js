/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Displays an overlay for selecting medias
 *
 * @class MediaSelection
 * @constructor
 */
define([
    'config',
    'sulumedia/collections/collections',
    'sulumedia/models/collection',
    'services/sulumedia/user-settings-manager'
], function(Config, Collections, Collection, UserSettingsManager) {

    'use strict';

    var defaults = {
            eventNamespace: 'sulu.media-selection-overlay',
            preselectedIds: [],
            instanceName: null,
            types: null,
            locale: ''
        },

        /**
         * listens on and opens the overlay
         * @event sulu.media-selection.record-selected
         */
        OPEN = function() {
            return createEventName.call(this, 'open');
        },

        /**
         * listens on and closes the overlay
         * @event sulu.media-selection.record-selected
         */
        CLOSE = function() {
            return createEventName.call(this, 'close');
        },

        /**
         * listens on and resets the selected items
         * @event sulu.media-selection.record-selected
         */
        SET_SELECTED = function() {
            return createEventName.call(this, 'set-selected');
        },

        /**
         * raised when a record has been selected
         * @event sulu.media-selection.record-selected
         */
        RECORD_SELECTED = function() {
            return createEventName.call(this, 'record-selected');
        },

        /**
         * raised when a record has been deselected
         * @event sulu.media-selection.record-deselected
         */
        RECORD_DESELECTED = function() {
            return createEventName.call(this, 'record-deselected');
        },

        /**
         * returns normalized event names
         */
        createEventName = function(postFix) {
            return this.options.eventNamespace +
                '.' + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        templates = {
            overlayContent: [
                '<div class="media-selection-overlay">',
                '   <div class="media-selection-overlay-navigation-container pull-left"></div>',
                '   <div class="media-selection-overlay-content">',
                '       <div class="fa-times media-selection-overlay-close"></div>',
                '       <div class="media-selection-overlay-dropzone-container"></div>',
                '       <h2 class="media-selection-overlay-content-title content-title"><%= title %></h2>',
                '       <div class="media-selection-overlay-content-area" ondragstart="return false;">',
                '           <div class="media-selection-overlay-datagrid-header" ondragstart="return false;">',
                '               <div class="media-selection-overlay-toolbar-container"></div>',
                '               <div id="selected-images-count" class="media-selection-overlay-selected-info"></div>',
                '           </div>',
                '           <div class="media-selection-overlay-datagrid-container"></div>',
                '       </div>',
                '   </div>',
                '</div>'
            ].join(''),

            mediaSelectedInfo: '<%= selectedCounter %> <%= selectedImagesLabel %>'
        },

        /**
         * Scrolls the whole form the the bottom
         */
        scrollToBottom = function() {this.sandbox.dom.scrollAnimate(
                this.sandbox.dom.height('.media-selection-overlay-content-area'),
                '.media-selection-overlay-content-area'
            );
        },

        /**
         * Takes an array of files and adds them to the datagrid
         * @param files {Array} array of files
         */
        addFilesToDatagrid = function(files) {
            for (var i = -1, length = files.length; ++i < length;) {
                files[i].selected = true;
            }
            this.sandbox.emit(
                'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.records.add',
                files,
                scrollToBottom.bind(this)
            );
            this.sandbox.emit('husky.data-navigation.' + this.options.instanceName + '.collections.reload');
        },

        /**
         * Handler method for the data navigation select event
         * @method dataNavigationSelectHandler
         * @param {Object} collection
         */
        dataNavigationSelectHandler = function(collection) {
            var collectionId,
                collectionTitle = this.sandbox.translate('media-selection.overlay.all-medias');

            if (collection) {
                collectionId = collection.id;
                collectionTitle = collection.title;

                this.sandbox.emit(
                    'husky.toolbar.media-selection-overlay.' + this.options.instanceName + '.item.show',
                    'add'
                );
                this.sandbox.emit('husky.dropzone.media-selection-overlay.' + this.options.instanceName + '.enable');
            } else {
                this.sandbox.emit(
                    'husky.toolbar.media-selection-overlay.' + this.options.instanceName + '.item.hide',
                    'add'
                );
                this.sandbox.emit('husky.dropzone.media-selection-overlay.' + this.options.instanceName + '.disable');
            }

            this.sandbox.emit('husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.url.update', {
                collection: collectionId,
                page: 1
            });
            changeUploadCollection.call(this, collectionId);
            this.$el.find('.media-selection-overlay-content-title').html(collectionTitle);
        },

        /**
         * Updates the selected counter of the overlay
         * @param selections The number of selected items
         */
        updateSelectedCounter = function(selections) {
            var template = '', label = '';

            if (selections) {
                label = (selections === 1) ?
                    this.sandbox.translate('media-selection.overlay.selected-image-label') :
                    this.sandbox.translate('media-selection.overlay.selected-images-label');

                template = this.sandbox.util.template(templates.mediaSelectedInfo, {
                    selectedCounter: selections,
                    selectedImagesLabel: label
                });
            }

            this.$el.find('#selected-images-count').html(template);
        },

        /**
         * custom event handling
         */
        bindCustomEvents = function() {
            this.sandbox.on(OPEN.call(this), function() {
                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.open');
            }.bind(this));

            this.sandbox.on(CLOSE.call(this), function() {
                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.close');
            }.bind(this));

            this.sandbox.on(SET_SELECTED.call(this), function(selectedIds) {
                selectedIds = selectedIds || [];
                this.options.preselectedIds = selectedIds;
                this.sandbox.emit(
                    'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.selected.update',
                    selectedIds
                );
                updateSelectedCounter.call(this, (selectedIds || []).length);
            }.bind(this));

            this.sandbox.on(
                'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.item.select',
                function(id, item) {
                    this.sandbox.emit(RECORD_SELECTED.call(this), id, item);
                }.bind(this)
            );

            this.sandbox.on(
                'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.item.deselect',
                function(id) {
                    this.sandbox.emit(RECORD_DESELECTED.call(this), id);
                }.bind(this)
            );

            this.sandbox.on(
                'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.number.selections',
                function(selections) {
                    updateSelectedCounter.call(this, selections);
                }.bind(this)
            );

            this.sandbox.on(
                'husky.data-navigation.' + this.options.instanceName + '.selected',
                dataNavigationSelectHandler.bind(this)
            );

            // if files got uploaded to the server add them to the datagrid
            this.sandbox.on(
                'husky.dropzone.media-selection-overlay.' + this.options.instanceName + '.files-added',
                function(files) {
                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                    addFilesToDatagrid.call(this, files);
                }.bind(this)
            );

            // open data-source folder-overlay
            this.sandbox.on(
                'sulu.toolbar.media-selection-overlay.' + this.options.instanceName + '.add',
                function() {
                    this.sandbox.emit(
                        'husky.dropzone.media-selection-overlay.' + this.options.instanceName + '.open-data-source'
                    );
                }.bind(this)
            );

            this.sandbox.on(
                'husky.overlay.dropzone-media-selection-overlay.' + this.options.instanceName + '.opened',
                function() {
                    this.$el.find('.media-selection-overlay-container').addClass('dropzone-overlay-opened');
                }.bind(this)
            );

            this.sandbox.on(
                'husky.overlay.dropzone-media-selection-overlay.' + this.options.instanceName + '.closed',
                function() {
                    this.$el.find('.media-selection-overlay-container').removeClass('dropzone-overlay-opened');
                }.bind(this)
            );

            // change datagrid view to table
            this.sandbox.on('sulu.toolbar.change.table', function() {
                UserSettingsManager.setMediaListView('table');
                UserSettingsManager.setMediaListPagination('dropdown');

                this.sandbox.emit(
                    'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.change',
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
                    'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.change',
                    1,
                    UserSettingsManager.getInfinityPageSize(),
                    'datagrid/decorators/masonry-view',
                    null,
                    'infinite-scroll'
                );
            }.bind(this));
        },

        /**
         * Changes the dropzone url and inserts a given collection id
         * @param collectionId
         */
        changeUploadCollection = function(collectionId) {
            this.sandbox.emit(
                'husky.dropzone.media-selection-overlay.' + this.options.instanceName + '.change-url',
                '/admin/api/media?collection=' + collectionId + '&locale=' + this.options.locale
            );
        },

        /**
         * starts the overlay component
         */
        startOverlay = function() {
            var $element = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        dragTrigger: '.media-selection-overlay-navigation-container',
                        removeOnClose: false,
                        el: $element,
                        container: this.$el,
                        cssClass: 'media-selection-overlay-container',
                        instanceName: this.options.instanceName,
                        skin: 'wide',
                        supportKeyInput: false,
                        slides: [
                            {
                                data: this.sandbox.util.template(templates.overlayContent, {
                                    title: this.sandbox.translate('media-selection.overlay.all-medias')
                                })
                            }
                        ]
                    }
                }
            ]);

            this.sandbox.once('husky.overlay.' + this.options.instanceName + '.opened', function() {

                this.$el.on('click', '.media-selection-overlay-close', function() {
                    this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.close');
                }.bind(this));

                this.sandbox.start([
                    {
                        name: 'data-navigation@husky',
                        options: {
                            el: this.$el.find('.media-selection-overlay-navigation-container'),
                            resultKey: 'collections',
                            showAddButton: false,
                            rootUrl: '/admin/api/collections?sortBy=title',
                            url: '/admin/api/collections?sortBy=title',
                            nameKey: 'title',
                            instanceName: this.options.instanceName,
                            globalEvents: false,
                            translates: {
                                noData: this.sandbox.translate('navigation.media.collections.empty'),
                                title: this.sandbox.translate('navigation.media.collections'),
                                addButton: '',
                                search: this.sandbox.translate('navigation.media.collections.search')
                            }
                        }
                    }
                ]);

                var buttons = this.sandbox.sulu.buttons.get({
                    add: {
                        options: {
                            id: 'add',
                            title: this.sandbox.translate('media-selection.list-toolbar.upload-info'),
                            hidden: true,
                            callback: function() {
                                this.sandbox.emit(
                                    'husky.dropzone.media-selection-overlay.' +
                                    this.options.instanceName + '.open-data-source'
                                );
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
                });

                this.sandbox.sulu.initListToolbarAndList.call(
                    this,
                    'mediaOverlay',
                    [
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
                    ],
                    {
                        el: this.$el.find('.media-selection-overlay-toolbar-container'),
                        instanceName: 'media-selection-overlay.' + this.options.instanceName,
                        showTitleAsTooltip: false,
                        template: buttons
                    },
                    {
                        el: this.$el.find('.media-selection-overlay-datagrid-container'),
                        url: [
                            '/admin/api/media?locale=', this.options.locale,
                            '&orderBy=media.created&orderSort=DESC',
                            (!!this.options.types ? '&types=' + this.options.types : '')
                        ].join(''),
                        view: UserSettingsManager.getMediaListView(),
                        pagination: UserSettingsManager.getMediaListPagination(),
                        resultKey: 'media',
                        instanceName: 'media-selection-overlay.' + this.options.instanceName,
                        preselected: this.options.preselectedIds,
                        viewSpacingBottom: 180,
                        viewOptions: {
                            table: {
                                actionIconColumn: 'name',
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
                                selectable: true,
                                selectOnAction: true,
                                unselectOnBackgroundClick: false,
                                locale: this.options.locale
                            }
                        },
                        paginationOptions: {
                            'infinite-scroll': {
                                reachedBottomMessage: 'public.reached-list-end',
                                scrollContainer: '.media-selection-overlay-content-area',
                                scrollOffset: 500
                            }
                        }
                    }
                );

                this.sandbox.start([
                    {
                        name: 'dropzone@husky',
                        options: {
                            el: this.$el.find('.media-selection-overlay-dropzone-container'),
                            maxFilesize: Config.get('sulu-media').maxFilesize,
                            url: '/admin/api/media?locale=' + this.options.locale,
                            method: 'POST',
                            paramName: 'fileVersion',
                            instanceName: 'media-selection-overlay.' + this.options.instanceName,
                            dropzoneEnabled: false,
                            cancelUploadOnOverlayClick: true
                        }
                    }
                ]);

            }.bind(this));
        };

    return {
        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // init collection
            this.collections = new Collections();

            bindCustomEvents.call(this);

            // init overlays
            startOverlay.call(this);
        }
    };
});
