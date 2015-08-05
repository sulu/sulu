/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
        'services/sulucontact/contact-manager',
        'services/sulucontact/contact-router',
        'services/sulucontact/contact-delete-dialog'
    ],
    function(ContactManager, ContactRouter, DeleteDialog) {

    'use strict';

    return {
        header: {
            tabs: {
                url: '/admin/content-navigations?alias=contact'
            },
            toolbar: {
                buttons: {
                    save: {
                        parent: 'saveWithOptions'
                    },
                    delete: {}
                }
            }
        },

        initialize: function() {
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', ContactRouter.toList);
            this.sandbox.on('sulu.toolbar.delete', this.deleteContact.bind(this));
            this.sandbox.on('sulu.tab.dirty', this.enableSave.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.saveTab.bind(this));
            this.sandbox.on('sulu.tab.saving', this.loadingSave.bind(this));
            this.sandbox.on('sulu.tab.saved', this.afterSave.bind(this));
        },

        deleteContact: function() {
            DeleteDialog.showDialog([this.options.id], function(){
                ContactManager.delete(this.options.id).then(function() {
                    ContactRouter.toList();
                }.bind(this));
            }.bind(this));
        },

        /**
         * Sets the save-button into loading-state and tells the tab to save itselve
         * @param action {String} the after-save action
         */
        saveTab: function(action) {
            this.afterSaveAction = action;
            this.sandbox.emit('sulu.tab.save');
        },

        /**
         * Sets the save-button in loading-state
         */
        loadingSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
        },

        /**
         * Enables the save-button
         */
        enableSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
        },

        /**
         * Executes the after save action: Navigates to edit, add or list
         * @param savedData {Object} the data after the save-process has finished
         */
        afterSave: function(savedData) {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            if (this.afterSaveAction === 'back') {
                ContactRouter.toList();
            } else if (this.afterSaveAction === 'new') {
                ContactRouter.toAdd();
            } else if (!this.options.id) {
                ContactRouter.toEdit(savedData.id);
            }
        }
    };
});
