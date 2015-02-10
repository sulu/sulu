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

        constants = {
            lastVisitedCollectionKey: 'last-visited-collection'
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
            return this.options.eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        templates = {
            addTab: function(options, header) {
                return [
                    '<div id="', options.ids.chooseTab, '">',
                    '   <div class="heading">',
                    '       <h3>', header, '</h3>',
                    '   </div>',
                    '   <div id="', options.ids.gridGroup, '"/>',
                    '   <div class="overlay-loader" id="', options.ids.loader , '"></div>',
                    '</div>'
                ].join('');
            },

            uploadTab: function(options, collection) {
                return [
                    '<div id="', options.ids.uploadTab , '">',
                    '   <div class="grid-row">',
                    '       <label>', collection , '</label>',
                    '       <div id="', options.ids.collectionSelect , '"></div>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div id="', options.ids.dropzone , '"></div>',
                    '   </div>',
                    '</div>'
                ].join('')
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
         * custom event handling
         */
        bindCustomEvents = function() {
            this.sandbox.on(this.DISPLAY_OPTION_CHANGED(), function(position) {
                setData.call(this, {displayOption: position}, false);
                this.sandbox.emit('sulu.content.changed');
            }, this);

            this.sandbox.on(this.DATA_CHANGED(), function() {
                this.sandbox.emit('sulu.content.changed');
            }, this);

            this.sandbox.on('husky.tabs.overlaymedia-selection.' + this.options.instanceName + '.add.initialized', function() {
                startOverlayLoader.call(this);
                this.collections.fetchSorted('title', {
                    success: function(collections) {
                        this.collectionArray = collections.toJSON();
                        stopOverlayLoader.call(this);
                        startGridGroup.call(this);
                        startSelect.call(this);
                        startDropzone.call(this);
                    }.bind(this)
                });
            }.bind(this));

            this.sandbox.on('husky.overlay.media-selection.' + this.options.instanceName + '.add.opened', function() {
                if (this.gridGroupDeprecated === true) {
                    reloadGridGroup.call(this);
                    this.gridGroupDeprecated = false;
                }
            }.bind(this));

            // set position of overlay if height of grid-group changes
            this.sandbox.on('sulu.grid-group.' + this.options.instanceName + '.height-changed', function() {
                this.sandbox.emit('husky.overlay.media-selection.' + this.options.instanceName + '.add' + '.set-position');
            }.bind(this));

            // set position of overlay if grid-group has initialized
            this.sandbox.on('sulu.grid-group.' + this.options.instanceName + '.initialized', function() {
                this.sandbox.emit('husky.overlay.media-selection.' + this.options.instanceName + '.add' + '.set-position');
            }.bind(this));

            // save the collection where new media should be uploaded
            this.sandbox.on('husky.select.media-selection-' + this.options.instanceName + '.selected.item', changeUploadCollection.bind(this));

            // add uploaded files
            this.sandbox.on('husky.dropzone.media-selection-' + this.options.instanceName + '.files-added', addUploadedFile.bind(this));

            this.sandbox.on('sulu.grid-group.' + this.options.instanceName + '.record-selected', function(id) {
                this.sandbox.emit(RECORD_SELECTED.call(this), id);
            }.bind(this));

            this.sandbox.on('sulu.grid-group.' + this.options.instanceName + '.record-deselected', function(id) {
                this.sandbox.emit(RECORD_DESELECTED.call(this), id);
            }.bind(this));
        },

        /**
         * Changes the dropzone url and inserts a given collection id
         * @param collectionId
         */
        changeUploadCollection = function(collectionId) {
            this.uploadCollection = collectionId;
            this.sandbox.emit(
                    'husky.dropzone.media-selection-' + this.options.instanceName + '.change-url',
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
         * Starts the select for choosing the collection to upload
         */
        startSelect = function() {
            var data = this.sandbox.util.extend([], true, this.collectionArray),
                preselected = this.sandbox.sulu.getUserSetting(constants.lastVisitedCollectionKey) || 'new';
            data.unshift({
                id: 'new',
                title: this.sandbox.translate(this.options.translations.createNewCollection)
            });
            changeUploadCollection.call(this, preselected);
            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: getId.call(this, 'collectionSelect'),
                        instanceName: 'media-selection-' + this.options.instanceName,
                        valueName: 'title',
                        data: data,
                        preSelectedElements: [preselected]
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
                        instanceName: 'media-selection-' + this.options.instanceName,
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
                reloadGridGroup.call(this)
            }
        },

        /**
         * starts the overlay component
         */
        startAddOverlay = function() {
            var chooseTabData = templates.addTab(this.options, this.sandbox.translate(this.options.translations.collections)),
                uploadTabData = templates.uploadTab(this.options, this.sandbox.translate(this.options.translations.collection));

            var $element = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$addButton,
                        cssClass: 'media-selection-overlay',
                        el: $element,
                        removeOnClose: false,
                        container: this.$el,
                        draggable: false,
                        instanceName: 'media-selection.' + this.options.instanceName + '.add',
                        skin: 'medium',
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.addImages),
                                okCallback: getAddOverlayData.bind(this),
                                cssClass: 'media-selection-overlay-add',
                                tabs: [
                                    {
                                        title: this.sandbox.translate(this.options.translations.choose),
                                        data: chooseTabData
                                    },
                                    {
                                        title: this.sandbox.translate(this.options.translations.upload),
                                        data: uploadTabData
                                    }
                                ]
                            }
                        ]
                    }
                }
            ]);
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

            this.sandbox.emit('sulu.content.changed');
        },

        removeHandler: function(id) {
            var data = this.getData();

            for (var i = -1, length = data.ids.length; ++i < length;) {
                if (data.ids[i] === id) {
                    data.ids.splice(data.ids.indexOf(id), 1);
                    break;
                }
            }

            this.setData(data, false);

            this.sandbox.emit('sulu.content.changed');
        }
    };
});
