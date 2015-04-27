/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * handles media selection
 *
 * @class MediaSelection
 * @constructor
 */
define(['sulumedia/collection/collections', 'sulumedia/model/collection'], function(Collections, Collection) {

    'use strict';

    var defaults = {
            eventNamespace: 'sulu.media-selection',
            thumbnailKey: 'thumbnails',
            thumbnailSize: '50x50',
            resultKey: 'media',
            dataAttribute: 'media-selection',
            dataDefault: {
                displayOption: 'top',
                ids: []
            },
            hideConfigButton: true,
            translations: {
                noContentSelected: 'media-selection.nomedia-selected',
                addImages: 'media-selection.add-images',
                choose: 'public.choose',
                collections: 'media-selection.collections',
                upload: 'media-selection.upload-new',
                collection: 'media-selection.upload-to-collection',
                createNewCollection: 'media-selection.create-new-collection',
                newCollection: 'media-selection.new-collection'
            }
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
         * raised when all overlay components returned their value
         * @event sulu.media-selection.input-retrieved
         */
        INPUT_RETRIEVED = function() {
            return createEventName.call(this, 'input-retrieved');
        },

        /**
         * raised when data has returned from the ajax request
         * @event sulu.media-selection.record-selected
         */
        RECORD_SELECTED = function() {
            return createEventName.call(this, 'record-selected');
        },

        /**
         * raised when data has returned from the ajax request
         * @event sulu.media-selection.record-deselected
         */
        RECORD_DESELECTED = function() {
            return createEventName.call(this, 'record-deselected');
        },

        /**
         * returns normalized event names
         */
        createEventName = function(postFix) {
            return this.options.eventNamespace + '.' + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        templates = {
            mediaSelection: function(options) {
                return [
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
                    '           <div class="media-selection-overlay-content-title">' + options.contentDefaultTitle + '</div>',
                    '           <div class="media-selection-overlay-datagrid-container"></div>',
                    '       </div>',
                    '   </div>',
                    '</div>'
                ].join('');
            },

            mediaSelectedInfo: function(options) {
                return options.selectedCounter + ' ' + options.selectedImagesLabel;
            },

            contentItem: function(title, thumbnails) {
                return [
                    '   <img src="', thumbnails['50x50'], '"/>',
                    '   <span class="title">', title, '</span>'
                ].join('');
            }
        },

        /**
         * returns id for given type
         */
        getId = function(type) {
            return '#' + this.options.ids[type];
        },

        /**
         * Starts the grid-group loader
         */
        startOverlayLoader = function() {
            var $element = this.$find(getId.call(this, 'loader'));
            if (!!$element.length) {
                this.sandbox.start([
                    {
                        name: 'loader@husky',
                        options: {
                            el: $element,
                            size: '100px',
                            color: '#cccccc'
                        }
                    }
                ]);
            }
        },

        /**
         * Stops the grid-group loader
         */
        stopOverlayLoader = function() {
            this.sandbox.stop(getId.call(this, 'loader'));
        },

        /**
         * Scrolls the whole form the the bottom
         */
        scrollToBottom = function() {
            this.sandbox.dom.scrollAnimate(this.sandbox.dom.height('.media-selection-overlay-datagrid-container'), '.media-selection-overlay-content');
        },

        /**
         * Takes an array of files and adds them to the datagrid
         * @param files {Array} array of files
         */
        addFilesToDatagrid = function(files) {
            for (var i = -1, length = files.length; ++i < length;) {
                files[i].selected = true;
            }
            this.sandbox.emit('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.records.add', files, scrollToBottom.bind(this));
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

                this.sandbox.emit('husky.toolbar.media-selection-ovelay.' + this.options.instanceName + '.item.show', 'add');
                this.sandbox.emit('husky.dropzone.media-selection-ovelay.' + this.options.instanceName + '.enable');
            } else {
                this.sandbox.emit('husky.toolbar.media-selection-ovelay.' + this.options.instanceName + '.item.hide', 'add');
                this.sandbox.emit('husky.dropzone.media-selection-ovelay.' + this.options.instanceName + '.disable');
            }

            this.sandbox.emit('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.url.update', { 
                collection: collectionId,
                page: 1
            });
            changeUploadCollection.call(this, collectionId);
            this.$el.find('.media-selection-overlay-content-title').html(collectionTitle);
        },

        updateSelectedCounter = function(data) {
            var tpl = '',
                selectedCount = (data.ids || []).length;

            if (selectedCount) {
                tpl = templates.mediaSelectedInfo({
                    selectedCounter: selectedCount,
                    selectedImagesLabel: this.sandbox.translate('media-selection.overlay.selected-images-label')
                });
            }

            this.$el.find('#selected-images-count').html(tpl);
        },

        /**
         * custom event handling
         */
        bindCustomEvents = function() {
            this.sandbox.on(this.DISPLAY_OPTION_CHANGED(), function(position) {
                setData.call(this, {displayOption: position}, false);
            }, this);

            this.sandbox.on(this.DATA_RETRIEVED(), function(data) {
                var ids = [];
                this.sandbox.util.foreach(data, function(el) {
                    ids.push(el.id);
                }.bind(this));

                setData.call(this, {ids: ids}, false);
            }, this);

            this.sandbox.on('husky.tabs.overlay.' + this.options.instanceName + '.add.initialized', function() {
                startOverlayLoader.call(this);
                this.collections.fetchSorted('title', {
                    success: function(collections) {
                        this.collectionArray = collections.toJSON();
                        stopOverlayLoader.call(this);
                        startGridGroup.call(this);
                        startDropzone.call(this);
                    }.bind(this)
                });
            }.bind(this));

            this.sandbox.on('husky.overlay.' + this.options.instanceName + '.add.opened', function() {
                if (this.gridGroupDeprecated === true) {
                    reloadGridGroup.call(this);
                    this.gridGroupDeprecated = false;
                }
            }.bind(this));

            // set position of overlay if height of grid-group changes
            this.sandbox.on('sulu.grid-group.' + this.options.instanceName + '.height-changed', function() {
                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.add' + '.set-position');
            }.bind(this));

            // set position of overlay if grid-group has initialized
            this.sandbox.on('sulu.grid-group.' + this.options.instanceName + '.initialized', function() {
                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.add' + '.set-position');
            }.bind(this));

            // save the collection where new media should be uploaded
            this.sandbox.on('husky.select.' + this.options.instanceName + '.selected.item', changeUploadCollection.bind(this));

            this.sandbox.on('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.item.select', function(id) {
                this.sandbox.emit(RECORD_SELECTED.call(this), id);
            }.bind(this));

            this.sandbox.on('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.item.deselect', function(id) {
                this.sandbox.emit(RECORD_DESELECTED.call(this), id);
            }.bind(this));

            this.sandbox.on('husky.data-navigation.' + this.options.instanceName + '.select', dataNavigationSelectHandler.bind(this));

            // change datagrid to table
            this.sandbox.on('sulu.list-toolbar.media-selection-ovelay.' + this.options.instanceName + '.change.table', function() {
                this.sandbox.emit('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.view.change', 'table');
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'table');
            }.bind(this));

            // change datagrid to thumbnail small
            this.sandbox.on('sulu.list-toolbar.media-selection-ovelay.' + this.options.instanceName + '.change.thumbnail-small', function() {
                this.sandbox.emit('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.view.change', 'thumbnail', {large: false});
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailSmall');
            }.bind(this));

            // change datagrid to thumbnail large
            this.sandbox.on('sulu.list-toolbar.media-selection-ovelay.' + this.options.instanceName + '.change.thumbnail-large', function() {
                this.sandbox.emit('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.view.change', 'thumbnail', {large: true});
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'thumbnailLarge');
            }.bind(this));

            // premark the current view in the toolbar
            this.sandbox.on('husky.toolbar.media-selection-ovelay.' + this.options.instanceName + '.initialized', function() {
                this.sandbox.emit('husky.toolbar.media-selection-ovelay.' + this.options.instanceName + '.item.mark', listViews[this.listView].itemId);
            }.bind(this));

            // load collections list if back icon is clicked
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.media.collections.list');
            }.bind(this));

            // if files got uploaded to the server add them to the datagrid
            this.sandbox.on('husky.dropzone.media-selection-ovelay.' + this.options.instanceName + '.files-added', function(files) {
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                addFilesToDatagrid.call(this, files);
            }.bind(this));

            // open data-source folder-overlay
            this.sandbox.on('sulu.list-toolbar.media-selection-ovelay.' + this.options.instanceName + '.add', function() {
                this.sandbox.emit('husky.dropzone.media-selection-ovelay.' + this.options.instanceName + '.open-data-source');
            }.bind(this));

            // add image to the selected images grid
            this.sandbox.on('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.item.select', function(itemId, item) {
                var data = this.getData(),
                    index = data.ids.indexOf(itemId);

                if (index > -1) {
                    return;
                }

                data.ids.push(itemId);
                this.setData(data, false);
                this.addItem(item);
                updateSelectedCounter.call(this, data);
            }.bind(this));

            // remove image to the selected images grid
            this.sandbox.on('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.item.deselect', function(itemId) {
                var data = this.getData(),
                    index = data.ids.indexOf(itemId);

                if (index > -1) {
                    data.ids.splice(index, 1);
                }

                this.setData(data, false);
                this.removeItemById(itemId);
                updateSelectedCounter.call(this, data);
            }.bind(this));

            this.sandbox.on('husky.overlay.dropzone-media-selection-ovelay.' + this.options.instanceName + '.opened', function() {
                this.$el.find('.media-selection-overlay-container').addClass('dropzone-overlay-opened');
            }.bind(this));

            this.sandbox.on('husky.overlay.dropzone-media-selection-ovelay.' + this.options.instanceName + '.closed', function() {
                this.$el.find('.media-selection-overlay-container').removeClass('dropzone-overlay-opened');
            }.bind(this));

            this.sandbox.on('husky.overlay.' + this.options.instanceName + '.add.opened', function() {
                var data = this.getData(),
                    selectedItems = data.ids || [];
                this.sandbox.emit('husky.datagrid.media-selection-ovelay.' + this.options.instanceName + '.selected.update', selectedItems);
                updateSelectedCounter.call(this, data);
            }.bind(this));
        },

        /**
         * Changes the dropzone url and inserts a given collection id
         * @param collectionId
         */
        changeUploadCollection = function(collectionId) {
            this.uploadCollection = collectionId;
            this.sandbox.emit(
                    'husky.dropzone.media-selection-ovelay.' + this.options.instanceName + '.change-url',
                    '/admin/api/media?collection=' + collectionId);
        },

        /**
         * Refreshes the data in the grid-group
         */
        reloadGridGroup = function() {
            var data = this.getData();
            this.sandbox.emit('sulu.grid-group.' + this.options.instanceName + '.reload', {
                data: this.collectionArray,
                preselected: data.ids
            });
        },

        /**
         * Starts the grid group
         */
        startGridGroup = function() {
            var gridUrl, urlParameter = {}, data = this.getData();

            if (this.options.types != '') {
                gridUrl = 'filterByTypes';
                urlParameter = {types: this.options.types};
            } else {
                gridUrl = 'all';
            }

            this.sandbox.start([
                {
                    name: 'grid-group@suluadmin',
                    options: {
                        data: this.collectionArray,
                        el: this.sandbox.dom.find(getId.call(this, 'gridGroup')),
                        instanceName: this.options.instanceName,
                        gridUrl: gridUrl,
                        urlParameter: urlParameter,
                        preselected: data.ids,
                        resultKey: this.options.resultKey,
                        dataGridOptions: {
                            view: 'table',
                            viewOptions: {
                                table: {
                                    excludeFields: ['id'],
                                    showHead: false,
                                    cssClass: 'minimal'
                                }
                            },
                            pagination: false,
                            matchings: [
                                {
                                    name: 'id'
                                },
                                {
                                    name: 'thumbnails',
                                    translation: 'thumbnails',
                                    type: 'thumbnails'
                                },
                                {
                                    name: 'title',
                                    translation: 'title'
                                }
                            ]
                        }
                    }
                }
            ]);
        },

        /**
         * Starts the dropzone for uploading a new media
         */
        startDropzone = function() {
            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: getId.call(this, 'dropzone'),
                        url: '/admin/api/media?collection=' + this.uploadCollection,
                        method: 'POST',
                        paramName: 'fileVersion',
                        showOverlay: false,
                        instanceName: 'media-selection-ovelay.' + this.options.instanceName,
                        afterDropCallback: uploadNewFile.bind(this),
                        keepFilesAfterSuccess: true
                    }
                }
            ]);
        },

        /**
         * Handles the upload a new media. Just uploads it or creates a new collection first
         * @returns {Object} returns a promise
         */
        uploadNewFile = function() {
            var def = this.sandbox.data.deferred();

            if (this.uploadCollection === 'new') {
                // only create one "new" collection. If a "new" collection exists take it
                if (!this.newCollectionId) {
                    this.newCollection.set({
                        title: getNewCollectionTitle.call(this)
                    });
                    this.newCollection.save(null, {
                        success: function(collection) {
                            collection = collection.toJSON();
                            this.newCollectionId = collection.id;
                            changeUploadCollection.call(this, collection.id);
                            this.collectionArray.push(collection);
                            def.resolve();
                        }.bind(this),
                        error: function() {
                            this.sandbox.logger.log('Error while saving collection');
                        }.bind(this)
                    });
                } else {
                    this.uploadCollection = this.newCollectionId;
                    changeUploadCollection.call(this, this.uploadCollection);
                    def.resolve();
                }
            } else {
                def.resolve();
            }
            return def.promise();
        },

        /**
         * Generates the new colleciton title. Looks how many collecitons with
         * the same name already exists and adds a ([how many are existing]) to the collection
         * @returns {string} the generated collection title
         */
        getNewCollectionTitle = function() {
            var translation = this.sandbox.translate(this.options.translations.newCollection),
                counter = 0;
            this.sandbox.util.foreach(this.collectionArray, function(collection) {
                if (collection.title.indexOf(translation) !== -1) {
                    counter++;
                }
            }.bind(this));

            if (counter > 0) {
                translation = translation + ' (' + counter + ')';
            }
            return translation;
        },

        /**
         * Displays a success label and adds the newly uploaded file
         * @param media {Object} the media object
         */
        addUploadedFile = function(media) {
            if (!!media.length) {
                var data = this.getData();
                this.sandbox.util.foreach(media, function(singleMedia) {
                    data.ids.push(singleMedia.id);
                }.bind(this));
                this.setData(data);
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                reloadGridGroup.call(this);
            }
        },

        /**
         * starts the overlay component
         */
        startAddOverlay = function() {
            var $element = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $element);
            this.listView = this.sandbox.sulu.getUserSetting(constants.listViewStorageKey) || 'thumbnailSmall';

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$addButton,
                        draggable: true,
                        dragTrigger: '.media-selection-overlay-navigation-container',
                        removeOnClose: false,
                        el: $element,
                        container: this.$el,
                        cssClass: 'media-selection-overlay-container',
                        instanceName: this.options.instanceName + '.add',
                        skin: 'wide',
                        supportKeyInput: false,
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.addImages),
                                data: templates.mediaSelection({
                                    contentDefaultTitle: this.sandbox.translate('media-selection.overlay.all-images')
                                })
                            }
                        ]
                    }
                }
            ]);

            this.sandbox.once('husky.overlay.' + this.options.instanceName + '.add.opened', function() {
                
                this.$el.on('click', '.media-selection-overlay-close', function() {
                    this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.add.close');
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
                        instanceName: 'media-selection-ovelay.' + this.options.instanceName,
                        showTitleAsTooltip: false,
                        template: [
                            {
                                id: 'add',
                                icon: 'plus-circle',
                                title: this.sandbox.translate('media-selection.list-toolbar.upload-info'),
                                hideTitle: false,
                                hidden: true,
                                callback: function() {
                                    this.sandbox.emit('husky.dropzone.media-selection-ovelay.' + this.options.instanceName + '.open-data-source');
                                }.bind(this)
                            },
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
                                            this.sandbox.emit('sulu.list-toolbar.media-selection-ovelay.' + this.options.instanceName + '.change.thumbnail-small');
                                        }.bind(this)
                                    },
                                    {
                                        id: 'big-thumbnails',
                                        title: this.sandbox.translate('sulu.list-toolbar.big-thumbnails'),
                                        callback: function() {
                                            this.sandbox.emit('sulu.list-toolbar.media-selection-ovelay.' + this.options.instanceName + '.change.thumbnail-large');
                                        }.bind(this)
                                    },
                                    {
                                        id: 'table',
                                        title: this.sandbox.translate('sulu.list-toolbar.table'),
                                        callback: function() {
                                            this.sandbox.emit('sulu.list-toolbar.media-selection-ovelay.' + this.options.instanceName + '.change.table');
                                        }.bind(this)
                                    }
                                ]
                            }
                        ],
                        inHeader: false
                    },
                    {
                        el: this.$el.find('.media-selection-overlay-datagrid-container'),
                        url: '/admin/api/media?orderBy=media.changed&orderSort=DESC',
                        view: listViews[this.listView].name,
                        resultKey: 'media',
                        instanceName: 'media-selection-ovelay.' + this.options.instanceName,
                        preselected: this.getData().ids,
                        sortable: false,
                        viewSpacingBottom: 180,
                        viewOptions: {
                            table: {
                                fullWidth: false,
                                rowClickSelect: true
                            },
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
                            instanceName: 'media-selection-ovelay.' + this.options.instanceName,
                            dropzoneEnabled: false,
                            cancelUploadOnOverlayClick: true
                        }
                    }
                ]);

            }.bind(this));
        },

        /**
         * extract data from overlay
         */
        getAddOverlayData = function() {
            var idsDef = this.sandbox.data.deferred();

            this.sandbox.emit('sulu.grid-group.' + this.options.instanceName + '.get-selected-ids', function(ids) {
                setData.call(this, {ids: ids});
                idsDef.resolve();
            }.bind(this));

            idsDef.then(function() {
                this.sandbox.emit(INPUT_RETRIEVED.call(this));
            }.bind(this));
        },

        setData = function(data, reinitialize) {
            var oldData = this.getData();

            for (var propertyName in data) {
                if (data.hasOwnProperty(propertyName)) {
                    oldData[propertyName] = data[propertyName];
                }
            }

            this.setData(oldData, reinitialize);
        };

    return {
        type: 'itembox',

        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            var data = this.getData();

            // init collection
            this.collections = new Collections();
            this.newCollection = new Collection();
            this.collectionArray = null;
            this.newCollectionId = null;
            this.gridGroupDeprecated = false;

            this.options.ids = {
                container: 'media-selection-' + this.options.instanceName + '-container',
                addButton: 'media-selection-' + this.options.instanceName + '-add',
                configButton: 'media-selection-' + this.options.instanceName + '-config',
                displayOption: 'media-selection-' + this.options.instanceName + '-display-option',
                content: 'media-selection-' + this.options.instanceName + '-content',
                chooseTab: 'media-selection-' + this.options.instanceName + '-choose-tab',
                uploadTab: 'media-selection-' + this.options.instanceName + '-upload-tab',
                gridGroup: 'media-selection-' + this.options.instanceName + '-grid-group',
                loader: 'media-selection-' + this.options.instanceName + '-loader',
                collectionSelect: 'media-selection-' + this.options.instanceName + '-collection-select',
                dropzone: 'media-selection-' + this.options.instanceName + '-dropzone'
            };

            // init vars
            this.uploadCollection = null;

            bindCustomEvents.call(this);

            this.render();

            // set display option
            if (!!data.displayOption) {
                this.setDisplayOption(data.displayOption);
            }

            // init overlays
            startAddOverlay.call(this);
        },

        isDataEmpty: function(data) {
            return this.sandbox.util.isEmpty(data.ids);
        },

        getUrl: function(data) {
            var delimiter = (this.options.url.indexOf('?') === -1) ? '?' : '&';

            return [
                this.options.url,
                delimiter,
                this.options.idsParameter, '=', (data.ids || []).join(',')
            ].join('');
        },

        getItemContent: function(item) {
            return templates.contentItem(item.title, item.thumbnails);
        },

        sortHandler: function(ids) {
            var data = this.getData();
            data.ids = ids;

            this.setData(data, false);
        },

        removeHandler: function(id) {
            var data = this.getData();

            for (var i = -1, length = data.ids.length; ++i < length;) {
                if (data.ids[i] === id) {
                    data.ids.splice(data.ids.indexOf(id), 1);
                    break;
                }
            }
            this.sandbox.emit(RECORD_DESELECTED.call(this), id);

            this.setData(data, false);
        }
    };
});
