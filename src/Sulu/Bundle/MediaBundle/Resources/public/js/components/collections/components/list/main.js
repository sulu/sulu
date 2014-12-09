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
            instanceName: '',
            newThumbnailSrc: 'http://lorempixel.com/150/100/',
            newThumbnailTitel: ''
        },
        constants = {
            datagridSelector: '.datagrid-container',
            toolbarSelector: '.list-toolbar-container',
            newFormSelector: '#collection-new'
        };

    return {

        view: true,

        layout: {
            content: {
                width: 'max'
            }
        },

        header: {
            title: 'media.collections.title',
            noBack: true,

            breadcrumb: [
                {title: 'navigation.media'},
                {title: 'media.collections.title'}
            ]
        },

        templates: [
            '/admin/media/template/collection/new',
            '/admin/media/template/collection/list'
        ],

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // show success-label if collection just got deleted
            this.sandbox.sulu.triggerDeleteSuccessLabel('labels.success.collection-deleted-desc');

            this.bindCustomEvents();
            this.render();
        },

        /**
         * Bind custom-related events
         */
        bindCustomEvents: function () {
            // add a new collection to the list
            this.sandbox.on('sulu.list-toolbar.add', this.openOverlay.bind(this));
            // navigate to colleciton-edit view
            this.sandbox.on('husky.datagrid.item.click', this.navigateToCollection.bind(this));
        },

        /**
         * Adds a new collection the the list
         * @returns {Boolean} returns false if a new and unsafed colleciton exists
         */
        addCollection: function () {
            if (this.sandbox.form.validate(constants.newFormSelector)) {
                var collection = this.sandbox.form.getData(constants.newFormSelector);
                this.sandbox.emit('sulu.media.collections.save-collection', collection, function(collection) {
                    this.sandbox.emit('husky.datagrid.record.add', collection);
                }.bind(this));
            } else {
                return false;
            }
        },

        /**
         * Renders the collections-list
         */
        render: function () {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/list'));
            this.initializeGrid();
        },

        /**
         * Initializes the acutal grid
         */
        initializeGrid: function() {
            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'collections', '/admin/api/media/fields',
                {
                    el: this.$find(constants.toolbarSelector),
                    instanceName: this.options.instanceName,
                    template: 'onlyAdd',
                    inHeader: true
                }, {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/collections',
                    view: 'group',
                    resultKey: 'collections'
                });
        },

        /**
         * Opens a overlay for a new collection
         */
        openOverlay: function () {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            this.$overlayContent = this.renderTemplate('/admin/media/template/collection/new');

            this.sandbox.once('husky.overlay.add-collection.opened', function () {
                this.sandbox.start(constants.newFormSelector);
                this.sandbox.form.create(constants.newFormSelector);
            }.bind(this));

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.sandbox.translate('sulu.media.add-collection'),
                        instanceName: 'add-collection',
                        data: this.$overlayContent,
                        okCallback: this.addCollection.bind(this),
                        openOnStart: true
                    }
                }
            ]);
        },

        /**
         * Navigates to the colleciton-edit view
         * @param collectionId {Number|String} the id of the collection
         */
        navigateToCollection: function (collectionId) {
            this.sandbox.emit('sulu.media.collections.collection-edit', collectionId);
        }
    };
});
