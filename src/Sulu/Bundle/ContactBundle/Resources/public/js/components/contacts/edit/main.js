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
    'services/sulucontact/contact-router'
], function(ContactManager, ContactRouter) {

    'use strict';

    return {

        collaboration: function() {
            if (!this.options.id) {
                return;
            }

            return {
                id: this.options.id,
                type: 'contact'
            };
        },

        /**
         * Returns the header config for this main-view
         * if an existing contact is edited a delete-button and a toggler get added
         * @return {Object} the header config object
         */
        header: function() {
            var config = {
                title: function() {
                    return this.data.fullName;
                }.bind(this),
                tabs: {
                    url: '/admin/content-navigations?alias=contact',
                    options: {
                        disablerToggler: 'husky.toggler.sulu-toolbar',
                        data: function() {
                            // this.data is set by sulu-content.js with data from loadComponentData()
                            return this.sandbox.util.extend(false, {}, this.data);
                        }.bind(this)
                    },
                    componentOptions: {
                        values: this.data
                    }
                },
                toolbar: {
                    buttons: {
                        save: {
                            parent: 'saveWithOptions'
                        }
                    }
                }
            };
            if (!!this.options.id) {
                config.toolbar.buttons.delete = {};
                config.toolbar.buttons.disabler = {
                    parent: 'toggler',
                    options: {
                        title: 'public.locked',
                        hidden: true
                    }
                };
                config.toolbar.buttons.enable = {
                    options: {
                        title: 'user.enable',
                        hidden: true
                    }
                };
            }
            return config;
        },

        /**
         * Load contact data to given options.id
         * Method is called from sulu-content when initializing tabs
         * Loaded contact is stored to this.data
         * @returns {*}
         */
        loadComponentData: function() {
            var promise = this.sandbox.data.deferred();
            ContactManager.loadOrNew(this.options.id).then(function(data) {
                promise.resolve(data);
            });
            return promise;
        },

        initialize: function() {
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', ContactRouter.toList);
            this.sandbox.on('sulu.toolbar.delete', this.deleteContact.bind(this));
            this.sandbox.on('sulu.tab.dirty', this.enableSave.bind(this));
            this.sandbox.on('sulu.router.navigate', this.disableSave.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
            this.sandbox.on('sulu.tab.saving', this.loadingSave.bind(this));
            this.sandbox.on('sulu.tab.data-changed', this.changeData.bind(this));
        },

        /**
         * Show delete-confirm dialog and if confirmed, delete current contact
         */
        deleteContact: function() {
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!!confirmed) {
                    ContactManager.delete(this.options.id).then(function() {
                        ContactRouter.toList();
                    }.bind(this));
                }
            }.bind(this));
        },

        /**
         * Overrides the views-data
         * @param newData
         */
        changeData: function(newData) {
            this.data = newData;
        },

        /**
         * Saves the tab and returns a after the tab has saved itselve
         * @returns promise with the saved data
         */
        saveTab: function() {
            var promise = $.Deferred();
            this.sandbox.once('sulu.tab.saved', function(savedData, updateData) {
                if (!!updateData) {
                    this.changeData(savedData);
                }
                promise.resolve(savedData);
            }.bind(this));
            this.sandbox.emit('sulu.tab.save');
            return promise;
        },

        /**
         * Saves all the data and executes the afterSave-method
         * @param action
         */
        save: function(action) {
            this.saveTab().then(function(savedData) {
                this.afterSave(action, savedData);
            }.bind(this));
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
         * Disables the save-button
         */
        disableSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', false);
        },

        /**
         * Executes the after save action: Navigates to edit, add or list
         * @param action {String} the after-save action
         * @param savedData {Object} the data after the save-process has finished
         */
        afterSave: function(action, savedData) {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            this.sandbox.emit('sulu.header.saved', savedData);
            if (action === 'back') {
                ContactRouter.toList();
            } else if (action === 'new') {
                ContactRouter.toAdd();
            } else if (!this.options.id) {
                ContactRouter.toEdit(savedData.id);
            }
        }
    };
});
