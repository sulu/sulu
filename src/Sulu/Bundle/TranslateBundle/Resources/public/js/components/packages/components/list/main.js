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

    return {

        view: true,

        initialize: function() {
            this.initializeHeader();
            this.render();
        },

        render: function() {
            RelationalStore.reset();
            //this.$el.removeData('Husky.Ui.DataGrid'); // FIXME: jquery

            var template = this.sandbox.template.parse(listTemplate);
            this.sandbox.dom.html(this.options.el, template);

            this.initDatagrid();
            this.initDropDown();
        },

        initDatagrid: function(){

            this.sandbox.start([
                {name: 'datagrid@husky', options: {
                    el: this.sandbox.dom.$('#package-list'),
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
            },this);

            this.dropDown();

        },

        dropDown: function(){

            this.sandbox.on('husky.dropdown.options.item.click', function(event) {

                // TODO - problem with events when checked, entered form, back to list, check different -> delete -> old checked values
                //this.unbindListener();

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
                            this.sandbox.emit('sulu.translate.packages.delete', ids, false);
                        }

                    }, this);

                    this.sandbox.emit('husky.datagrid.items.get-selected');

                }
            },this);

        },

        unbindListener: function(){
            this.sandbox.off('husky.datagrid.items.selected',this.dropDown());
//            this.sandbox.off('husky.dropdown.options.item.click',this.dropDown());
        },


        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'add');

            this.sandbox.on('husky.button.add.click', function(event){
                this.sandbox.emit('sulu.translate.package.new');

            }, this);
        }


    };
});
