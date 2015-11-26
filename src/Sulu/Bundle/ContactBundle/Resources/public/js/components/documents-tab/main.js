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
            this.currentSelection = this.sandbox.util.arrayGetColumn(this.data.medias, 'id');
            this.bindCustomEvents();
            this.render();
        },

        render: function() {
            this.html(this.renderTemplate(this.templates[0]));
            this.startSelectionOverlay();
            this.initList();
        },

        bindCustomEvents: function() {
            // checkbox clicked
            this.sandbox.on('husky.datagrid.documents.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('husky.toolbar.documents.item.' + postfix, 'deleteSelected', false);
            }, this);

            this.sandbox.on('sulu.contacts.' + this.options.type + '.document.removed', function(id, mediaId) {
                this.sandbox.emit('husky.datagrid.documents.record.remove', mediaId);
            }.bind(this));

            this.sandbox.on('sulu.media-selection-overlay.documents.record-selected', this.addItem.bind(this));
            this.sandbox.on('sulu.media-selection-overlay.documents.record-deselected', this.removeItem.bind(this));
        },

        addItem: function(id, item) {
            if (!!this.ignoreUpdates) {
                return;
            }

            if (this.currentSelection.indexOf(id) === -1) {
                this.manager.addDocument(this.data.id, id).then(function() {
                    this.sandbox.emit('husky.datagrid.documents.record.add', item);
                    this.currentSelection.push(id);
                }.bind(this));
            }
        },

        removeItem: function(itemId) {
            if (!!this.ignoreUpdates) {
                return;
            }

            this.manager.removeDocuments(this.data.id, itemId).then(function() {
                this.currentSelection = this.sandbox.util.removeFromArray(this.currentSelection, [itemId]);
            }.bind(this));
        },

        /**
         * Opens
         */
        showAddOverlay: function() {
            this.updateOverlaySelected();
            this.sandbox.emit('sulu.media-selection-overlay.documents.open');
        },

        /**
         * Updates selected items in overlay
         */
        updateOverlaySelected: function() {
            this.ignoreUpdates = true;
            this.sandbox.emit('sulu.media-selection-overlay.documents.set-selected', this.currentSelection);
            this.ignoreUpdates = false;
        },

        /**
         * Removes all selected items
         */
        removeSelected: function() {
            this.sandbox.emit('husky.datagrid.documents.items.get-selected', function(ids) {
                this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                    if (!!confirmed) {
                        this.currentSelection = this.sandbox.util.removeFromArray(this.currentSelection, ids);
                        this.manager.removeDocuments(this.data.id, ids);
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Initializes the datagrid-list
         */
        initList: function() {
            var managerData = this.manager.getDocumentsData(this.data.id);
            this.sandbox.sulu.initListToolbarAndList.call(this, managerData.fieldsKey, managerData.fieldsUrl,
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'documents',
                    template: this.getListTemplate(),
                    hasSearch: true
                },
                {
                    el: this.$find('#documents-list'),
                    url: managerData.listUrl,
                    searchInstanceName: 'documents',
                    instanceName: 'documents',
                    resultKey: 'media',
                    searchFields: ['name', 'title', 'description'],
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
                add: {
                    options: {
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
                name: 'media-selection-overlay@sulumedia',
                options: {
                    el: $container,
                    instanceName: 'documents',
                    preselectedIds: this.currentSelection
                }
            }]);
        }
    };
});
