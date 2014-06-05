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
            instanceName: 'undefined',
            newCollectionColor: '#cccccc',
            newCollectionTitle: 'sulu.media.new-collection'
        },
        constants = {
            openAllKey: 'public.open-all',
            closeAllKey: 'public.close-all',
            toggleAllClass: 'toggle-all',
            slideDownIcon: 'caret-right',
            slideUpIcon: 'caret-down',
            newFormId: 'collection-new'
        },

        namespace = 'sulu.collection-list.',

        templates = {
            toggleAll: [
                '<span class="fa-<%= icon %> icon"></span>',
                '<span><%= text %></span>'
            ].join('')
        };

    return {

        view: true,

        fullSize: {
            width: true,
            keepPaddings: true
        },

        header: {
            title: 'media.collections.title',
            noBack: true,

            breadcrumb: [
                {title: 'navigation.media'},
                {title: 'media.collections.title'}
            ]
        },

        templates: ['/admin/media/template/collection/new'],

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // show success-label if collection just got deleted
            this.sandbox.sulu.triggerDeleteSuccessLabel('labels.success.collection-deleted-desc');

            this.toggleAll = {
                $element: null,
                allOpen: false
            };
            this.$overlayContent = null;
            this.toolbarStarted = false;

            this.bindCustomEvents();
            this.render();
            this.startListToolbar();
            this.bindDomEvents();
        },

        /**
         * Bind custom-related events
         */
        bindCustomEvents: function () {
            // add a new collection to the list
            this.sandbox.on('sulu.list-toolbar.add', this.openOverlay.bind(this));
            // start list-toolbar after header has initialized
            this.sandbox.on('sulu.header.initialized', this.startListToolbar.bind(this));
            // delete media if list-toolbar delete is clicked
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));
            // route to collection-edit
            this.sandbox.on('sulu.grid-group.collections.show-group', this.showAllFiles.bind(this));
            // activate/deactive delete button
            this.sandbox.on('sulu.grid-group.collections.elements-selected', this.toggleDeleteButton.bind(this));

            // update a group in grid if collection was changed
            this.sandbox.on('sulu.media.collections.collection-changed', function(collectionId) {
                this.sandbox.emit('sulu.grid-group.collections.update-group', collectionId);
            }.bind(this));

            // edit media on click
            this.sandbox.on('sulu.grid-group.collections.record-clicked', function(recordId) {
                this.sandbox.emit('sulu.media.collections.edit-media', recordId);
            }.bind(this));

            // update the last clicked grid if a media got changed
            this.sandbox.on('sulu.media.collections.media-saved', function () {
                this.sandbox.emit('sulu.grid-group.collections.update-last-clicked');
            }.bind(this));
        },

        /**
         * Deletes all the selected media
         */
        deleteMedia: function () {
            this.sandbox.sulu.showDeleteDialog(function (confirmed) {
                if (confirmed === true) {
                    this.sandbox.emit('sulu.grid-group.collections.get-selected', function(selectedMedias) {
                        for (var groupId in selectedMedias) {
                            if (selectedMedias.hasOwnProperty(groupId)) {
                                this.sandbox.emit('sulu.media.collections.delete-media', selectedMedias[groupId], function (groupId, recordId) {
                                    this.sandbox.emit('sulu.grid-group.collections.remove-record', groupId, recordId);
                                }.bind(this, groupId), true);
                            }
                        }
                    }.bind(this));
                }
            }.bind(this));
        },

        /**
         * Adds a new collection the the list
         * @returns {Boolean} returns false if a new and unsafed colleciton exists
         */
        addCollection: function () {
            if (this.sandbox.form.validate('#' + constants.newFormId)) {
                var data = this.sandbox.form.getData('#' + constants.newFormId),
                    collection = {
                        mediaNumber: 0,
                        style: {
                            color: data.color
                        }
                    };
                collection = this.sandbox.util.extend(true, {}, collection, data);
                this.sandbox.emit('sulu.grid-group.collections.add-group', collection);
                this.sandbox.emit('sulu.media.collections.save-collection', collection);
            } else {
                return false;
            }
        },

        /**
         * Starts the list toolbar in the header
         */
        startListToolbar: function () {
            if (this.toolbarStarted === false) {
                this.toolbarStarted = true;
                var $listtoolbar = this.sandbox.dom.createElement('</div>');
                this.sandbox.dom.append(this.$el, $listtoolbar);
                this.sandbox.start([
                    {
                        name: 'list-toolbar@suluadmin',
                        options: {
                            el: $listtoolbar,
                            instanceName: this.options.instanceName,
                            template: 'defaultNoSettings',
                            inHeader: true
                        }
                    }
                ]);
            }
        },

        /**
         * Renders the collections-list
         */
        render: function () {
            // render toggle all button
            this.toggleAll.$element = this.sandbox.dom.createElement('<div class="' + constants.toggleAllClass + '"/>');
            this.sandbox.dom.html(this.toggleAll.$element, this.sandbox.util.template(templates.toggleAll)({
                icon: constants.slideDownIcon,
                text: this.sandbox.translate(constants.openAllKey)
            }));
            this.sandbox.dom.append(this.$el, this.toggleAll.$element);

            this.initializeGridGroup();
            this.initializeOverlay();
        },

        /**
         * Initializes the grid-group
         */
        initializeGridGroup: function() {
            var $collections = this.sandbox.dom.createElement('<div/>');
            this.sandbox.start([{
                name: 'grid-group@suluadmin',
                options: {
                    data: this.options.data,
                    el: $collections,
                    instanceName: 'collections',
                    gridUrl: '/admin/api/media?collection=',
                    fieldsUrl: '/admin/api/media/fields',
                    fieldsKey: 'mediaFields'
                }
            }]);
            this.sandbox.dom.append(this.$el, $collections);
        },

        /**
         * Initializes the overlay for adding a new collection
         */
        initializeOverlay: function () {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            this.$overlayContent = this.renderTemplate('/admin/media/template/collection/new');

            this.sandbox.once('husky.overlay.add-collection.opened', function () {
                this.sandbox.start('#' + constants.newFormId);
                this.sandbox.form.create('#' + constants.newFormId);
                this.sandbox.form.setData('#' + constants.newFormId, {
                    title: this.sandbox.translate(this.options.newCollectionTitle),
                    color: this.options.newCollectionColor
                });
            }.bind(this));

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.sandbox.translate('sulu.media.add-collection'),
                        instanceName: 'add-collection',
                        data: this.$overlayContent,
                        okCallback: this.addCollection.bind(this)
                    }
                }
            ]);
        },

        /**
         * Opens the add collection overlay
         */
        openOverlay: function () {
            this.sandbox.emit('husky.overlay.add-collection.open');
        },

        /**
         * Handles the click on the show-all pagination
         * navigates to another view where all files are accessable
         * @param collectionId {Number|String} the id of the collection
         */
        showAllFiles: function (collectionId) {
            this.sandbox.emit('sulu.media.collections.collection-edit', collectionId);
        },

        /**
         * Binds dom related events
         */
        bindDomEvents: function () {
            this.sandbox.dom.on(this.toggleAll.$element, 'click', this.toggleAllCollections.bind(this));
        },

        /**
         * Opens are closes all collections
         */
        toggleAllCollections: function () {
            if (this.toggleAll.allOpen === true) {
                this.toggleAll.allOpen = false;
                this.sandbox.dom.html(this.toggleAll.$element, this.sandbox.util.template(templates.toggleAll)({
                    icon: constants.slideDownIcon,
                    text: this.sandbox.translate(constants.openAllKey)
                }));
                this.sandbox.emit('sulu.grid-group.collections.close-all-groups');
            } else {
                this.toggleAll.allOpen = true;
                this.sandbox.dom.html(this.toggleAll.$element, this.sandbox.util.template(templates.toggleAll)({
                    icon: constants.slideUpIcon,
                    text: this.sandbox.translate(constants.closeAllKey)
                }));
                this.sandbox.emit('sulu.grid-group.collections.show-all-groups');
            }
        },

        /**
         * Activates/Deactivates delete-button
         * @param activate {Boolean} if true activates if false deactivates delete button
         */
        toggleDeleteButton: function(activate) {
            if (activate === true) {
                this.sandbox.emit('sulu.list-toolbar.' + this.options.instanceName + '.delete.state-change', true);
            } else {
                this.sandbox.emit('sulu.list-toolbar.' + this.options.instanceName + '.delete.state-change', false);
            }
        }
    };
});
