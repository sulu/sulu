/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define({

    name: 'Sulu Security Role List',

    initialize: function() {
        this.initializeDatagrid();
        this.initializeHeader();
    },

    initializeDatagrid: function() {
        this.sandbox.start([
            {
                name: 'datagrid@husky',
                options: {
                    el: '#datagrid',
                    url: '/security/api/roles/list',
                    selectItem: {
                        type: 'checkbox'
                    },
                    pagination: true,
                    paginationOptions: {
                        pageSize: 4,
                        showPages: 6
                    },
                    removeRow: true,
                    autoRemoveHandling: false,
                    tableHead: [
                        {content: 'Content 1', width: "30%"},
                        {content: 'Content 2'},
                        {content: 'Content 3'}
                    ],
                    excludeFields: ['id']
                }
            }
        ]);
    },

    initializeHeader: function() {
        this.sandbox.emit('husky.header.button-type', 'add');

        this.sandbox.on('husky.button.add.click', function() {
            this.sandbox.emit('sulu.roles.new');
        });
    }
});