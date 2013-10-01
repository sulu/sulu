/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalstore',
    './models/user',
    'sulusecurity/models/role',
    'sulusecurity/models/permission',
    'sulucontact/model/contact'], function(RelationalStore, User, Role, Permission, Contact) {

    'use strict';
    // TODO put inside return
    var sandbox,
        idDelete,

    // callback for deleting a role after confirming
        delSubmit = function() {
            sandbox.emit('husky.dialog.hide');
            sandbox.emit('husky.header.button-state', 'loading-delete-button');

            RelationalStore.reset();

            // delete

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
        };

    return {

        name: 'Sulu Contact Permissions',

        initialize: function() {
            sandbox = this.sandbox;

            if (this.options.display === 'form') {
                this.renderForm();
            }

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {

            this.sandbox.on('sulu.user.permissions.save', function(data) {
                this.save(data);
            }.bind(this));

            this.sandbox.on('sulu.user.permissions.delete', function(id) {
                this.del(id);
            }.bind(this));
        },

        // saves the data, which is thrown together with a sulu.roles.save event
//        save: function(data) {
//            this.sandbox.emit('husky.header.button-state', 'loading-save-button');
//
//            RelationalStore.reset();
//
//            // TODO save
//        },

        // deletes the role with the id thrown with the sulu.role.delete event
        del: function(id) {
            idDelete = id;

            // show dialog and call delete only when user confirms
            this.sandbox.emit('sulu.dialog.confirmation.show', {
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

        renderForm: function() {

            this.user = null;
            this.contact = null;

            if (!!this.options.id) {
                this.loadContactData(this.options.id);
            } else {
                // TODO error message
                sandbox.logger.log('error: form not accessible without contact id');
            }
        },

        loadContactData: function(id) {
            var contact = new Contact({id: id});
            contact.fetch({
                    success: function(contactModel) {
                        this.contact = contactModel;
                        this.loadUserRoles();
                    }.bind(this),
                    error: function(){
                        // TODO error message
                    }
            });

        },

        loadUserRoles: function() {

            var userId = 1,
                user;

            // TODO when security bundle is registered return also user with contact
            // userId = contact.get('user').get('id');

            if (!!userId) {

                user = new User({id: userId});
                user.url = '/security/api/users/'+userId+'/roles';
                user.fetch({
                    success: function(userModel) {
                        this.user = userModel;
                        this.startComponent();
                    }.bind(this),
                    error: function() {
                        // TODO error message
                    }
                });
            } else {
                // new user
                this.user = new User();
                this.startComponent();
            }

        },

        startComponent: function(){

            var data = {};
            data.contact = this.contact.toJSON();
            data.user = this.user.toJSON();

            this.sandbox.start([{
                name: 'permissions/components/form@sulusecurity',
                options: {
                    el: this.$el,
                    data: data
                }}
            ]);

        }

    };
});