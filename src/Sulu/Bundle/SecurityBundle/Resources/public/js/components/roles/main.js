/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


define(['sulusecurity/models/role'], function(Role) {

    'use strict';

    var sandbox,
        role,
        idDelete,
        loading = 'delete',

    // callback for deleting a role after confirming
        delSubmit = function() {
            sandbox.emit('husky.dialog.hide');
            sandbox.emit('husky.header.button-state', 'loading-' + loading + '-button');

            unbindDialogListener();

            if (typeof idDelete === 'number' || typeof idDelete === 'string') {
                delSubmitOnce(idDelete, true);
            } else {
                idDelete.forEach(function(id) {
                    delSubmitOnce(id, false);
                });
            }

            sandbox.emit('husky.header.button-state', 'standard');
        },

        delSubmitOnce = function(id, navigate) {
            role.set({id: id});
            role.destroy({
                success: function() {
                    if (!!navigate) {
                        sandbox.emit('sulu.router.navigate', 'settings/roles');
                    } else {
                        sandbox.emit('husky.datagrid.row.remove', id);
                    }
                },
                error: function() {
                    // TODO Output error message
                    sandbox.emit('husky.header.button-state', 'standard');
                }
            });
        },

    // callback for aborting the deletion of a role
        hideDialog = function() {
            sandbox.emit('husky.dialog.hide');
            unbindDialogListener();
        },
    // unbind the listeners of the dialog box
        unbindDialogListener = function() {
            sandbox.off('husky.dialog.submit', delSubmit);
            sandbox.off('husky.dialog.cancel', hideDialog);
        },

    // binds the listeners to the dialog box
        bindDialogListener = function() {
            sandbox.on('husky.dialog.submit', delSubmit);
            sandbox.on('husky.dialog.cancel', hideDialog);
        };

    return {
        name: 'Sulu Security Role',

        initialize: function() {
            sandbox = this.sandbox;

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
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

            sandbox.on('sulu.role.delete', function(id) {
                loading = 'delete';
                this.del(id);
            }.bind(this));

            sandbox.on('sulu.roles.delete', function(ids) {
                loading = 'add';
                this.del(ids);
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


            sandbox.emit('sulu.router.navigate', 'settings/roles/edit:' + id);
        },

        // saves the data, which is thrown together with a sulu.roles.save event
        save: function(data) {
            sandbox.emit('husky.header.button-state', 'loading-save-button');

            role.set(data);
            role.save(null, {
                success: function() {
                    sandbox.emit('husky.header.button-state', 'standard');
                },
                error: function() {
                    sandbox.emit('sulu.dialog.error.show', 'An error occured during saving the role!');
                    sandbox.emit('husky.header.button-state', 'standard');
                }
            });
        },

        // deletes the role with the id thrown with the sulu.role.delete event
        // id cann be an array of ids or one id
        del: function(id) {
            //save id to delete it with other callback
            idDelete = id;

            // show dialog and call delete only when user confirms
            // TODO improvement: sulu dialog with callback
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
            role = new Role();

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
                        sandbox.start([component]);
                    }
                });
            } else {
                sandbox.start([component]);
            }
        }
    };
});
