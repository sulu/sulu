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
    'sulucontact/model/contact',
    './collections/roles'
], function(RelationalStore, User, Role, Permission, Contact, Roles) {

    'use strict';

    return {

        name: 'Sulu Contact Permissions',

        initialize: function() {

            if (this.options.display === 'form') {
                this.renderForm();
            }

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {

            this.sandbox.on('sulu.user.permissions.save', function(data) {
                this.save(data);
            }.bind(this));

            // delete contact
            this.sandbox.on('sulu.user.permissions.delete', function(id) {
                this.confirmDeleteDialog(function(wasConfirmed) {
                    if (wasConfirmed) {
                        var contactModel = Contact.findOrCreate({id: id});

                        this.sandbox.emit('husky.header.button-state', 'loading-save-button');
                        contactModel.destroy({
                            success: function() {
                                this.sandbox.emit('sulu.router.navigate', 'contacts/contacts');
                            }.bind(this)

                        });
                    }
                }.bind(this));
            }, this);
        },

        save: function(data) {
            this.sandbox.emit('husky.header.button-state', 'loading-save-button');

            var userModel = User.findOrCreate(data.user);

            if(!!data.user.id) { // PUT
                userModel.url = '/security/api/users/'+data.user.id;
            } else { // POST
                userModel.url = '/security/api/user';
            }

            // TODO roles and config

            userModel.save(null, {
                success: function() {
                    this.sandbox.emit('sulu.router.navigate', '/contacts/contacts');
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)

            });

        },




        // render form and load data

        renderForm: function() {
            RelationalStore.reset();
            this.user = null;
            this.contact = null;

            if (!!this.options.id) {
                this.loadContactData();
            } else {
                // TODO error message
                this.sandbox.logger.log('error: form not accessible without contact id');
            }
        },

        loadContactData: function() {
            var contact = Contact.findOrCreate({id: this.options.id});
            contact.fetch({
                success: function(contactModel) {
                    this.contact = contactModel;
                    this.loadRoles();
                }.bind(this),
                error: function() {
                    // TODO error message
                }
            });

        },

        loadRoles: function() {
            var roles = new Roles();
            roles.fetch({
                success: function(rolesCollection) {
                    this.roles = rolesCollection;
                    this.loadUser();
                }.bind(this),
                error: function() {
                    // TODO error message
                }
            });

        },

        loadUser: function() {

            var user = new User();
            user.url = '/security/api/users?contactId=' + this.options.id;
            user.fetch({
                success: function(userModel) {
                    this.user = userModel;
                    this.startComponent();
                }.bind(this),
                error: function() {
                    // TODO check status code
                    this.startComponent();
                }.bind(this)
            });
        },

        startComponent: function() {

            var data = {};
            data.contact = this.contact.toJSON();

            if(!!this.user) {
                data.user = this.user.toJSON();
            }

            data.roles = this.roles.toJSON();

            this.sandbox.start([
                {
                    name: 'permissions/components/form@sulusecurity',
                    options: {
                        el: this.$el,
                        data: data
                    }}
            ]);

        },




        // dialog

        /**
         * @var ids - array of ids to delete
         * @var callback - callback function returns true or false if data got deleted
         */
        confirmDeleteDialog: function(callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            // show dialog
            this.sandbox.emit('sulu.dialog.confirmation.show', {
                content: {
                    title: "Be careful!",
                    content: "<p>The operation you are about to do will delete data.<br/>This is not undoable!</p><p>Please think about it and accept or decline.</p>"
                },
                footer: {
                    buttonCancelText: "Don't do it",
                    buttonSubmitText: "Do it, I understand"
                }
            });

            // submit -> delete
            this.sandbox.once('husky.dialog.submit', function() {
                this.sandbox.emit('husky.dialog.hide');
                if (!!callbackFunction) {
                    callbackFunction.call(this, true);
                }
            }.bind(this));

            // cancel
            this.sandbox.once('husky.dialog.cancel', function() {
                this.sandbox.emit('husky.dialog.hide');
                if (!!callbackFunction) {
                    callbackFunction.call(this, false);
                }
            }.bind(this));
        }

    };
});
