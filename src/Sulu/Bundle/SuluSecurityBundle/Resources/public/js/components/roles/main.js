/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalstore', './models/role'], function(Store, Role) {

    'use strict';

    var permissionCodes = {VIEW: 64, ADD: 32, EDIT: 16, DELETE: 8, ARCHIVE: 4, LIVE: 2, SECURITY: 1 },
        sandbox,
        idDelete,

    // callback for deleting a role after confirming
        delSubmit = function() {
            sandbox.emit('husky.dialog.hide');
            sandbox.emit('husky.header.button-state', 'loading-delete-button');

            Store.reset();

            var role = new Role({id: idDelete});
            role.destroy({
                success: function() {
                    sandbox.emit('sulu.router.navigate', 'settings/roles');
                },
                error: function() {
                    // TODO Output error message
                    sandbox.emit('husky.header.button-state', 'standard');
                }
            });

            unbindDialogListener();
        },

    // callback for aborting the deletion of a role
        hideDialog = function() {
            sandbox.emit('husky.dialog.hide');
            unbindDialogListener();
        },

    // binds the listeners to the dialog box
        bindDialogListener = function() {
            sandbox.on('husky.dialog.submit', delSubmit);
            sandbox.on('husky.dialog.cancel', hideDialog);
        },

    // unbind the listeners of the dialog box
        unbindDialogListener = function() {
            sandbox.off('husky.dialog.submit', delSubmit);
            sandbox.off('husky.dialog.cancel', hideDialog);
        },

    // converts the permissions from the database to a easier represenation for the form
        convertPermissionsFromBinary = function(contexts) {
            var permissions = {};
            // go through all contexts
            contexts.forEach(function(permissionData) {
                permissions[permissionData.context] = {};
                // check all permissions for this context
                for (var key in permissionCodes) {
                    if (permissionCodes.hasOwnProperty(key)) {
                        permissions[permissionData.context][key] = !!(permissionData.permissions & permissionCodes[key]);
                    }
                }
            });
            return permissions;
        },

    // converts the permissions from the easier representation for the form to the database
        convertPermissionsToBinary = function(contexts) {
            console.log(contexts);
        };


    return {
        name: 'Sulu Security Role',

        initialize: function() {
            sandbox = this.sandbox;

            if (this.options.display == 'list') {
                this.renderList();
            } else if (this.options.display == 'form') {
                this.renderForm();
            }

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            sandbox.on('sulu.roles.new', function() {
                this.add();
            }.bind(this));

            sandbox.on('sulu.roles.load', function(id) {
                this.load(id);
            }.bind(this));

            sandbox.on('sulu.roles.save', function(data) {
                this.save(data);
            }.bind(this));

            sandbox.on('sulu.roles.delete', function(id) {
                this.del(id);
            }.bind(this));
        },

        // redirects to a new form, when the sulu.roles.new event is thrown
        add: function() {
            sandbox.emit('husky.header.button-state', 'loading-add-button');
            sandbox.emit('sulu.router.navigate', 'settings/roles/new');
        },

        // redirects to the form with the role data, when the sulu.roles.load event with an id is thrown
        load: function(id) {
            sandbox.emit('husky.header.button-state', 'loading-add-button');

            Store.reset();

            sandbox.emit('sulu.router.navigate', 'settings/roles/edit:' + id);
        },


        // saves the data, which is thrown together with a sulu.roles.save event
        save: function(data) {
            sandbox.emit('husky.header.button-state', 'loading-save-button');

            Store.reset();

            var role = new Role(data);
            role.save(null, {
                success: function() {
                    sandbox.emit('sulu.router.navigate', 'settings/roles');
                },
                error: function() {
                    sandbox.emit('sulu.dialog.error.show', 'An error occured during saving the role!');
                    sandbox.emit('husky.header.button-state', 'standard');
                }
            });
        },

        // deletes the role with the id thrown with the sulu.role.delete event
        del: function(id) {
            idDelete = id;

            // show dialog and call delete only when user confirms
            sandbox.emit('sulu.dialog.confirmation.show', {
                content: {
                    title: 'Be careful!',
                    content: [
                        '<p>',
                        'This operation you are about to do will delete data. <br /> This is not undoable!',
                        '</p>',
                        '<p>',
                        ' Please think about it and accept or decline.',
                        '</p>'
                    ].join('')
                },
                footer: {
                    buttonCancelText: 'Don\'t do it',
                    buttonSubmitText: 'Do it, I understand'
                }
            });

            bindDialogListener();
        },

        renderList: function() {
            sandbox.start([
                {
                    name: 'roles/components/list@sulusecurity',
                    options: {
                        el: this.options.el
                    }
                }
            ]);
        },

        renderForm: function() {
            var role = new Role();

            var component = {
                name: 'roles/components/form@sulusecurity',
                options: {
                    el: this.options.el,
                    data: role.defaults()
                }
            };

            if (!!this.options.id) {
                role.set({id: this.options.id});
                role.fetch({
                    success: function(model) {
                        component.options.data = model.toJSON();
                        component.options.data.permissions = convertPermissionsFromBinary(
                            model.get('permissions').toJSON() // add non-used contexts
                        );
                        sandbox.start([component]);
                    }
                });
            } else {
                sandbox.start([component]);
            }
        }
    }
});