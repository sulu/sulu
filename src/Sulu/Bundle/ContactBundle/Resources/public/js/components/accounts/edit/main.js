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
    'services/sulucontact/account-router',
    'services/sulucontact/account-delete-dialog',], function(AccountManager, AccountRouter, DeleteDialog) {

    'use strict';

    return {
        header: function() {
            return {
                tabs: {
                    url: '/admin/content-navigations?alias=account'
                },
                toolbar: {
                    buttons: {
                        save: {
                            parent: 'saveWithOptions'
                        },
                        delete: {}
                    }
                }
            };
        },

        initialize: function() {
            this.bindCustomEvents();
            this.afterSaveAction = '';
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', AccountRouter.toList);
            this.sandbox.on('sulu.tab.dirty', this.enableSave.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
            this.sandbox.on('sulu.tab.saving', this.loadingSave.bind(this));
            this.sandbox.on('sulu.tab.saved', this.afterSave.bind(this));
            this.sandbox.on('sulu.toolbar.delete', this.deleteAccount.bind(this));
        },

        deleteAccount: function() {
            DeleteDialog.showDialog([this.options.id], function(deleteContacts){
                AccountManager.delete(this.options.id, deleteContacts).then(function() {
                    AccountRouter.toList();
                }.bind(this));
            }.bind(this));
        },

        /**
         * Sets the save-button into loading-state and tells the tab to save itselve
         * @param action {String} the after-save action
         */
        save: function(action) {
            this.afterSaveAction = action;
            this.sandbox.emit('sulu.tab.save');
        },

        /**
         * Enables the save-button
         */
        enableSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
        },

        /**
         * Sets the save-button in loading-state
         */
        loadingSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
        },

        /**
         * Executes the after save action: Navigates to edit, add or list
         * @param savedData {Object} the data after the save-process has finished
         */
        afterSave: function(savedData) {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            if (this.afterSaveAction === 'back') {
                AccountRouter.toList();
            } else if (this.afterSaveAction === 'new') {
                AccountRouter.toAdd();
            } else if (!this.options.id) {
                AccountRouter.toEdit(savedData.id);
            }
        }
    };
});
