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
define(['sulumedia/collection/collections', 'sulumedia/model/collection'], function (Collections, Collection) {

    'use strict';

    var defaults = {
            visibleItems: 6,
            instanceName: null,
            url: '',
            idsParameter: 'ids',
            preselected: {ids: [], displayOption: 'top', config: {}},
            idKey: 'id',
            titleKey: 'title',
            thumbnailKey: 'thumbnails',
            thumbnailSize: '50x50',
            resultKey: 'media',
            positionSelectedClass: 'selected',
            hidePositionElement: false,

            translations: {
                noMediaSelected: 'media-selection.nomedia-selected',
                addImages: 'media-selection.add-images',
                choose: 'public.choose',
                collections: 'media-selection.collections',
                visible: 'public.visible',
                of: 'public.of',
                upload: 'media-selection.upload-new',
                collection: 'media-selection.upload-to-collection',
                createNewCollection: 'media-selection.create-new-collection',
                newCollection: 'media-selection.new-collection',
                viewall: 'public.view-all',
                viewless: 'public.view-less'
            }
        },

        dataDefaults = {
            ids: [],
            displayOption: 'top',
            config: {}
        },

        constants = {
            lastVisitedCollectionKey: 'last-visited-collection'
        },

        /**
         * namespace for events
         * @type {string}
         */
            eventNamespace = 'sulu.media-selection.',

        /**
         * raised when all overlay components returned their value
         * @event sulu.media-selection.input-retrieved
         */
            INPUT_RETRIEVED = function () {
            return createEventName.call(this, 'input-retrieved');
        },

        /**
         * raised when the overlay data has been changed
         * @event sulu.media-selection.data-changed
         */
            DATA_CHANGED = function () {
            return createEventName.call(this, 'data-changed');
        },

        /**
         * raised when selected element has been removed
         * @event sulu.media-selection.selection-removed
         */
        SELECTION_REMOVED = function() {
            return createEventName.call(this, 'selection-removed');
        },

        /**
         * raised before data is requested with AJAX
         * @event sulu.media-selection.data-request
         */
            DATA_REQUEST = function () {
            return createEventName.call(this, 'data-request');
        },

        /**
         * raised when data has returned from the ajax request
         * @event sulu.media-selection.data-retrieved
         */
            DATA_RETRIEVED = function () {
            return createEventName.call(this, 'data-retrieved');
        },

        /**
         * raised when data has returned from the ajax request
         * @event sulu.media-selection.record-selected
         * @param id {Number|String} id of the record
         */
        RECORD_SELECTED = function(){
            return createEventName.call(this, 'record-selected');
        },

        /**
         * raised when data has returned from the ajax request
         * @event sulu.media-selection.record-deselected
         * @param id {Number|String} id of the record
         */
        RECORD_DESELECTED = function(){
            return createEventName.call(this, 'record-deselected');
        },

        /**
         * returns normalized event names
         */
            createEventName = function (postFix) {
            return eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        templates = {
            skeleton: function(options, positionClass) {
                return [
                    '<div class="white-box form-element" id="', options.ids.container, '">',
                    '   <div class="header">',
                    '       <span class="fa-plus-circle icon left action" id="', options.ids.addButton, '"></span>',
                    '       <div class="position ',positionClass,'">',
                    '<div class="husky-position" id="', options.ids.displayOption ,'">',
                    '    <div class="top left" data-position="leftTop"></div>',
                    '    <div class="top middle" data-position="top"></div>',
                    '    <div class="top right" data-position="rightTop"></div>',
                    '    <div class="middle left" data-position="left"></div>',
                    '    <div class="middle middle inactive"></div>',
                    '    <div class="middle right" data-position="right"></div>',
                    '    <div class="bottom left" data-position="leftBottom"></div>',
                    '    <div class="bottom middle" data-position="bottom"></div>',
                    '    <div class="bottom right" data-position="rightBottom"></div>',
                    '</div>',
                    '       </div>',
                    '       <span class="fa-cog icon right border" id="', options.ids.configButton, '" style="display:none"></span>',
                    '   </div>',
                    '   <div class="content" id="', options.ids.content, '"></div>',
                    '</div>'
                ].join('');
            },

            noContent: function (noContentString) {
                return [
                    '<div class="no-content">',
                    '   <span class="fa-coffee icon"></span>',
                    '   <div class="text">', noContentString, '</div>',
                    '</div>'
                ].join('');
            },

            addTab: function (options, header) {
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

            uploadTab: function (options, collection) {
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

            contentItem: function (id, num, value, imageUrl) {
                return [
                    '<li data-id="', id, '">',
                    '   <span class="num">', num, '</span>',
                    '   <img src="', imageUrl, '"/>',
                    '   <span class="value">', value, '</span>',
                    '   <span class="fa-times remove"></span>',
                    '</li>'
                ].join('');
            }
        },

        /**
         * returns id for given type
         */
        getId = function (type) {
            return '#' + this.options.ids[type];
        },

        /**
         * render component
         */
        render = function () {
            // init collection
            this.collections = new Collections();
            this.newCollection = new Collection();
            this.collectionArray = null;
            this.newCollectionId = null;
            this.gridGroupDeprecated = false;
            this.viewAll = false;

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

            if(!!this.options.hidePositionElement){
                this.sandbox.dom.html(this.$el, templates.skeleton(this.options, 'hidden'));
            } else {
                this.sandbox.dom.html(this.$el, templates.skeleton(this.options, ''));
            }

            // init container
            this.$container = this.sandbox.dom.find(getId.call(this, 'container'), this.$el);
            this.$content = this.sandbox.dom.find(getId.call(this, 'content'), this.$el);
            this.$addButton = this.sandbox.dom.find(getId.call(this, 'addButton'), this.$el);
            this.$configButton = this.sandbox.dom.find(getId.call(this, 'configButton'), this.$el);
            // TODO: footer this.$footer

            // set preselected values
            if (!!this.sandbox.dom.data(this.$el, 'media-selection')) {
                var data = this.sandbox.util.extend(true, {}, dataDefaults, this.sandbox.dom.data(this.$el, 'media-selection'));
                setData.call(this, data);
            } else {
                setData.call(this, this.options.preselected);
            }

            // render no images selected
            renderStartContent.call(this);

            // sandbox event handling
            bindCustomEvents.call(this);

            // init vars
            this.itemsVisible = this.options.visibleItems;
            this.uploadCollection = null;
            this.URI = {
                str: '',
                hasChanged: false
            };

            // generate URI for data
            setURI.call(this);

            // set display-option value
            setDisplayOption.call(this);

            // init overlays
            startAddOverlay.call(this);

            // load preselected items
            loadContent.call(this);

            // handle dom events
            bindDomEvents.call(this);
        },

        /**
         * Renders the content at the beginning
         * (with no items and before any request)
         */
        renderStartContent = function () {
            var noMedia = this.sandbox.translate(this.options.translations.noMediaSelected);
            this.sandbox.dom.html(this.$content, templates.noContent(noMedia));
        },

        /**
         * Starts the grid-group loader
         */
        startOverlayLoader = function () {
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
        stopOverlayLoader = function () {
            this.sandbox.stop(getId.call(this, 'loader'));
        },

        /**
         * custom event handling
         */
        bindCustomEvents = function () {
            this.sandbox.on('husky.tabs.overlaymedia-selection.' + this.options.instanceName + '.add.initialized', function () {
                startOverlayLoader.call(this);
                this.collections.fetch({
                    success: function (collections) {
                        this.collectionArray = collections.toJSON();
                        stopOverlayLoader.call(this);
                        startGridGroup.call(this);
                        startSelect.call(this);
                        startDropzone.call(this);
                    }.bind(this)
                });
            }.bind(this));

            this.sandbox.on('husky.overlay.media-selection.'+ this.options.instanceName +'.add.opened', function () {
                if (this.gridGroupDeprecated === true) {
                    reloadGridGroup.call(this);
                    this.gridGroupDeprecated = false;
                }
            }.bind(this));

            // data from overlay retrieved
            this.sandbox.on(INPUT_RETRIEVED.call(this), function () {
                setURI.call(this);
                loadContent.call(this);
            }.bind(this));

            // data from ajax request retrieved
            this.sandbox.on(DATA_RETRIEVED.call(this), function () {
                renderContent.call(this);
            }.bind(this));

            // set position of overlay if height of grid-group changes
            this.sandbox.on('sulu.grid-group.' + this.options.instanceName + '.height-changed', function () {
                this.sandbox.emit('husky.overlay.media-selection.' + this.options.instanceName + '.add' + '.set-position');
            }.bind(this));

            // set position of overlay if grid-group has initialized
            this.sandbox.on('sulu.grid-group.' + this.options.instanceName + '.initialized', function () {
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
        changeUploadCollection = function (collectionId) {
            this.uploadCollection = collectionId;
            this.sandbox.emit(
                'husky.dropzone.media-selection-'+ this.options.instanceName +'.change-url',
                '/admin/api/media?collection=' + collectionId);
        },

        /**
         * Refreshes the data in the grid-group
         */
        reloadGridGroup = function() {
            this.sandbox.emit('sulu.grid-group.'+ this.options.instanceName +'.reload', {
                data: this.collectionArray,
                preselected: this.data.ids
            });
        },

        /**
         * Starts the grid group
         */
        startGridGroup = function () {
            this.sandbox.start([
                {
                    name: 'grid-group@suluadmin',
                    options: {
                        data: this.collectionArray,
                        el: this.sandbox.dom.find(getId.call(this, 'gridGroup')),
                        instanceName: this.options.instanceName,
                        gridUrl: '/admin/api/media?' + (this.options.types != '' ? 'types=' + this.options.types + '&' : '') + 'collection=',
                        preselected: this.data.ids,
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
        startSelect = function () {
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
        startDropzone = function () {
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
        uploadNewFile = function () {
            var def = this.sandbox.data.deferred();

            if (this.uploadCollection === 'new') {
                // only create one "new" collection. If a "new" collection exists take it
                if (!this.newCollectionId) {
                    this.newCollection.set({
                        title: getNewCollectionTitle.call(this)
                    });
                    this.newCollection.save(null, {
                        success: function (collection) {
                            collection = collection.toJSON();
                            this.newCollectionId = collection.id;
                            changeUploadCollection.call(this, collection.id);
                            this.collectionArray.push(collection);
                            def.resolve();
                        }.bind(this),
                        error: function () {
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
                this.sandbox.util.foreach(media, function(singleMedia) {
                    this.data.ids.push(singleMedia.id);
                }.bind(this));
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                reloadGridGroup.call(this)
            }
        },

        /**
         * handle dom events
         */
        bindDomEvents = function () {
            // chgange display options on click on a positon square
            this.sandbox.dom.on(getId.call(this, 'displayOption') + ' > div', 'click', changeDisplayOptions.bind(this));

            // click on remove icons
            this.sandbox.dom.on(getId.call(this, 'content'), 'click', removeHandler.bind(this), 'li .remove');

            // view all
            this.sandbox.dom.on(this.$el, 'click', function() {
                this.viewAll = true;
                renderContent.call(this)
            }.bind(this), '.view-all');

            // view less
            this.sandbox.dom.on(this.$el, 'click', function() {
                this.viewAll = false;
                renderContent.call(this)
            }.bind(this), '.view-less');
        },

        /**
         * Handles the click event on the remove icon
         * @param event
         */
        removeHandler = function (event) {
            var $item = this.sandbox.dom.parents(event.currentTarget, 'li'),
                dataId = this.sandbox.dom.data($item, 'id');
            this.sandbox.dom.remove($item);
            removeItemWithId.call(this, dataId);
            this.data.ids.splice(this.data.ids.indexOf(dataId), 1);
            this.itemsVisible--;
            detachFooter.call(this);
            if (this.items.length === 0) {
                renderStartContent.call(this);
            } else {
                renderFooter.call(this);
            }
            this.gridGroupDeprecated = true;
            this.sandbox.emit(DATA_CHANGED.call(this), this.data, this.$el);
            this.sandbox.emit(RECORD_DESELECTED.call(this), dataId);
        },

        /**
         * Removes an item with a given id
         * @param id {Number|String} the id of the item to delete
         * @returns {boolean} returns true if deleted successfully
         */
         removeItemWithId = function (id) {
            for (var i = -1, length = this.items.length; ++i < length;) {
                if (this.items[i].id === id) {
                    this.items.splice(i, 1);
                    return true;
                }
            }
            return false;
        },

        /**
         * renders the content decides whether the footer is rendered or not
         */
        renderContent = function () {
            if (this.viewAll === true) {
                this.itemsVisible = this.items.length;
            } else {
                this.itemsVisible = (this.items.length < this.options.visibleItems) ? this.items.length : this.options.visibleItems;
            }

            if (this.items.length !== 0) {
                var ul = this.sandbox.dom.createElement('<ul class="items-list"/>'),
                    i = -1,
                    length = this.items.length,
                    url;

                //loop stops if no more items are left or if number of rendered items matches itemsVisible
                for (; ++i < length && i < this.itemsVisible;) {
                    url = this.items[i][this.options.thumbnailKey][this.options.thumbnailSize];
                    this.sandbox.dom.append(ul, templates.contentItem(this.items[i][this.options.idKey], i + 1, this.items[i][this.options.titleKey], url));
                }

                this.sandbox.dom.html(this.$content, ul);
                renderFooter.call(this);
            } else {
                renderStartContent.call(this);
                detachFooter.call(this);
            }
        },

        /**
         * renders the footer and calls a method to bind the events for itself
         */
        renderFooter = function () {
            if (this.$footer === null || this.$footer === undefined) {
                this.$footer = this.sandbox.dom.createElement('<div class="footer"/>');
            }

            this.sandbox.dom.html(this.$footer, [
                '<span>',
                '<strong>' + this.itemsVisible + ' </strong>', this.sandbox.translate(this.options.translations.of) , ' ',
                '<strong>' + this.items.length + ' </strong>', this.sandbox.translate(this.options.translations.visible),
                '</span>'
            ].join(''));

            if (this.itemsVisible < this.items.length) {
                this.sandbox.dom.append(
                    this.sandbox.dom.find('span', this.$footer),
                    '<strong class="view-all pointer"> ('+ this.sandbox.translate(this.options.translations.viewall) +')</strong>'
                );
            } else if (this.itemsVisible > this.options.visibleItems) {
                this.sandbox.dom.append(
                    this.sandbox.dom.find('span', this.$footer),
                    '<strong class="view-less pointer"> ('+ this.sandbox.translate(this.options.translations.viewless) +')</strong>'
                );
            }

            this.sandbox.dom.append(this.$container, this.$footer);
        },

        /**
         * starts the overlay component
         */
        startAddOverlay = function () {
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
        getAddOverlayData = function () {
            var idsDef = this.sandbox.data.deferred();

            this.sandbox.emit('sulu.grid-group.' + this.options.instanceName + '.get-selected-ids', function (ids) {
                setData.call(this, {ids: ids});
                idsDef.resolve();
            }.bind(this));

            idsDef.then(function () {
                this.sandbox.emit(INPUT_RETRIEVED.call(this));
            }.bind(this));
        },

        /**
         * starts the loader component
         */
         startLoader = function () {
            detachFooter.call(this);

            var $loaderContainer = this.sandbox.dom.createElement('<div class="loader"/>');
            this.sandbox.dom.html(this.$content, $loaderContainer);

            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: $loaderContainer,
                        size: '100px',
                        color: '#e4e4e4'
                    }
                }
            ]);
        },

        /**
         * removes the footer
         */
        detachFooter = function () {
            if (this.$footer !== null) {
                this.sandbox.dom.remove(this.$footer);
            }
        },

        /**
         * load content from generated uri
         */
        loadContent = function () {
            //only request if URI has changed
            if (this.URI.hasChanged === true) {
                this.sandbox.emit(DATA_REQUEST.call(this));
                startLoader.call(this);

                // reset item visible
                this.itemsVisible = this.options.visibleItems;

                if (this.data.ids.length > 0) {
                    this.sandbox.util.load(this.URI.str)
                        .then(function(data) {
                            dataRetrieved.call(this, data._embedded[this.options.resultKey]);
                        }.bind(this))
                        .then(function(error) {
                            this.sandbox.logger.log(error);
                        }.bind(this));
                } else {
                    dataRetrieved.call(this, []);
                }
            }
        },

        dataRetrieved = function(data) {
            this.items = data;

            this.sandbox.emit(DATA_RETRIEVED.call(this));
        },

        /**
         * set data of media-selection
         */
         setData = function (data) {
            for (var propertyName in data) {
                if (data.hasOwnProperty(propertyName)) {
                    this.data[propertyName] = data[propertyName];
                }
            }
            this.sandbox.dom.data(this.$el, 'media-selection', this.data);
        },

        /**
         * generates the URI for the request
         */
        setURI = function () {
            var delimiter = (this.options.url.indexOf('?') === -1) ? '?' : '&',
                newURI = [
                    this.options.url,
                    delimiter, this.options.idsParameter, '=', (this.data.ids || []).join(',')
                ].join('');
            // min source must be selected
            if (newURI !== this.URI.str) {
                if (this.URI.str !== '') {
                    this.sandbox.emit(DATA_CHANGED.call(this), this.data, this.$el);
                }
                this.URI.str = newURI;
                this.URI.hasChanged = true;
            } else {
                this.URI.hasChanged = false;
            }
        },

        /**
         * Changes the display option
         * @param event {Object} the click event
         */
        changeDisplayOptions = function (event) {
            // deselect the current positon element
            this.sandbox.dom.removeClass(
                this.sandbox.dom.find('.' + this.options.positionSelectedClass, getId.call(this, 'displayOption')),
                this.options.positionSelectedClass
            );

            // select clicked on
            this.sandbox.dom.addClass(event.currentTarget, this.options.positionSelectedClass);

            setData.call(this, {displayOption: this.sandbox.dom.data(event.currentTarget, 'position')});
            this.sandbox.emit(DATA_CHANGED.call(this), this.data, this.$el);
        },

        /**
         * set display option to element
         */
        setDisplayOption = function () {
            var $element = this.$find(getId.call(this, 'displayOption')),
                $position = this.sandbox.dom.find('[data-position="' + this.data.displayOption + '"]', $element);
            if (!!$position.length) {
                this.sandbox.dom.addClass($position, this.options.positionSelectedClass);
            }
        };

    return {
        historyClosed: true,

        initialize: function () {
            // extend default options
            this.options = this.sandbox.util.extend({}, defaults, this.options);
            this.data = {};

            render.call(this);
        }
    };
});
