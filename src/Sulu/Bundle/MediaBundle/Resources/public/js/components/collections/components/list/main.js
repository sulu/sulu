/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function () {

    'use strict';

    var defaults = {
        data: {},
        slideDuration: 250, //ms
        instanceName: 'collections',
        newCollectionColor: '#cccccc',
        newCollectionTitle: 'sulu.media.new-collection',
        newTitleKey: 'public.title',
        newColorKey: 'public.color'
    },
    constants = {
        openAllKey: 'public.open-all',
        closeAllKey: 'public.close-all',
        elementsKey: 'public.elements',
        toggleAllClass: 'toggle-all',
        slideDownIcon: 'caret-right',
        slideUpIcon: 'caret-down',
        collectionsClass: 'collections',
        collectionClass: 'collection',
        collectionSlideClass: 'collection-slide',
        rightContentClass: 'rightContent',
        titleClass: 'collection-title',
        colorChangerId: 'change-color',
        titleChangerId: 'change-title',
        colorPointClass: 'collection-color',
        newCollectionId: 'new'
    },

    namespace = 'sulu.collection-list.',

    templates = {
        toggleAll: [
            '<span class="fa-<%= icon %> icon"></span>',
            '<span><%= text %></span>'
        ].join(''),
        collection: [
            '<div class="', constants.collectionClass ,'">',
            '   <div class="head">',
            '       <div class="', constants.colorPointClass ,'" style="background-color: <%= color %>"></div>',
            '       <div class="fa-<%= icon %> icon"></div>',
            '       <div class="', constants.titleClass ,'"><%= title %></div>',
            '       <div class="', constants.rightContentClass ,'"></div>',
            '   </div>',
            '   <div class="', constants.collectionSlideClass ,'"></div>',
            '</div>'
        ].join(''),
        addCollection: [
            '<div class="grid">',
            '   <div class="grid-row">',
            '       <label for="', constants.titleChangerId ,'"><%= titleDesc %></label>',
            '       <input class="form-element" type="text" id="', constants.titleChangerId ,'" value="<%= title %>"/>',
            '   </div>',
            '   <div class="grid-row">',
            '       <label for="', constants.colorChangerId ,'"><%= colorDesc %></label>',
            '       <input class="form-element" type="text" id="', constants.colorChangerId ,'" value="<%= color %>"/>',
            '   </div>',
            '</div>'
        ].join(''),
        totalElements: [
            '<span class="total"><%= total %> <%= elements %></span>'
        ].join('')
    },

    /**
     * raised after the title of a collection got changed
     * @event sulu.collection-list.<instance-name>.title-changed
     * @param {String|Number} id of the collection
     * @param {String} new title of the collection
     */
    TITLE_CHANGED = function() {
        return createEventName.call(this, 'title-changed');
    },

    /**
     * raised after the title of a newly added collection got changed
     * @event sulu.collection-list.<instance-name>.collection-added
     * @param {String} title of the new collection
     */
    COLLECTION_ADDED = function() {
        return createEventName.call(this, 'collection-added');
    },

    /** returns normalized event names */
    createEventName = function(postFix) {
        return namespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
    };

    return {

        view: true,

        header: {
            title: 'media.collections.title',
            noBack: true,

            breadcrumb: [
                {title: 'navigation.media'},
                {title: 'media.collections.title'}
            ]
        },

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.toggleAll = {
                $element: null,
                allOpen: false
            };
            // stores all colleciton objects with the corresponding id
            this.collections = {}
            // stores key value paires of an array of selected elements as value and the corresponding datagrid-name as key
            this.selectedMedias = {};
            this.$overlayContent = null;
            this.lastClickedGrid = null;

            this.bindCustomEvents();
            this.render();
            this.bindDomEvents();
        },

        /**
         * Bind custom-related events
         */
        bindCustomEvents: function() {
            // add a new collection to the list
            this.sandbox.on('sulu.list-toolbar.add', this.openOverlay.bind(this));
            // start list-toolbar after header has initialized
            this.sandbox.on('sulu.header.initialized', this.startListToolbar.bind(this));
            // update a collection in the list if the data-record has changed
            this.sandbox.on('sulu.media.collections.collection-changed', this.updateCollection.bind(this));
            // delete media if list-toolbar delete is clicked
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));

            // update the last clicked grid if a media got changed
            this.sandbox.on('sulu.media.collections.media-saved', function() {
                this.sandbox.emit('husky.datagrid.'+ this.lastClickedGrid +'.update');
            }.bind(this));
        },

        /**
         * Deletes all the selected media
         */
        deleteMedia: function() {
            this.setSelectedMedias().then(function() {
                for (var key in this.selectedMedias) {
                    this.sandbox.emit('sulu.media.collections.delete-media', this.selectedMedias[key], function(key, mediaId) {
                        this.sandbox.emit('husky.datagrid.'+ this.collections[key].datagridName +'.record.remove', mediaId);
                        this.collections[key].selectedElements = 0;
                        delete this.selectedMedias[key];
                    }.bind(this, key));
                }
            }.bind(this));
        },

        /**
         * Asks each datagrid with selected elements for the selected
         * element-ids and stores them in the global array
         */
        setSelectedMedias: function() {
            var key, count = 0, length = Object.keys(this.collections).length,
                dfd = this.sandbox.data.deferred();

            for (var key in this.collections) {
                if (this.collections[key].selectedElements > 0) {
                    this.sandbox.emit('husky.datagrid.'+ this.collections[key].datagridName +'.items.get-selected', function(ids) {
                        // stores the selected media-ids with the id of the corresponding datagrid
                        this.selectedMedias[key] = ids;
                        count++;
                        if (count === length) {
                            dfd.resolve();
                        }
                    }.bind(this));
                } else {
                    length--;
                    if (count === length) {
                        dfd.resolve();
                    }
                }
            }
            return dfd.promise();
        },

        /**
         * Adds a new collection the the list
         * @param name {String} the title of the new collection - optional
         * @returns {Boolean} returns false if a new and unsafed colleciton exists
         */
        addCollection: function() {
            var title = this.sandbox.dom.val(this.sandbox.dom.find('#' + constants.titleChangerId, this.$overlayContent)),
                color = this.sandbox.dom.val(this.sandbox.dom.find('#' + constants.colorChangerId, this.$overlayContent)),
                collection = {
                    id: constants.newCollectionId,
                    mediaNumber: 0,
                    title: title,
                    style: {
                        color: color
                    }
            };
            this.renderCollection(collection, this.$find('.' + constants.collectionsClass));
            this.sandbox.emit(COLLECTION_ADDED.call(this), title, color);
        },

        /**
         * Starts the list toolbar in the header
         */
        startListToolbar: function() {
            var $listtoolbar = this.sandbox.dom.createElement('</div>');
            this.sandbox.dom.append(this.$el, $listtoolbar);
            this.sandbox.start([{
                name: 'list-toolbar@suluadmin',
                options: {
                    el: $listtoolbar,
                    instanceName: this.options.instanceName,
                    template: 'defaultNoSettings',
                    inHeader: true
                }
            }]);
        },

        /**
         * Renders the collections-list
         */
        render: function() {
            var $collections;

            // render toggle all button
            this.toggleAll.$element = this.sandbox.dom.createElement('<div class="'+ constants.toggleAllClass +'"/>');
            this.sandbox.dom.html(this.toggleAll.$element, this.sandbox.util.template(templates.toggleAll)({
                icon: constants.slideDownIcon,
                text: this.sandbox.translate(constants.openAllKey)
            }));
            this.sandbox.dom.append(this.$el, this.toggleAll.$element);

            // render collection items
            $collections = this.sandbox.dom.createElement('<div class="'+ constants.collectionsClass +'"/>');
            this.sandbox.util.foreach(this.options.data, function(collection) {
                this.renderCollection(collection, $collections);
            }.bind(this));
            this.initializeOverlay();
            this.sandbox.dom.append(this.$el, $collections);
        },

        /**
         * Initializes the overlay for adding a new collection
         */
        initializeOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            this.$overlayContent = this.sandbox.dom.createElement(this.sandbox.util.template(templates.addCollection)({
                title: this.sandbox.translate(this.options.newCollectionTitle),
                color: this.options.newCollectionColor,
                titleDesc: this.sandbox.translate(this.options.newTitleKey),
                colorDesc: this.sandbox.translate(this.options.newColorKey)
            }));

            this.sandbox.start([{
                name: 'overlay@husky',
                options: {
                    el: $container,
                    title: this.sandbox.translate('sulu.media.add-collection'),
                    instanceName: 'add-collection',
                    data: this.$overlayContent,
                    okCallback: this.addCollection.bind(this)
                }
            }]);
        },

        /**
         * Opens the add colleciton overlay
         */
        openOverlay: function() {
            this.sandbox.emit('husky.overlay.add-collection.open');
        },

        /**
         * Renders a single collection object
         * @param collection {Object} the collection to render
         * @param $container {Object} the dom element to append to rendred collection to
         */
        renderCollection: function(collection, $container) {
            var $collection = this.sandbox.dom.createElement(this.sandbox.util.template(templates.collection)({
                color: collection.style.color,
                icon: constants.slideDownIcon,
                title: collection.title
            }));

            // insert the total-elements markup
            this.sandbox.dom.html(
                this.sandbox.dom.find('.' + constants.rightContentClass, $collection),
                this.sandbox.util.template(templates.totalElements)({
                    total: collection.mediaNumber,
                    elements: this.sandbox.translate(constants.elementsKey)
                })
            );

            // hide the slide-container
            this.sandbox.dom.hide(this.sandbox.dom.find('.' + constants.collectionSlideClass, $collection));

            this.sandbox.dom.append($container, $collection);
            this.collections[collection.id] = {
                id: collection.id,
                $el: $collection,
                data: collection,
                selectedElements: 0,
                datagridName: null,
                datagridLoaded: false
            };
            this.bindCollectionDomEvents(collection.id);
        },

        /**
         * Replaces a collection with a passed one. If the id of the passed one isn't contained
         * in the global collections object it assumes that the passed collection got newly added
         * @param collection
         */
        updateCollection: function(collection) {
            // if the passed collection already exists override the data property
            if (!!this.collections[collection.id]) {
                this.collections[collection.id].data = collection;

            // else, if a placeholder collection (new one) exists create a new collection object and delete the placeholder
            } else if (!!this.collections[constants.newCollectionId]) {
                this.collections[collection.id] = {
                    $el: this.collections[constants.newCollectionId].$el,
                    data: collection
                };
                // rebind events and start grid
                this.sandbox.dom.off(this.collections[collection.id].$el);
                this.bindCollectionDomEvents(collection.id);
                this.startCollectionDatagrid(
                    collection.id,
                    this.sandbox.dom.find('.' + constants.collectionSlideClass, this.collections[collection.id].$el)
                );

                delete this.collections[constants.newCollectionId];
            } else {
                this.sandbox.logger.log('Error. Undefined collection cannot be updated');
                return false;
            }

            // update title in dom
            this.sandbox.dom.html(
                this.sandbox.dom.find('.' + constants.titleClass, this.collections[collection.id].$el),
                this.collections[collection.id].data.title
            );
            // update color in dom
            this.sandbox.dom.css(
                this.sandbox.dom.find('.' + constants.colorPointClass, this.collections[collection.id].$el),
                {'background-color': this.collections[collection.id].data.style.color}
            );
        },

        /**
         * Starts the preview-datagrid for a collection
         * @param id {Number|String} the collections identifier
         * @param $container {Object} the dom container to start the datagrid in
         */
        startCollectionDatagrid: function (id, $container) {
            // store number of selected elements
            this.sandbox.on('husky.datagrid.'+ this.options.instanceName + id +'.number.selections', function(number) {
                this.collections[id].selectedElements = number;
                this.refreshDeleteState();
            }.bind(this));

            // edit single media on datagrid click
            this.sandbox.on('husky.datagrid.'+ this.options.instanceName + id +'.item.click', function(mediaId) {
                this.lastClickedGrid = this.collections[id].datagridName;
                this.sandbox.emit('sulu.media.collections.edit-media', mediaId);
            }.bind(this));

            this.collections[id].datagridName = this.options.instanceName + id;
            this.collections[id].datagridLoaded = true;
            this.sandbox.sulu.initList.call(this, 'mediaFields', '/admin/api/media/fields',
                {
                    el: $container,
                    url: '/admin/api/media?collection=' + id,
                    view: 'thumbnail',
                    pagination: 'showall',
                    instanceName: this.options.instanceName + id,
                    searchInstanceName: this.options.instanceName,
                    paginationOptions: {
                        showall: {
                            showAllHandler: this.showAllFiles.bind(this, id)
                        }
                    }
                }
            );
        },

        /**
         * Looks if any collection has selected elements and enbalbes or disables the delete button
         * @param {Boolean} returns true if the button got enabled
         */
        refreshDeleteState: function() {
            for (var key in this.collections) {
                if (this.collections[key].selectedElements > 0) {
                    this.sandbox.emit('sulu.list-toolbar.'+ this.options.instanceName +'.delete.state-change', true);
                    return true;
                }
            }
            this.sandbox.emit('sulu.list-toolbar.'+ this.options.instanceName +'.delete.state-change', false);
            return false;
        },

        /**
         * Handles the click on the show-all pagination
         * navigates to another view where all files are accessable
         * @param collectionId {Nubmer|String} the id of the collection
         */
        showAllFiles: function(collectionId) {
            this.sandbox.emit('sulu.media.collections.files', collectionId);
        },

        /**
         * Binds dom related events
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.toggleAll.$element, 'click', this.toggleAllCollections.bind(this));
        },

        /**
         * Binds dom events on a collection element
         * @param id {Number|String} the identifier of the collection
         */
        bindCollectionDomEvents: function(id) {
            if (id !== constants.newCollectionId) {
                // toggle slide-container
                this.sandbox.dom.on(this.collections[id].$el, 'click', function() {
                    this.toggleCollection(this.collections[id]);
                }.bind(this), '.head');

                this.sandbox.dom.on(this.collections[id].$el, 'click', function(event) {
                    this.sandbox.dom.stopPropagation(event);
                    this.showAllFiles(id);
                }.bind(this), '.' + constants.titleClass);
            }
        },

        /**
         * Opens are closes all collections
         */
        toggleAllCollections: function() {
            var action, id;
            if (this.toggleAll.allOpen === true) {
                action = this.slideUp.bind(this);
                this.toggleAll.allOpen = false;
                this.sandbox.dom.html(this.toggleAll.$element, this.sandbox.util.template(templates.toggleAll)({
                    icon: constants.slideDownIcon,
                    text: this.sandbox.translate(constants.openAllKey)
                }));
            } else {
                action = this.slideDown.bind(this);
                this.toggleAll.allOpen = true;
                this.sandbox.dom.html(this.toggleAll.$element, this.sandbox.util.template(templates.toggleAll)({
                    icon: constants.slideUpIcon,
                    text: this.sandbox.translate(constants.closeAllKey)
                }));
            }
            for (id in this.collections) {
                action(this.collections[id]);
            }
        },

        /**
         * Slides a collection up or down
         * @param collection {Object} the object of the collection
         */
        toggleCollection: function(collection) {
            // if slide-container is visible slide it up
            if (this.sandbox.dom.is(
                this.sandbox.dom.find('.' + constants.collectionSlideClass, collection.$el),
                ':visible'
            )) {
                this.slideUp(collection);
            // else slide it down
            } else {
                this.slideDown(collection);
            }
        },

        /**
         * Slides a colleciton down
         * @param $collection {Object} the object of the collection
         */
        slideUp: function(collection) {
            this.sandbox.dom.slideUp(
                this.sandbox.dom.find('.' + constants.collectionSlideClass, collection.$el),
                this.options.slideDuration
            );
            this.sandbox.dom.removeClass(
                this.sandbox.dom.find('.head .icon', collection.$el),
                'fa-' + constants.slideUpIcon
            );
            this.sandbox.dom.prependClass(
                this.sandbox.dom.find('.head .icon', collection.$el),
                'fa-' + constants.slideDownIcon
            );
        },

        /**
         * Slides a colleciton down
         * @param $collection {Object} the object of the collection
         */
        slideDown: function(collection) {
            this.sandbox.dom.slideDown(
                this.sandbox.dom.find('.' + constants.collectionSlideClass, collection.$el),
                this.options.slideDuration
            );
            this.sandbox.dom.removeClass(
                this.sandbox.dom.find('.head .icon', collection.$el),
                'fa-' + constants.slideDownIcon
            );
            this.sandbox.dom.prependClass(
                this.sandbox.dom.find('.head .icon', collection.$el),
                'fa-' + constants.slideUpIcon
            );
            if (collection.datagridLoaded === false) {
                this.startCollectionDatagrid(
                    collection.id,
                    this.sandbox.dom.find('.' + constants.collectionSlideClass, collection.$el)
                );
            }
        }
    };
});
