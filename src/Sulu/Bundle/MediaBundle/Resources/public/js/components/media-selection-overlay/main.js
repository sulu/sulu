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
define(['sulumedia/collection/collections', 'sulumedia/model/collection'], function(Collections, Collection) {

    'use strict';

    var defaults = {
            eventNamespace: 'sulu.media-selection-overlay',
            preselectedIds: [],
            instanceName: null

        },

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
                    unselectOnBackgroundClick: false
                }
            },
            thumbnailLarge: {
                itemId: 'big-thumbnails',
                name: 'thumbnail',
                thViewOptions: {
                    large: true,
                    unselectOnBackgroundClick: false
                }
            }
        },

        constants = {
            lastVisitedCollectionKey: 'last-visited-collection',
            listViewStorageKey: 'mediaOverlayListView'
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
                '       <div class="media-selection-overlay-header" ondragstart="return false;">',
                '           <div class="media-selection-overlay-toolbar-container"></div>',
                '           <div id="selected-images-count" class="media-selection-overlay-selected-info"></div>',
                '       </div>',
                '       <div class="media-selection-overlay-content-area" ondragstart="return false;">',
                '           <div class="media-selection-overlay-content-title"><%= title %></div>',
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
        scrollToBottom = function() {
            this.sandbox.dom.scrollAnimate(
                this.sandbox.dom.height('.media-selection-overlay-datagrid-container'),
                '.media-selection-overlay-content'
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
                collectionTitle = this.sandbox.translate('media-selection.overlay.all-images');

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
                'husky.data-navigation.' + this.options.instanceName + '.select',
                dataNavigationSelectHandler.bind(this)
            );

            // change datagrid to table
            this.sandbox.on(
                'sulu.list-toolbar.media-selection-overlay.' + this.options.instanceName + '.change.table',
                function() {
                    this.sandbox.emit(
                        'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.view.change',
                        'table'
                    );
                    this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'table');
                }.bind(this)
            );

            // change datagrid to thumbnail small
            this.sandbox.on(
                'sulu.list-toolbar.media-selection-overlay.' + this.options.instanceName + '.change.thumbnail-small',
                function() {
                    this.sandbox.emit(
                        'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.view.change',
                        'thumbnail', {large: false}
                    );
                    this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailSmall');
                }.bind(this)
            );

            // change datagrid to thumbnail large
            this.sandbox.on(
                'sulu.list-toolbar.media-selection-overlay.' + this.options.instanceName + '.change.thumbnail-large',
                function() {
                    this.sandbox.emit(
                        'husky.datagrid.media-selection-overlay.' + this.options.instanceName + '.view.change',
                        'thumbnail', {large: true}
                    );
                    this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailLarge');
                }.bind(this)
            );

            // premark the current view in the toolbar
            this.sandbox.on(
                'husky.toolbar.media-selection-overlay.' + this.options.instanceName + '.initialized',
                function() {
                    this.sandbox.emit(
                        'husky.toolbar.media-selection-overlay.' + this.options.instanceName + '.item.mark',
                        listViews[this.listView].itemId
                    );
                }.bind(this)
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
                'sulu.list-toolbar.media-selection-overlay.' + this.options.instanceName + '.add',
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
        },

        /**
         * Changes the dropzone url and inserts a given collection id
         * @param collectionId
         */
        changeUploadCollection = function(collectionId) {
            this.uploadCollection = collectionId;
            this.sandbox.emit(
                    'husky.dropzone.media-selection-overlay.' + this.options.instanceName + '.change-url',
                    '/admin/api/media?collection=' + collectionId);
        },

        /**
         * starts the overlay component
         */
        startOverlay = function() {
            var $element = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $element);
            this.listView = this.sandbox.sulu.getUserSetting(constants.listViewStorageKey) || 'thumbnailSmall';

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
                                    title: this.sandbox.translate('media-selection.overlay.all-images')
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
                                noData: '',
                                title: this.sandbox.translate('navigation.media.collections'),
                                addButton: '',
                                search: this.sandbox.translate('navigation.media.collections.search')
                            }
                        }
                    }
                ]);

                this.sandbox.sulu.initListToolbarAndList.call(
                    this,
                    'mediaOverlay',
                    [
                        {
                            name: 'id',
                            translation: 'public.id',
                            disabled: true,
                            default: false,
                            sortable: true,
                            type: '',
                            width: '50px',
                            minWidth: '',
                            editable: false,
                            class: ''
                        },
                        {
                            name: 'thumbnails',
                            translation: 'media.media.thumbnails',
                            disabled: false,
                            default: true,
                            sortable: true,
                            type: 'thumbnails',
                            width: '',
                            minWidth: '',
                            editable: false,
                            class: ''
                        },
                        {
                            name: 'title',
                            translation: 'public.title',
                            disabled: false,
                            default: false,
                            sortable: true,
                            type: 'title',
                            width: '',
                            minWidth: '',
                            editable: false,
                            class: ''
                        },
                        {
                            name: 'size',
                            translation: 'media.media.size',
                            disabled: false,
                            default: true,
                            sortable: true,
                            type: 'bytes',
                            width: '',
                            minWidth: '',
                            editable: false,
                            class: ''
                        }
                    ],
                    {
                        el: this.$el.find('.media-selection-overlay-toolbar-container'),
                        instanceName: 'media-selection-overlay.' + this.options.instanceName,
                        showTitleAsTooltip: false,
                        template: [
                            {
                                id: 'add',
                                icon: 'plus-circle',
                                title: this.sandbox.translate('media-selection.list-toolbar.upload-info'),
                                hidden: true,
                                callback: function() {
                                    this.sandbox.emit(
                                        'husky.dropzone.media-selection-overlay.' +
                                        this.options.instanceName + '.open-data-source'
                                    );
                                }.bind(this)
                            },
                            {
                                id: 'change',
                                icon: 'th-large',
                                dropdownOptions: {
                                    markSelected: true
                                },
                                dropdownItems: [
                                    {
                                        id: 'small-thumbnails',
                                        title: this.sandbox.translate('sulu.list-toolbar.small-thumbnails'),
                                        callback: function() {
                                            this.sandbox.emit(
                                                'sulu.list-toolbar.media-selection-overlay.' +
                                                this.options.instanceName + '.change.thumbnail-small'
                                            );
                                        }.bind(this)
                                    },
                                    {
                                        id: 'big-thumbnails',
                                        title: this.sandbox.translate('sulu.list-toolbar.big-thumbnails'),
                                        callback: function() {
                                            this.sandbox.emit(
                                                'sulu.list-toolbar.media-selection-overlay.' +
                                                this.options.instanceName + '.change.thumbnail-large'
                                            );
                                        }.bind(this)
                                    },
                                    {
                                        id: 'table',
                                        title: this.sandbox.translate('sulu.list-toolbar.table'),
                                        callback: function() {
                                            this.sandbox.emit(
                                                'sulu.list-toolbar.media-selection-overlay.' +
                                                this.options.instanceName + '.change.table'
                                            );
                                        }.bind(this)
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        el: this.$el.find('.media-selection-overlay-datagrid-container'),
                        url: '/admin/api/media?orderBy=media.changed&orderSort=DESC',
                        view: listViews[this.listView].name,
                        resultKey: 'media',
                        instanceName: 'media-selection-overlay.' + this.options.instanceName,
                        preselected: this.options.preselectedIds,
                        sortable: false,
                        viewSpacingBottom: 180,
                        viewOptions: {
                            thumbnail: listViews[this.listView].thViewOptions || {}
                        },
                        paginationOptions: {
                            dropdown: {
                                verticalAlignment: 'top'
                            }
                        }
                    }
                );

                this.sandbox.start([
                    {
                        name: 'dropzone@husky',
                        options: {
                            el: this.$el.find('.media-selection-overlay-dropzone-container'),
                            url: '/admin/api/media',
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
            this.newCollection = new Collection();
            this.collectionArray = null;
            this.newCollectionId = null;

            // init vars
            this.uploadCollection = null;

            bindCustomEvents.call(this);

            // init overlays
            startOverlay.call(this);
        }
    };
});
