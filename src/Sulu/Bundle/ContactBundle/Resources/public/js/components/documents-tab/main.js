/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulucontact/account-manager',
    'services/sulucontact/contact-manager'
], function(AccountManager, ContactManager) {

    'use strict';

    return {

        stickyToolbar: 140,

        layout: function() {
            return {
                content: {
                    width: 'fixed'
                }
            };
        },

        templates: ['/admin/contact/template/basic/documents'],

        initialize: function() {
            this.manager = (this.options.type === 'contact') ? ContactManager : AccountManager;

            this.data = this.options.data();
            this.bindCustomEvents();
            this.render();
        },

        render: function() {
            this.html(this.renderTemplate(this.templates[0]));
            this.startSelectionOverlay();
            this.initList();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.tab.save', this.saveMedias, this);

            // enable delete button if selection not empty
            this.sandbox.on('husky.datagrid.documents.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('husky.toolbar.documents.item.' + postfix, 'deleteSelected', false);
            }, this);

            // enable save button if something changes
            this.sandbox.on('husky.datagrid.documents.records.remove', function () {
                this.sandbox.emit('sulu.tab.dirty');
            }, this);
            
            this.sandbox.on('husky.datagrid.documents.records.set', function () {
                this.sandbox.emit('sulu.tab.dirty');
            }, this);
        },

        saveMedias: function() {
            this.sandbox.emit('sulu.tab.saving');
            this.sandbox.emit('husky.datagrid.documents.records.get', function(displayedMedias) {
                var mediaIds = _.map(displayedMedias, function (media) { return media.id; });
                this.manager.setDocuments(this.data.id, mediaIds).then(function (savedData) {
                    this.sandbox.emit('sulu.tab.saved', savedData, true);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Open the media selection overlay
         */
        showAddOverlay: function() {
            this.sandbox.emit('husky.datagrid.documents.records.get', function(displayedMedias) {
                this.sandbox.emit('sulu.media-selection-overlay.document-selection.set-items', displayedMedias);
                this.sandbox.emit('sulu.media-selection-overlay.document-selection.open');
            }.bind(this));
        },

        /**
         * Removes selected medias
         */
        removeSelected: function() {
            this.sandbox.emit('husky.datagrid.documents.items.get-selected', function(mediaIds) {
                this.sandbox.emit('husky.datagrid.documents.records.remove', mediaIds);
            }.bind(this));
        },

        /**
         * Initializes the datagrid-list
         * Search and sort are disabled, because they would reload the data from the server and therefore override
         * unsaved local changes.
         */
        initList: function() {
            var managerData = this.manager.getDocumentsData(this.data.id);
            this.sandbox.sulu.initListToolbarAndList.call(this, managerData.fieldsKey, managerData.fieldsUrl,
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'documents',
                    hasSearch: false,
                    template: this.getListTemplate()
                },
                {
                    el: this.$find('#documents-list'),
                    url: managerData.listUrl,
                    instanceName: 'documents',
                    resultKey: 'media',
                    searchFields: ['name', 'title', 'description'],
                    sortable: false,
                    clickCallback: function(id, item) {
                        window.location.href = item.url;
                    },
                    viewOptions: {
                        table: {
                            selectItem: {
                                type: 'checkbox'
                            }
                        }
                    }
                }
            );
        },

        /**
         * @returns {Array} buttons used by the list-toolbar
         */
        getListTemplate: function() {
            return this.sandbox.sulu.buttons.get({
                edit: {
                    options: {
                        class: 'highlight',
                        callback: this.showAddOverlay.bind(this)
                    }
                },
                deleteSelected: {
                    options: {
                        callback: this.removeSelected.bind(this)
                    }
                }
            });
        },

        /**
         * Starts the overlay-component responsible for selecting the documents
         */
        startSelectionOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $container);

            this.sandbox.start([{
                name: 'media-selection/overlay@sulumedia',
                options: {
                    el: $container,
                    instanceName: 'document-selection',
                    removeable: false,
                    saveCallback: function(overlayItems) {
                        this.sandbox.emit('husky.datagrid.documents.records.set', overlayItems);
                    }.bind(this)
                }
            }]);
        }
    };
});
