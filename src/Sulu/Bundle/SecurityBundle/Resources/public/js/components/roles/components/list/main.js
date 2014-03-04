/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var bindCustomEvents = function() {
        this.sandbox.on('sulu.list-toolbar.add', function() {
            this.sandbox.emit('sulu.roles.new');
        }.bind(this));

        this.sandbox.on('sulu.list-toolbar.delete', function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.roles.delete', ids);
            }.bind(this));
        }.bind(this));

        this.sandbox.on('husky.datagrid.item.click', function(id) {
            this.sandbox.emit('sulu.roles.load', id);
        }.bind(this));
    };

    return {
        name: 'Sulu Security Role List',

        templates: ['/admin/security/template/role/list'],

        initialize: function() {
            this.initializeDataGrid();
            bindCustomEvents.call(this);
        },

        initializeDataGrid: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/security/template/role/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'rolesFields', '/admin/api/roles/fields',
                {
                    el: '#list-toolbar-container',
                    instanceName: 'roles'
                },
                {
                    el: this.sandbox.dom.find('#roles-list', this.$el),
                    url: '/admin/api/roles?flat=true',
                    selectItem: {
                        type: 'checkbox'
                    },
                    sortable: true
                }
            );

        }
    };
});
