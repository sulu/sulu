/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config', 'widget-groups'], function(AppConfig, WidgetGroups) {

    'use strict';

    var constants = {
            localizationsUrl: '/admin/api/localizations',
            localizationConnector: '-',
            permissionGridId: '#permissions-grid',
            permissionLoaderClass: '.permission-loader'
        };

    return {

        name: 'Sulu Security Permissions Form',

        templates: ['/admin/security/template/permission/form'],

        initialize: function() {
            this.formId = '#permissions-form';
            this.selectedRoles = [];
            this.deselectedRoles = [];
            this.systemLanguage = null;

            if (!!this.options.data) {
                this.user = this.options.data.user;
                this.contact = this.options.data.contact;
                this.roles = this.options.data.roles;
            } else {
                // TODO error-message
                this.sandbox.logger.log("no data given");
            }

            this.render();
            this.listenForChange();
            this.initializeRoles();
            this.bindCustomEvents();
            this.sandbox.form.create(this.formId);

            if (!!this.options.data.contact && !!this.options.data.contact.id && WidgetGroups.exists('contact-detail')) {
                this.initSidebar(
                    '/admin/widget-groups/contact-detail?contact=',
                    this.options.data.contact.id
                );
            }
        },

        initSidebar: function(url, id) {
            this.sandbox.emit('sulu.sidebar.set-widget', url + id);
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.formId, 'change', function() {
                this.sandbox.emit('sulu.tab.dirty');
            }.bind(this), '.changeListener select, ' +
                '.changeListener input, ' +
                '.changeListener textarea');

            this.sandbox.dom.on(this.formId, 'keyup', function() {
                this.sandbox.emit('sulu.tab.dirty');
            }.bind(this), '.changeListener select, ' +
            '.changeListener input, ' +
            '.changeListener textarea');

            this.sandbox.on('husky.select.systemLanguage.selected.item', function() {
                this.sandbox.emit('sulu.tab.dirty');
            }.bind(this));

            this.sandbox.util.each(this.roles, function(index, value) {
                this.sandbox.on('husky.select.languageSelector' + value.id + '.selected.item', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }, this);
                this.sandbox.on('husky.select.languageSelector' + value.id + '.deselected.item', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }, this);
            }.bind(this));

        },

        render: function() {
            var headline = this.contact ? this.contact.firstName + ' ' + this.contact.lastName : this.sandbox.translate('security.permission.title');

            this.sandbox.emit('husky.toggler.sulu-toolbar.change', this.user.locked);
            this.sandbox.emit('sulu.header.toolbar.item.show', 'disabler');
            if (this.user.enabled === false) {
                this.sandbox.emit('sulu.header.toolbar.item.show', 'enable');
            }
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/security/template/permission/form', {
                user: !!this.user ? this.user : null,
                headline: headline
            }));
            this.sandbox.start(this.$el);
            this.startLanguageDropdown();
        },

        destroy: function() {
            this.sandbox.emit('sulu.header.toolbar.item.hide', 'disabler');
            this.sandbox.emit('sulu.header.toolbar.item.hide', 'enable');
        },

        /**
         * Starts the dropdown for the system language
         */
        startLanguageDropdown: function() {
            var translatedLocales = AppConfig.getLocales(true),
                data = [],
                key;

            for (key in translatedLocales) {
                if (translatedLocales.hasOwnProperty(key)) {
                    data.push({id: key, name: translatedLocales[key]});
                }
            }

            this.systemLanguage = this.user.locale;

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('#systemLanguage', this.$el),
                        instanceName: 'systemLanguage',
                        defaultLabel: this.sandbox.translate('security.permission.role.chooseLanguage'),
                        value: 'name',
                        data: data,
                        preSelectedElements: [this.systemLanguage]
                    }
                }
            ]);
        },

        bindRoleTableEvents: function() {
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
            }
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.user.permissions.error', function(code) {
                switch (code) {
                    case 1001:
                        var $wrapper = this.sandbox.dom.parent(this.sandbox.dom.find('#username'));
                        this.sandbox.dom.prependClass($wrapper, 'husky-validate-error');
                        break;
                    case 1002:
                        var $wrapperPasswordRepeat = this.sandbox.dom.parent(this.sandbox.dom.find('#passwordRepeat')),
                            $wrapperPassword = this.sandbox.dom.parent(this.sandbox.dom.find('#password'));
                        this.sandbox.dom.prependClass($wrapperPassword, 'husky-validate-error');
                        this.sandbox.dom.prependClass($wrapperPasswordRepeat, 'husky-validate-error');
                        break;
                    default:
                        this.sandbox.logger.warn('Unrecognized error code!', code);
                        break;
                }
            }.bind(this));

            this.sandbox.on('sulu.tab.save', this.save, this);

            // update systemLanguage on dropdown select
            this.sandbox.on('husky.select.systemLanguage.selected.item', function(locale) {
                this.systemLanguage = locale;
            }.bind(this));

            this.sandbox.on('husky.toggler.sulu-toolbar.changed', function(value) {
                this.user.locked = value;
                this.sandbox.emit('sulu.tab.dirty');
            }.bind(this));

            this.sandbox.on('husky.toolbar.header.item.select', function(object) {
                if (object.id === 'enable') {
                    this.sandbox.emit('sulu.user.activate');
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'enable');
                }
            }.bind(this));

            this.sandbox.on('sulu.user.activated', function() {
                this.sandbox.emit('sulu.header.toolbar.item.hide', 'enable', true);
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.user.activated');
            }.bind(this))
        },

        save: function(action) {
            // FIXME  Use datamapper instead
            var data;
            if (this.sandbox.form.validate(this.formId)) {
                data = {
                    user: {
                        username: this.sandbox.dom.val('#username'),
                        email: this.sandbox.dom.val('#email'),
                        contact: this.contact,
                        locale: this.systemLanguage,
                        locked: this.user.locked,
                        enabled: this.user.enabled
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
                this.sandbox.emit('sulu.tab.saving');
                this.sandbox.emit('sulu.user.permissions.save', data, action);
                this.sandbox.once('sulu.user.permissions.saved', function(model) {
                    this.user = model;
                    this.sandbox.emit('sulu.tab.saved');
                    this.sandbox.emit('sulu.labels.warning.show', 'security.warning');
                }.bind(this));
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
            var $container = this.sandbox.dom.createElement('<div class="' + constants.permissionLoaderClass + '"/>');
            this.sandbox.dom.append(this.$find(constants.permissionGridId), $container);
            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: $container,
                        size: '100px',
                        color: '#e4e4e4'
                    }
                }
            ]);

            this.sandbox.util.load(constants.localizationsUrl, null, 'json')
                .then(function(response) {
                    return response._embedded.localizations;
                })
                .then(this.renderRoles.bind(this));
        },

        renderRoles: function (localizations) {
            this.getSelectRolesOfUser();

            var $permissionsContainer = this.sandbox.dom.$(constants.permissionGridId),
                $table = this.sandbox.dom.createElement('<table/>', {class: 'table matrix', id: 'rolesTable'}),
                $tableHeader = this.prepareTableHeader(),
                $tableContent = this.prepareTableContent(),
                $tmp = this.sandbox.dom.append($table, $tableHeader);

            $tmp = this.sandbox.dom.append($tmp, $tableContent);
            this.sandbox.dom.html($permissionsContainer, $tmp);

            var rows = this.sandbox.dom.find('tbody tr', '#rolesTable');

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
                            dropdownPadding: 50,
                            data: localizations.map(function (localization) {
                                return {
                                    id: localization.localization,
                                    name: localization.localization
                                };
                            }),
                            preSelectedElements: preSelectedValues
                        }
                    }
                ]);

                this.sandbox.stop(this.$find(constants.permissionLoaderClass));
            }.bind(this));

            this.bindRoleTableEvents();
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
                this.sandbox.translate('security.permission.role.language')
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
            tableHead: function(thLabel1, thLabel2) {
                return [
                    '<thead>',
                    '   <tr>',
                    '       <th class="checkbox-cell">',
                    '           <div class="custom-checkbox">',
                    '               <input id="selectAll" type="checkbox"/>',
                    '               <span class="icon"></span>',
                    '           </div>',
                    '       </th>',
                    '       <th width="40%">', thLabel1, '</th>',
                    '       <th width="55%">', thLabel2, '</th>',
                    '   </tr>',
                    '</thead>'
                ].join('');

            },
            tableRow: function(id, title, selected) {

                var $row;

                if (!!selected) {
                    $row = [
                        '<tr data-id=\"', id, '\">',
                        '   <td class="checkbox-cell">',
                        '       <div class="custom-checkbox">',
                        '           <input type="checkbox" class="is-selected" checked/>',
                        '           <span class="icon"></span>',
                        '       </div>',
                        '   </td>',
                        '   <td>', title, '</td>',
                        '   <td class="m-top-15" id="languageSelector', id, '"></td>',
                        '</tr>'
                    ].join('');
                } else {
                    $row = [
                        '<tr data-id=\"', id, '\">',
                        '   <td class="checkbox-cell">',
                        '       <div class="custom-checkbox">',
                        '           <input type="checkbox"/>',
                        '           <span class="icon"></span>',
                        '       </div>',
                        '   </td>',
                        '   <td>', title, '</td>',
                        '   <td class="m-top-15" id="languageSelector', id, '"></td>',
                        '</tr>'
                    ].join('');
                }

                return $row;
            }
        }
    };
});
