/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/translate/template/package/list',
    'mvc/relationalstore'
], function(listTemplate, RelationalStore) {

    'use strict';
    var selectedItemIds;

    return {

        view: true,

        initialize: function() {
            this.initializeHeader();
            this.render();
        },

        render: function() {
            RelationalStore.reset();
//            this.$el.removeData('Husky.Ui.DataGrid'); // FIXME: jquery

            var template = this.sandbox.template.parse(listTemplate);
            this.$el.html(template); // FIXME: jquery

            this.initDatagrid();
            this.initDropDown();
        },

        initDatagrid: function(){

            this.sandbox.start([
                {name: 'datagrid@husky', options: {
                    el: this.$el.find('#package-list'), // FIXME: jquery
                    url: '/translate/api/packages', // FIXME use list function with fields
                    pagination: false,
                    selectItem: {
                        type: 'checkbox'
                    },
                    removeRow: false,
                    tableHead: [
                        {content: 'Title'}
                    ],
                    excludeFields: ['id']
                }}
            ]);

            this.sandbox.on('husky.datagrid.item.click', function(item) {
                this.sandbox.emit('sulu.translate.package.load', item);
            }, this);

        },

        initDropDown: function(){

            this.sandbox.start([{
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
                            'type':'delete',
                            'name': 'Delete'
                        }
                    ]
                }
            }]);

            this.sandbox.on('husky.dropdown.options.clicked',  function() {
                this.sandbox.emit('husky.dropdown.options.toggle');
            });



            this.sandbox.on('husky.dropdown.options.item.click', function(event) {



                if (event.type == "delete") {
                    this.sandbox.emit('husky.dropdown.options.hide');
                    this.sandbox.emit('husky.header.button-state', 'disable');

                    // get selected ids and show dialog
                    this.sandbox.once('husky.datagrid.items.selected', function(ids) {

                        if (ids.length == 0) {
                            // no items selected
                            console.log("no ids in array");
                            this.sandbox.emit('husky.header.button-state', 'standard');
                        } else if (ids.length > 0) {
                            this.sandbox.emit('sulu.translate.packages.delete', ids);
                        }

                    }, this);

                    this.sandbox.emit('husky.datagrid.items.get-selected');
                }
            },this);


        },

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'add');

            this.sandbox.on('husky.button.add.click', function(event){
                this.sandbox.emit('sulu.translate.package.new');

            }, this);
        }


    };
});
