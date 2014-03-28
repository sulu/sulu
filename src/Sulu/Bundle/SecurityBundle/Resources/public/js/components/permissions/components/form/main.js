/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    // TODO field for selection of locale of user - currently default value of backbone model

    return {

        name: 'Sulu Security Permissions Form',

        templates: ['/admin/security/template/permission/form'],

        view: true,

        initialize: function() {
            this.saved = true;
            this.formId = '#permissions-form';
            this.selectedRoles = [];
            this.deselectedRoles = [];

            this.passwordField1Id = '#husky-password-fields-instance1-password1';
            this.passwordField2Id = '#husky-password-fields-instance1-password2';

            if (!!this.options.data) {
                this.user = this.options.data.user;
                this.contact = this.options.data.contact;
                this.roles = this.options.data.roles;
            } else {
                // TODO error message
                this.sandbox.logger.log("no data given");
            }

            this.render();
            this.initializePasswordFields();
            this.initializeRoles();

            this.bindDOMEvents();
            this.bindCustomEvents();

            this.initializeHeaderbar();

            this.sandbox.form.create(this.formId);
        },

        addConstraintsToPasswordFields: function() {
            // TODO FIXME
            setTimeout(function() {
                this.sandbox.form.addConstraint(this.formId, this.passwordField1Id, 'required', {required: true});
                this.sandbox.form.addConstraint(this.formId, this.passwordField2Id, 'required', {required: true});
            }.bind(this), 10);
        },

        // Headerbar

        initializeHeaderbar: function() {
            this.currentType = '';
            this.currentState = '';

            this.setHeaderBar(true);
            this.listenForChange();
        },

        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                var type = (!!this.options.data && !!this.options.data.id) ? 'edit' : 'add';
                this.sandbox.emit('sulu.edit-toolbar.content.state.change', type, saved, true);
            }
            this.saved = saved;
        },

        listenForChange: function() {

            this.sandbox.dom.on(this.formId, 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), 'select, input');

            this.sandbox.dom.on(this.formId, 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), 'input');

            this.sandbox.util.each(this.roles, function(index, value) {
                this.sandbox.on('husky.select.languageSelector' + value.id + '.selected.item', function() {
                    this.setHeaderBar(false);
                }, this);
                this.sandbox.on('husky.select.languageSelector' + value.id + '.deselected.item', function() {
                    this.setHeaderBar(false);
                }, this);
            }.bind(this));

        },

        // Form

        render: function() {
            var email = "",
                headline;
            if (!!this.contact.emails && this.contact.emails.length > 0) {
                email = this.contact.emails[0].email;
            }

            headline = this.contact ? this.contact.firstName + ' ' + this.contact.lastName : this.sandbox.translate('security.permission.title');
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/security/template/permission/form', {user: !!this.user ? this.user : null, email: email, headline: headline}));
        },

        initializePasswordFields: function() {

            this.sandbox.start([
                {
                    name: 'password-fields@husky',
                    options: {
                        instanceName: "instance1",
                        el: '#password-component',
                        labels: {
                            inputPassword1: this.sandbox.translate('security.permission.password'),
                            inputPassword2: this.sandbox.translate('security.permission.passwordRepeat'),
                            generateLabel: this.sandbox.translate('security.permission.generatePassword')
                        },
                        validation: this.formId
                    }
                }
            ]);

            // set timeout
            if (!this.user || !this.user.id) {
                this.addConstraintsToPasswordFields();
            }
        },

        bindDOMEvents: function() {

            this.sandbox.dom.on('#rolesTable', 'click', function(event) {
                var id = this.sandbox.dom.attr(event.currentTarget, 'id');
                if (id === 'selectAll') {
                    this.selectAll(event.currentTarget);
                } else {
                    this.selectItem(event.currentTarget);
                }
            }.bind(this), 'input[type="checkbox"]');
        },

        selectAll: function(checkbox) {

            var $checkboxes = this.sandbox.dom.find('tr td:first-child() input[type="checkbox"]', '#rolesTable'),
                roleId;

            if (this.selectedRoles.length === this.roles.length) {

                this.sandbox.dom.removeClass(checkbox, 'is-selected');
                this.sandbox.dom.prop(checkbox, 'checked', false);

                this.sandbox.util.each($checkboxes, function(index, value) {
                    this.sandbox.dom.removeClass(value, 'is-selected');
                    this.sandbox.dom.prop(value, 'checked', false);
                }.bind(this));
                this.selectedRoles = [];
                this.sandbox.logger.log(this.selectedRoles, 'selected roles');
            } else {

                this.sandbox.dom.addClass(checkbox, 'is-selected');
                this.sandbox.dom.prop(checkbox, 'checked', true);

                this.sandbox.util.each($checkboxes, function(index, value) {
                    roleId = this.sandbox.dom.data(this.sandbox.dom.parent(this.sandbox.dom.parent(value)), 'id');

                    if (this.selectedRoles.indexOf(roleId) < 0) {
                        this.selectedRoles.push(roleId);
                    }

                    this.sandbox.dom.addClass(value, 'is-selected');
                    this.sandbox.dom.prop(value, 'checked', true);

                }.bind(this));

                this.sandbox.logger.log(this.selectedRoles, 'selected roles');
            }
        },

        selectItem: function($element) {

            var roleId = this.sandbox.dom.data(this.sandbox.dom.parent(this.sandbox.dom.parent($element)), 'id'),
                index = this.selectedRoles.indexOf(roleId);

            if (index >= 0) {
                this.sandbox.dom.removeClass($element, 'is-selected');
                this.sandbox.dom.prop($element, 'checked', false);
                this.selectedRoles.splice(index, 1);

                if (this.deselectedRoles.indexOf(roleId) < 0) {
                    this.deselectedRoles.push(roleId);
                }

                this.sandbox.logger.log(roleId, "role deselected");
            } else {
                this.sandbox.dom.addClass($element, 'is-selected');
                this.sandbox.dom.prop($element, 'checked', true);
                this.selectedRoles.push(roleId);

                this.sandbox.logger.log(roleId, "role selected");

            }

        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('sulu.edit-toolbar.delete', function() {
                this.sandbox.emit('sulu.user.permissions.delete', this.contact.id);
            }, this);

            this.sandbox.on('sulu.user.permissions.saved', function(model) {
                this.user = model;

                if (!!this.user.id && !!this.sandbox.form.element.hasConstraint(this.passwordField1Id, 'required')) {
                    this.sandbox.form.deleteConstraint(this.formId, this.passwordField1Id, 'required');
                    this.sandbox.form.deleteConstraint(this.formId, this.passwordField2Id, 'required');
                }

                this.setHeaderBar(true);
            }, this);

            this.sandbox.on('sulu.edit-toolbar.save', function() {
                this.save();
            }, this);

            this.sandbox.on('sulu.edit-toolbar.back', function(){
                this.sandbox.emit('sulu.contacts.contacts.list');
            }, this);
        },

        save: function() {
            // FIXME  Use datamapper instead

            var data;

            this.getPassword();

            if (this.sandbox.form.validate(this.formId)) {

                this.sandbox.logger.log('validation succeeded');

                data = {
                    user: {
                        username: this.sandbox.dom.val('#username'),
                        contact: this.contact,
                        locale: this.sandbox.dom.val('#locale')
                    },

                    selectedRolesAndConfig: this.getSelectedRolesAndLanguages(),
                    deselectedRoles: this.deselectedRoles
                };

                if (!!this.user && !!this.user.id) {
                    data.user.id = this.user.id;
                }

                if (!!this.password && this.password !== '') {
                    data.user.password = this.password;
                }

                this.sandbox.emit('sulu.user.permissions.save', data);
            }
        },

        isValidPassword: function() {
            if (!!this.user && !!this.user.id) { // existion user - does not have to set password
                return true;
            } else { // new user - should set password at least once and it should not be empty
                return !!this.password && this.password !== '';
            }
        },

        getSelectedRolesAndLanguages: function() {
            var $tr,
                data = [],
                config;

            this.sandbox.util.each(this.selectedRoles, function(index, value) {
                $tr = this.sandbox.dom.find('#languageSelector' + value);

                config = {};
                config.roleId = value;

                this.sandbox.emit('husky.select.languageSelector' + value + '.getChecked', function(selected) {
                    config.selection = selected;
                });

                data.push(config);

            }.bind(this));

            return data;

        },

        getPassword: function() {
            this.sandbox.emit('husky.password.fields.instance1.get.passwords', function(password1) {
                this.password = password1;
            }.bind(this));
        },

        // Grid with roles and permissions

        initializeRoles: function() {

            this.getSelectRolesOfUser();

            var $permissionsContainer = this.sandbox.dom.$('#permissions-grid'),
                $table = this.sandbox.dom.createElement('<table/>', {class: 'table matrix', id: 'rolesTable'}),
                $tableHeader = this.prepareTableHeader(),
                $tableContent = this.prepareTableContent(),
                $tmp = this.sandbox.dom.append($table, $tableHeader),
                rows;

            $tmp = this.sandbox.dom.append($tmp, $tableContent);
            this.sandbox.dom.html($permissionsContainer, $tmp);

            rows = this.sandbox.dom.find('tbody tr', '#rolesTable');

            // TODO get elements for dropdown from portal

            this.sandbox.util.each(rows, function(index, value) {
                var id = this.sandbox.dom.data(value, 'id'),
                    preSelectedValues = this.getUserRoleLocalesWithRoleId(id);

                this.sandbox.start([
                    {
                        name: 'select@husky',
                        options: {
                            el: '#languageSelector' + id,
                            instanceName: 'languageSelector' + id,
                            defaultLabel: this.sandbox.translate('security.permission.role.chooseLanguage'),
                            checkedAllLabel: this.sandbox.translate('security.permission.role.allLanguages'),
                            value: 'name',
                            data: ["Deutsch", "English", "Spanish", "Italienisch"],
                            preSelectedElements: preSelectedValues
                        }
                    }
                ]);
            }.bind(this));
        },

        getUserRoleLocalesWithRoleId: function(id) {

            var locales;
            if (!!this.user && this.user.userRoles) {
                this.sandbox.util.each(this.user.userRoles, function(index, value) {
                    if (value.role.id === id) {
                        locales = value.locales;
                        return false;
                    }
                }.bind(this));
            }

            if (!!locales) {
                return locales;
            } else {
                return [];
            }
        },

        getSelectRolesOfUser: function() {
            if (!!this.user && !!this.user.userRoles) {
                this.sandbox.util.each(this.user.userRoles, function(index, value) {
                    this.selectedRoles.push(value.role.id);
                }.bind(this));
            }
        },

        prepareTableHeader: function() {
            return this.template.tableHead(
                this.sandbox.translate('security.permission.role.title'),
                this.sandbox.translate('security.permission.role.language'),
                this.sandbox.translate('security.permission.role.permissions')
            );
        },

        prepareTableContent: function() {
            var $tableBody = this.sandbox.dom.createElement('<tbody/>'),
                tableContent = [];

            this.sandbox.util.each(this.roles, function(index, value) {
                tableContent.push(this.prepareTableRow(value));
            }.bind(this));

            return this.sandbox.dom.append($tableBody, tableContent);
        },


        prepareTableRow: function(role) {

            var $tableRow;

            if (this.selectedRoles.indexOf(role.id) >= 0) {
                $tableRow = this.template.tableRow(role.id, role.name, true);
            } else {
                $tableRow = this.template.tableRow(role.id, role.name, false);
            }

            return $tableRow;
        },

        template: {
            tableHead: function(thLabel1, thLabel2, thLabel3) {
                return [
                    '<thead>',
                        '<tr>' +
                            '<th width="5%"><input id="selectAll" type="checkbox" class="custom-checkbox"/><span class="custom-checkbox-icon"></span></th>',
                            '<th width="30%">', thLabel1, '</th>',
                            '<th width="45%">', thLabel2, '</th>',
                            '<th width="20%">', thLabel3, '</th>',
                        '</tr>',
                    '</thead>'
                ].join('');

            },
            tableRow: function(id, title, selected) {

                var $row;

                if (!!selected) {
                    $row = [
                        '<tr data-id=\"', id, '\">',
                            '<td><input type="checkbox" class="custom-checkbox is-selected" checked/><span class="custom-checkbox-icon"></span></td>',
                            '<td>', title, '</td>',
                            '<td class="m-top-15" id="languageSelector', id, '"></td>',
                            '<td></td>',
                        '</tr>'
                    ].join('');
                } else {
                    $row = [
                        '<tr data-id=\"', id, '\">',
                            '<td><input type="checkbox" class="custom-checkbox"/><span class="custom-checkbox-icon"></span></td>',
                            '<td>', title, '</td>',
                            '<td class="m-top-15" id="languageSelector', id, '"></td>',
                            '<td></td>',
                        '</tr>'
                    ].join('');
                }

                return $row;
            }
        }
    };
});
