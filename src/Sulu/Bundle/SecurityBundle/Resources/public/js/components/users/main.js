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
    'sulucontact/models/contact',
    './collections/roles',
    './models/userRole',
    'widget-groups'
], function(User, Role, Contact, Roles, UserRole, WidgetGroups) {

    'use strict';

    return {

        name: 'Sulu Contact Permissions',

        layout: function() {
            return {
                content: {
                    width: (WidgetGroups.exists('contact-detail') ? 'max' : 'fixed')
                },
                sidebar: {
                    width: 'fixed',
                    cssClasses: 'sidebar-padding-50'
                }
            };
        },

        initialize: function() {
            this.renderForm();
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {

            this.sandbox.on('sulu.user.permissions.save', function(data, action) {
                this.save(data, action);
            }.bind(this));

            this.sandbox.on('sulu.user.activate', function() {
                this.enableUser();
            }.bind(this));
        },

        save: function(data) {
            this.user.set('username', data.user.username);
            this.user.set('contact', this.contact);
            this.user.set('locale', data.user.locale);
            this.user.set('email', (!!data.user.email) ? data.user.email : null);
            this.user.set('locked', !!data.user.locked);

            if (!!data.user.password && data.user.password !== '') {
                this.user.set('password', data.user.password);
            } else {
                this.user.set('password', '');
            }
            // prepare deselected roles
            this.sandbox.util.each(data.deselectedRoles, function(index, value) {
                var userRole;
                if (this.user.get('userRoles').length > 0) {

                    userRole = this.user.get('userRoles').findWhere({
                        role: this.roles.get(value)
                    });
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
                    tmp = this.user.get('userRoles').findWhere({
                        role: this.roles.get(value.roleId)
                    });
                    if (!!tmp) {
                        userRole = tmp;
                    }
                }

                userRole.set('role', this.roles.get(value.roleId));
                userRole.set('locales', value.selection);
                this.user.get('userRoles').add(userRole);
            }.bind(this));

            this.user.save(null, {
                global: false,
                success: function(model) {
                    this.sandbox.emit('sulu.user.permissions.saved', model.toJSON());
                }.bind(this),
                error: function(obj, resp) {
                    if (!!resp && !!resp.responseJSON && !!resp.responseJSON.message) {
                        this.sandbox.emit('sulu.labels.error.show',
                            this.gerErrorMessage(resp.responseJSON.code),
                            'labels.error',
                            ''
                        );
                        this.sandbox.emit('sulu.user.permissions.error', resp.responseJSON.code);
                    }
                }.bind(this)
            });
        },

        /**
         * Takes a code and returns a an error string
         * @param code
         */
        gerErrorMessage: function(code) {
            if (code === 1004) {
                return 'security.user.error.notUniqueEmail';
            }
            if (code === 1002) {
                return 'security.user.error.missingPassword';
            }
            if (code === 1001) {
                return 'security.user.error.notUnique';
            }
            return "";
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
            //TODO: fetch model (fetchUserByContactId)

            this.sandbox.util.load('/admin/api/users?contactId=' + this.options.id).then(function(data) {
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
                    name: 'users/components/form@sulusecurity',
                    options: {
                        el: $form,
                        data: data
                    }
                }
            ]);
        },

        enableUser: function() {
            var dfd = this.sandbox.data.deferred(),
                url = '/admin/api/users/' + this.user.id + '?action=enable';

            this.sandbox.util.save(url, 'POST', {})
                .then(function(response) {
                    this.sandbox.logger.log('successfully enabled user', response);
                    this.sandbox.emit('sulu.user.activated');
                    this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + this.user.attributes.contact.id + '/permissions', true, false, true);
                    dfd.resolve();
                }.bind(this))
                .fail(function() {
                    dfd.reject();
                }.bind(this));
            return dfd;
        }
    };
});
