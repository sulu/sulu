/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulusecurity/role-router',
    'services/sulusecurity/role-manager'
], function(RoleRouter, RoleManager) {

    'use strict';

    var constants = {
            datagridInstanceName: 'roles',
            toolbarInstanceName: 'roles'
        },

        bindCustomEvents = function() {
            this.sandbox.on('sulu.toolbar.add', RoleRouter.toAdd);

            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.items.get-selected', function(ids) {
                    this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                        if (wasConfirmed) {
                            deleteRoles.call(this, ids);
                        }
                    }.bind(this));
                }.bind(this));
            }.bind(this));

            this.sandbox.on('husky.datagrid.' + constants.datagridInstanceName + '.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
            }.bind(this));
        },

        deleteRoles = function(ids) {
            RoleManager.delete(ids).then(function() {
                this.sandbox.emit('sulu.labels.success.show', 'sulu-security.role.removed');
                this.sandbox.util.foreach(ids, function(id) {
                    this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.record.remove', id);
                }.bind(this));
            }.bind(this)).fail(function() {
                this.sandbox.emit('sulu.labels.error.show');
            }.bind(this));
        };

    return {

        stickyToolbar: true,

        name: 'Sulu Security Role List',

        layout: {
            content: {
                width: 'max'
            }
        },

        header: function() {
            return {
                noBack: true,

                title: 'security.roles.title',
                underline: false,

                toolbar: {
                    buttons: {
                        add: {},
                        deleteSelected: {},
                        export: {
                            options: {
                                urlParameter: {
                                    flat: true
                                },
                                url: '/admin/api/roles.csv'
                            }
                        }
                    }
                }
            };
        },

        templates: ['/admin/security/template/role/list'],

        initialize: function() {
            this.initializeDataGrid();
            bindCustomEvents.call(this);
        },

        initializeDataGrid: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/security/template/role/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'roles', '/admin/api/roles/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: constants.toolbarInstanceName,
                    groups: [
                        {
                            id: 1,
                            align: 'left'
                        },
                        {
                            id: 2,
                            align: 'right'
                        }
                    ]
                },
                {
                    el: this.sandbox.dom.find('#roles-list', this.$el),
                    url: '/admin/api/roles?flat=true',
                    searchInstanceName: 'roles',
                    searchFields: ['id', 'name', 'system'],
                    resultKey: 'roles',
                    instanceName: constants.datagridInstanceName,
                    actionCallback: RoleRouter.toEdit
                },
                'roles',
                '#roles-list-info'
            );
        }
    };
});
