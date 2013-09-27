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

        initialize: function() {
            this.initializeDataGrid();
            this.initializeHeader();
        },

        initializeDataGrid: function() {
            this.sandbox.start([
                {
                    name: 'datagrid@husky',
                    options: {
                        el: this.$el,
                        url: '/security/api/roles/list?fields=id,name,system',
                        selectItem: {
                            type: 'checkbox'
                        },
                        pagination: true,
                        paginationOptions: {
                            pageSize: 10,
                            showPages: 6
                        },
                        removeRow: false,
                        tableHead: [
                            {content: 'Name', width: "30%"},
                            {content: 'System'}
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
