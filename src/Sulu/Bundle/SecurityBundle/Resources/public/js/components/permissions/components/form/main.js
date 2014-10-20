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
    var setHeaderToolbar = function() {
        var toolbarItems = [
            {
                id: 'save-button',
                icon: 'floppy-o',
                iconSize: 'large',
                class: 'highlight',
                position: 1,
                group: 'left',
                disabled: true,
                callback: function () {
                    this.sandbox.emit('sulu.header.toolbar.save');
                }.bind(this)
            }
        ],
        configDropdown = {
            icon: 'gear',
            iconSize: 'large',
            group: 'left',
            id: 'options-button',
            position: 30,
            items: [
                {
                    title: this.sandbox.translate('toolbar.delete'),
                    callback: function () {
                        this.sandbox.emit('sulu.header.toolbar.delete');
                    }.bind(this)
                }
            ]
        },
        configItems = {
            confirm: {
                title: this.sandbox.translate('security.user.activate'),
                callback: function () {
                    this.sandbox.emit('sulu.user.activate');
                }.bind(this)
            }
        };

        if (!this.user.enabled) {
            configDropdown.items.push(configItems.confirm);
        }

        // add workflow items
        if (configDropdown.items.length > 0) {
            toolbarItems.push(configDropdown);
        }

        this.sandbox.emit('sulu.header.set-toolbar', {
            template: toolbarItems
        });
    };

    return {

        name: 'Sulu Security Permissions Form',

        layout: {
            sidebar: {
                width: 'fixed',
                cssClasses: 'sidebar-padding-50'
            }
        },

        templates: ['/admin/security/template/permission/form'],

        view: true,

        initialize: function() {
            this.saved = true;
            this.formId = '#permissions-form';
            this.selectedRoles = [];
            this.deselectedRoles = [];
            this.systemLanguage = null;

            if (!!this.options.data) {
                this.user = this.options.data.user;
                this.contact = this.options.data.contact;
                this.roles = this.options.data.roles;
            } else {
                // TODO error message
                this.sandbox.logger.log("no data given");
            }

            this.setTitle();
            this.render();
            this.initializeRoles();

            this.bindDOMEvents();
            this.bindCustomEvents();

            this.initializeHeaderbar();

            this.sandbox.form.create(this.formId);

            if (!!this.options.data.contact && !!this.options.data.contact.id) {
                this.initSidebar(
                    '/admin/widget-groups/contact-detail?contact=',
                    this.options.data.contact.id
                );
            }
        },

        initSidebar: function(url, id) {
            this.sandbox.emit('sulu.sidebar.set-widget', url + id);
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
                this.sandbox.emit('sulu.header.toolbar.state.change', type, saved, true);
            }
            this.saved = saved;
        },

        listenForChange: function() {

            this.sandbox.dom.on(this.formId, 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), '.changeListener select, ' +
                '.changeListener input, ' +
                '.changeListener textarea');

            this.sandbox.dom.on(this.formId, 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), '.changeListener select, ' +
            '.changeListener input, ' +
            '.changeListener textarea');

            this.sandbox.on('husky.select.systemLanguage.selected.item', function() {
                this.setHeaderBar(false);
            }.bind(this));

            this.sandbox.util.each(this.roles, function(index, value) {
                this.sandbox.on('husky.select.languageSelector' + value.id + '.selected.item', function() {
                    this.setHeaderBar(false);
                }, this);
                this.sandbox.on('husky.select.languageSelector' + value.id + '.deselected.item', function() {
                    this.setHeaderBar(false);
                }, this);
            }.bind(this));

        },

        /**
         * Sets the title to the username
         * default title as fallback
         */
        setTitle: function() {
            var title = this.sandbox.translate('contact.contacts.title'),
                breadcrumb = [
                    {title: 'navigation.contacts'},
                    {title: 'contact.contacts.title', event: 'sulu.contacts.contacts.list'}
                ];

            if (!!this.options.data.contact && !!this.options.data.contact.id) {
                title = this.options.data.contact.fullName;
                breadcrumb.push({title: '#' + this.options.data.contact.id});
            }

            this.sandbox.emit('sulu.header.set-title', title);
            this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
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
            this.startLanguageDropdown();

            setHeaderToolbar.call(this);
        },

        /**
         * Starts the dropdown for the system language
         */
        startLanguageDropdown: function() {
            this.systemLanguage = this.options.data.user.locale;

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('#systemLanguage', this.$el),
                        instanceName: 'systemLanguage',
                        defaultLabel: this.sandbox.translate('security.permission.role.chooseLanguage'),
                        value: 'name',
                        data: [
                            {id: "de", name: "Deutsch"},
                            {id: "en", name: "English"}
                        ],
                        preSelectedElements: [this.systemLanguage]
                    }
                }
            ]);
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
                    roleId = this.sandbox.dom.data(this.sandbox.dom.parent(this.sandbox.dom.parent(this.sandbox.dom.parent(value))), 'id');

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

            var roleId = this.sandbox.dom.data(this.sandbox.dom.parent(this.sandbox.dom.parent(this.sandbox.dom.parent($element))), 'id'),
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
            this.sandbox.on('sulu.user.permissions.error', function(code) {
                switch (code) {
                    case 1001:
                        var $wrapper = this.sandbox.dom.parent(this.sandbox.dom.find('#username'));
                        this.sandbox.dom.prependClass($wrapper, 'husky-validate-error');
                        this.setHeaderBar(true);
                        break;
                    case 1002:
                        var $wrapperPasswordRepeat = this.sandbox.dom.parent(this.sandbox.dom.find('#passwordRepeat')),
                            $wrapperPassword = this.sandbox.dom.parent(this.sandbox.dom.find('#password'));
                        this.sandbox.dom.prependClass($wrapperPassword, 'husky-validate-error');
                        this.sandbox.dom.prependClass($wrapperPasswordRepeat, 'husky-validate-error');

                        this.setHeaderBar(true);
                        break;
                    default:
                        this.sandbox.logger.warn('Unrecognized error code!', code);
                        break;
                }
            }.bind(this));

            // delete contact
            this.sandbox.on('sulu.header.toolbar.delete', function() {
                this.sandbox.emit('sulu.user.permissions.delete', this.contact.id);
            }, this);

            this.sandbox.on('sulu.user.permissions.saved', function(model) {
                this.user = model;

                this.setHeaderBar(true);
            }, this);

            this.sandbox.on('sulu.header.toolbar.save', function() {
                this.save();
            }, this);

            this.sandbox.on('sulu.header.back', function(){
                this.sandbox.emit('sulu.contacts.contacts.list');
            }, this);

            // update systemLanguage on dropdown select
            this.sandbox.on('husky.select.systemLanguage.selected.item', function(locale) {
                this.systemLanguage = locale;
            }.bind(this));
        },

        save: function() {
            // FIXME  Use datamapper instead

            var data;

            if (this.sandbox.form.validate(this.formId)) {

                this.sandbox.logger.log('validation succeeded');

                data = {
                    user: {
                        username: this.sandbox.dom.val('#username'),
                        contact: this.contact,
                        locale: this.systemLanguage
                    },

                    selectedRolesAndConfig: this.getSelectedRolesAndLanguages(),
                    deselectedRoles: this.deselectedRoles
                };

                this.password = this.sandbox.dom.val('#password');

                if (!!this.user && !!this.user.id) {
                    data.user.id = this.user.id;
                }

                if (!!this.password && this.password !== '') {
                    data.user.password = this.password;
                }

                this.sandbox.emit('sulu.user.permissions.save', data);
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

                this.sandbox.emit('husky.select.languageSelector' + value + '.get-checked', function(selected) {
                    config.selection = selected;
                });

                data.push(config);

            }.bind(this));

            return data;

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
                            multipleSelect: true,
                            defaultLabel: this.sandbox.translate('security.permission.role.chooseLanguage'),
                            checkedAllLabel: this.sandbox.translate('security.permission.role.allLanguages'),
                            value: 'name',
                            data: ["Deutsch", "English", "Espanol", "Italiano"],
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
                    '   <tr>',
                    '       <th width="5%">',
                    '           <div class="custom-checkbox">',
                    '               <input id="selectAll" type="checkbox"/>',
                    '               <span class="icon"></span>',
                    '           </div>',
                    '       </th>',
                    '       <th width="30%">', thLabel1, '</th>',
                    '       <th width="45%">', thLabel2, '</th>',
                    '       <th width="20%">', thLabel3, '</th>',
                    '   </tr>',
                    '</thead>'
                ].join('');

            },
            tableRow: function(id, title, selected) {

                var $row;

                if (!!selected) {
                    $row = [
                        '<tr data-id=\"', id, '\">',
                        '   <td>',
                        '       <div class="custom-checkbox">',
                        '           <input type="checkbox" class="is-selected" checked/>',
                        '           <span class="icon"></span>',
                        '       </div>',
                        '   </td>',
                        '   <td>', title, '</td>',
                        '   <td class="m-top-15" id="languageSelector', id, '"></td>',
                        '   <td></td>',
                        '</tr>'
                    ].join('');
                } else {
                    $row = [
                        '<tr data-id=\"', id, '\">',
                        '   <td>',
                        '       <div class="custom-checkbox">',
                        '           <input type="checkbox"/>',
                        '           <span class="icon"></span>',
                        '       </div>',
                        '   </td>',
                        '   <td>', title, '</td>',
                        '   <td class="m-top-15" id="languageSelector', id, '"></td>',
                        '   <td></td>',
                        '</tr>'
                    ].join('');
                }

                return $row;
            }
        }
    };
});
