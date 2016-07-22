/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulusecurity/role-manager',
    'services/sulusecurity/role-router'
], function(RoleManager, RoleRouter) {

    'use strict';

    return {

        collaboration: function() {
            if (!this.options.id) {
                return;
            }

            return {
                id: this.options.id,
                type: 'roles'
            };
        },

        loadComponentData: function() {
            if (!!this.options.id) {
                return RoleManager.load(this.options.id);
            }

            return {
                name: '',
                permissions: [],
                system: ''
            };
        },

        header: function() {
            var config = {
                tabs: {
                    url: '/admin/content-navigations?alias=roles',
                    options: {
                        data: function() {
                            return this.data;
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
            }

            return config;
        },

        initialize: function() {
            this.action = null;
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', RoleRouter.toList);
            this.sandbox.on('sulu.toolbar.delete', this.deleteRole.bind(this));
            this.sandbox.on('sulu.tab.dirty', this.enableSave.bind(this));
            this.sandbox.on('sulu.router.navigate', this.disableSave.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.saveTab.bind(this));
            this.sandbox.on('sulu.tab.saving', this.loadingSave.bind(this));
            this.sandbox.on('sulu.tab.data-changed', this.changeData.bind(this));
            this.sandbox.on('sulu.tab.saved', this.tabSavedHandler.bind(this));
            this.sandbox.on('sulu.tab.save-error', this.saveErrorHandler.bind(this));
        },

        changeData: function(data) {
            this.data = data;
        },

        enableSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
        },

        disableSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', false);
        },

        loadingSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
        },

        /**
         * Handles an error which happened when saving the tabs
         */
        saveErrorHandler: function(code) {
            var errorMessage = '';
            this.disableSave();
            if (code == 1101) {
                errorMessage = 'security.roles.error.non-unique';
            }
            this.sandbox.emit('sulu.labels.error.show', errorMessage);
        },

        /**
         * Asks the user if the role should be deleted. When confirmed
         * the role gets deleted and the application navigates to the list.
         */
        deleteRole: function() {
            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    RoleManager.delete(this.options.id).then(function() {
                        RoleRouter.toList();
                    }.bind(this));
                }
            }.bind(this));
        },

        /**
         * Saves the current tab.
         */
        saveTab: function(action) {
            this.action = action;
            this.sandbox.emit('sulu.tab.save');
        },

        /**
         * Handler which gets executed after a tab has saved
         *
         * @param {Object} savedData The data which got saved
         * @param {Boolean} updateData True to update the component data
         */
        tabSavedHandler: function(savedData, updateData) {
            this.sandbox.emit('sulu.labels.success.show', 'sulu-security.role.saved');
            this.sandbox.emit('sulu.labels.warning.show', 'security.warning');
            if (!!updateData) {
                this.changeData(savedData);
            }
            this.afterSave(savedData);
        },

        /**
         * Executes the after save action: Navigates to edit, add or list
         *
         * @param {Object} savedData the data after the save-process has finished
         */
        afterSave: function(savedData) {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            this.sandbox.emit('sulu.header.saved', savedData);
            if (this.action === 'back') {
                RoleRouter.toList();
            } else if (this.action === 'new') {
                RoleRouter.toAdd();
            } else if (!this.options.id) {
                RoleRouter.toEdit(savedData.id);
            }
        }
    };
});
