/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    return {
        name: 'Sulu Security Role List',

        templates: ['/admin/security/template/role/list'],

        initialize: function() {
            this.initializeDataGrid();
            this.initializeHeader();
        },

        initializeDataGrid: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/security/template/role/list'));

            // dropdown - showing options
            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        el: '#options-dropdown',
                        trigger: '.dropdown-toggle',
                        setParentDropDown: true,
                        instanceName: 'options',
                        alignment: 'right',
                        data: [
                            {
                                'id': 1,
                                'type': 'delete',
                                'name': this.sandbox.translate('public.delete')
                            }
                        ]
                    }
                }
            ]);

            // dropdown clicked
            this.sandbox.on('husky.dropdown.options.item.click', function(event) {
                if (event.type === "delete") {
                    this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                        this.sandbox.emit('sulu.roles.delete', ids);
                    }.bind(this));
                }
            }, this);

            this.sandbox.start([
                {
                    name: 'datagrid@husky',
                    options: {
                        el: this.sandbox.dom.find('#roles-list'),
                        url: '/admin/api/security/roles/list?fields=id,name,system',
                        selectItem: {
                            type: 'checkbox'
                        },
                        pagination: false,
                        removeRow: false,
                        tableHead: [
                            {content: this.sandbox.translate('security.roles.name'), width: "30%"},
                            {content: this.sandbox.translate('security.roles.system')}
                        ],
                        excludeFields: ['id']
                    }
                }
            ]);

            this.sandbox.on('husky.datagrid.item.click', function(id) {
                this.sandbox.emit('sulu.roles.load', id);
            }.bind(this));
        },

        initializeHeader: function() {
            this.sandbox.emit('husky.header.button-type', 'add');

            this.sandbox.on('husky.button.add.click', function() {
                this.sandbox.emit('sulu.roles.new');
            }.bind(this));
        }
    };
});
