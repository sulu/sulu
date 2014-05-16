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
        instanceName: 'collections'
    },
    constants = {
        openAllKey: 'public.open-all',
        closeAllKey: 'public.close-all',
        elementsKey: 'public.elements',
        toggleAllClass: 'toggle-all',
        slideDownIcon: 'play',
        slideUpIcon: 'play-down',
        collectionsClass: 'collections',
        collectionClass: 'collection',
        collectionSlideClass: 'slide',
        rightContentClass: 'rightContent'
    },

    templates = {
        toggleAll: [
            '<span class="icon-<%= icon %> icon"></span>',
            '<span><%= text %></span>'
        ].join(''),
        collection: [
            '<div class="', constants.collectionClass ,'">',
            '   <div class="head">',
            '       <div class="color" style="background-color: <%= color %>"></div>',
            '       <div class="icon-<%= icon %> icon"></div>',
            '       <div class="title"><%= title %></div>',
            '       <div class="', constants.rightContentClass ,'"></div>',
            '   </div>',
            '   <div class="', constants.collectionSlideClass ,'"></div>',
            '</div>'
        ].join(''),
        totalElements: [
            '<span class="total"><%= total %> <%= elements %></span>'
        ].join('')
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
            // stores all colleciton elements with the corresponding id
            this.collections = {}

            this.render();
            this.startListToolbar();
            this.bindDomEvents();
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
                    template: 'default',
                    inHeader: true
                }
            }]);
        },

        /**
         * Renders the collections-list
         */
        render: function() {
            var $collections, $collection;

            // render toggle all button
            this.toggleAll.$element = this.sandbox.dom.createElement('<div class="'+ constants.toggleAllClass +'"/>');
            this.sandbox.dom.html(this.toggleAll.$element, this.sandbox.util.template(templates.toggleAll)({
                icon: constants.slideDownIcon,
                text: this.sandbox.translate(constants.openAllKey)
            }));
            this.sandbox.dom.append(this.$el, this.toggleAll.$element);

            // render collection items
            $collections = this.sandbox.dom.createElement('<div class="'+ constants.collectionsClass +'"/>');
            this.sandbox.util.foreach(this.options.data._embedded, function(collection) {
                $collection = this.sandbox.dom.createElement(this.sandbox.util.template(templates.collection)({
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

                this.sandbox.dom.append($collections, $collection);
                this.collections[collection.id] = $collection;
                this.startCollectionDatagrid(collection, this.sandbox.dom.find('.' + constants.collectionSlideClass, $collection));
                this.bindCollectionDomEvents(collection.id, $collection);
            }.bind(this));
            this.sandbox.dom.append(this.$el, $collections);
        },

        /**
         * Starts the preview-datagrid for a collection
         * @param collection {Object} the collection object
         * @param $container {Object} the dom container to start the datagrid in
         */
        startCollectionDatagrid: function (collection, $container) {
            //TODO: don't fetch the data from contact-bundle
            this.sandbox.sulu.initList.call(this, 'accountsFields'+collection.id, '/admin/api/contacts/fields',
                {
                    el: $container,
                    url: '/admin/api/contacts?flat=true',
                    view: 'thumbnail',
                    pagination: 'showall',
                    instanceName: this.options.instanceName + collection.id,
                    searchInstanceName: this.options.instanceName,
                    paginationOptions: {
                        showall: {
                            showAllHandler: this.showAllFiles.bind(this, collection.id)
                        }
                    }
                }
            );
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
         * @param $collection {Object} the dom element of the collection
         */
        bindCollectionDomEvents: function(id, $collection) {
            // toggle slide-container
            this.sandbox.dom.on(this.sandbox.dom.find('.head', $collection), 'click', function() {
                this.toggleCollection($collection);
            }.bind(this));
        },

        /**
         * Opens are closes all collections
         */
        toggleAllCollections: function() {
            var action, $id;
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
            for ($id in this.collections) {
                action(this.collections[$id]);
            }
        },

        /**
         * Slides a collection up or down
         * @param $collection {Object} the dom object of the collection
         */
        toggleCollection: function($collection) {
            // if slide-container is visible slide it up
            if (this.sandbox.dom.is(
                this.sandbox.dom.find('.' + constants.collectionSlideClass, $collection),
                ':visible'
            )) {
                this.slideUp($collection);
            // else slide it down
            } else {
                this.slideDown($collection);
            }
        },

        /**
         * Slides a colleciton down
         * @param $collection {Object} the dom object of the colleciton
         */
        slideUp: function($collection) {
            this.sandbox.dom.slideUp(
                this.sandbox.dom.find('.' + constants.collectionSlideClass, $collection),
                this.options.slideDuration
            );
            this.sandbox.dom.removeClass(
                this.sandbox.dom.find('.head .icon', $collection),
                'icon-' + constants.slideUpIcon
            );
            this.sandbox.dom.prependClass(
                this.sandbox.dom.find('.head .icon', $collection),
                'icon-' + constants.slideDownIcon
            );
        },

        /**
         * Slides a colleciton down
         * @param $collection {Object} the dom object of the colleciton
         */
        slideDown: function($collection) {
            this.sandbox.dom.slideDown(
                this.sandbox.dom.find('.' + constants.collectionSlideClass, $collection),
                this.options.slideDuration
            );
            this.sandbox.dom.removeClass(
                this.sandbox.dom.find('.head .icon', $collection),
                'icon-' + constants.slideDownIcon
            );
            this.sandbox.dom.prependClass(
                this.sandbox.dom.find('.head .icon', $collection),
                'icon-' + constants.slideUpIcon
            );
        }
    };
});
