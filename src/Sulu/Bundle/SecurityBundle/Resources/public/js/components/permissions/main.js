/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    './models/user',
    'sulusecurity/models/role',
    'sulusecurity/models/permission',
    'sulucontact/model/contact',
    './collections/roles',
    './models/userRole'
], function(User, Role, Permission, Contact, Roles, UserRole) {

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

            // load list view
            this.sandbox.on('sulu.contacts.contacts.list', function() {
                this.sandbox.emit('sulu.router.navigate', 'contacts/contacts');
            }, this);

            // delete contact
            this.sandbox.on('sulu.user.permissions.delete', function(id) {
                this.confirmDeleteDialog(function(wasConfirmed) {
                    if (wasConfirmed) {
                        var contactModel = Contact.findOrCreate({id: id});

                        this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
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
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            this.user.set('username', data.user.username);
            this.user.set('contact', this.contact);
            this.user.set('locale', data.user.locale);

            if (!!data.user.password && data.user.password !== '') {
                this.user.set('password', data.user.password);
            } else {
                this.user.set('password', '');
            }

            if (!!data.user.id) { // PUT
                this.user.url = '/admin/api/users/' + data.user.id;
            } else { // POST
                this.user.url = '/admin/api/users';
            }

            // prepare deselected roles
            this.sandbox.util.each(data.deselectedRoles, function(index, value) {
                var userRole;

                if (this.user.get('userRoles').length > 0) {

                    userRole = this.user.get('userRoles').findWhere(
                        {
                            role: this.roles.get(value)
                        }
                    );
                    if (!!userRole) {
                        this.user.get('userRoles').remove(userRole);
                    }
                }

            }.bind(this));

            // prepare selected roles
            this.sandbox.util.each(data.selectedRolesAndConfig, function(index, value) {
                var userRole = new UserRole(),
                    tmp;

                if (this.user.get('userRoles').length > 0) {

                    tmp = this.user.get('userRoles').findWhere(
                        {
                            role: this.roles.get(value.roleId)
                        }
                    );
                    if (!!tmp) {
                        userRole = tmp;
                    }
                }

                userRole.set('role', this.roles.get(value.roleId));
                userRole.set('locales', value.selection);
                this.user.get('userRoles').add(userRole);
            }.bind(this));

            this.user.save(null, {
                success: function(model) {
                    this.sandbox.emit('sulu.user.permissions.saved', model.toJSON());
                }.bind(this),
                error: function(obj, resp) {
                    if (!!resp && !!resp.responseJSON && !!resp.responseJSON.message) {
                        this.sandbox.emit('sulu.labels.error.show',
                            resp.responseJSON.message,
                            'labels.error',
                            ''
                        );
                        this.sandbox.emit('sulu.user.permissions.error', resp.responseJSON.code);
                    }
                }.bind(this)
            });
        },

        // render form and load data

        renderForm: function() {

            this.user = null;
            this.contact = null;

            if (!!this.options.id) {
                this.loadRoles();
            } else {
                // TODO error message
                this.sandbox.logger.log('error: form not accessible without contact id');
            }
        },

        loadRoles: function() {
            this.roles = new Roles();
            this.roles.fetch({
                success: function() {
                    this.loadUser();
                }.bind(this),
                error: function() {
                    // TODO error message
                }
            });

        },

        loadUser: function() {
            this.user = new User();
            this.user.url = '/admin/api/users?contactId=' + this.options.id;

            this.sandbox.util.load(this.user.url).then(function(data) {
                if (!!data && !!data._embedded && !!data._embedded.users && data._embedded.users.length > 0) {
                    this.user.set(data._embedded.users[0]);
                    this.contact = this.user.get('contact').toJSON();
                    this.startComponent();
                } else {
                    this.loadContact();
                }
            }.bind(this));
        },

        loadContact: function() {
            this.contact = new Contact({id: this.options.id});
            this.contact.fetch({
                success: function() {
                    this.contact = this.contact.toJSON();
                    this.startComponent();
                }.bind(this),
                error: function() {
                    // TODO error message
                    this.sandbox.logger.log("failed to load contact");
                }.bind(this)
            });
        },

        startComponent: function() {
            var data = {},
                $form = $('<div id="roles-form-container"/>');
            data.contact = this.contact;

            if (!!this.user) {
                data.user = this.user.toJSON();
            }

            data.roles = this.roles.toJSON();

            this.html($form);
            this.sandbox.start([
                {
                    name: 'permissions/components/form@sulusecurity',
                    options: {
                        el: $form,
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

            // show warning dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'sulu.overlay.delete-desc',

                function() {
                    // cancel callback
                    callbackFunction(false);
                }.bind(this),

                function() {
                    // ok callback
                    callbackFunction(true);
                }.bind(this)
            );
        }
    };
});
