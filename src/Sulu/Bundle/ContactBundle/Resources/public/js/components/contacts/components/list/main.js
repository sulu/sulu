/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/contact/template/contact/list',
    'mvc/relationalstore'
], function(listTemplate, RelationalStore) {

    'use strict';

    var sandbox;


    return {

        view: true,

        initialize: function() {
            sandbox = this.sandbox;
            this.render();
        },

        render: function() {

            RelationalStore.reset(); //FIXME really necessary?


            var template = this.sandbox.template.parse(listTemplate);
            this.sandbox.dom.html(this.$el, template);


            // dropdown - showing options
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

            // datagrid
            this.sandbox.start([{
                name: 'datagrid@husky',
                options: {
                    el: this.sandbox.dom.find('#people-list', this.$el),
                    url: '/contact/api/contacts/list?fields=id,title,firstName,lastName,position',
                    pagination: false,
                    selectItem: {
                        type: 'checkbox'
                    },
                    removeRow: false,
                    tableHead: [

                        {content: 'Title'},
                        {content: 'Firstname'},
                        {content: 'Lastname'},
                        {content: 'Position'}
                    ],
                    excludeFields: ['id']
                }
            }]);

            // navigate to edit contact
            this.sandbox.on('husky.datagrid.item.click', function(item) {
                this.sandbox.emit('sulu.contacts.contacts.load', item);
            }, this);


            this.sandbox.on('husky.dropdown.options.clicked',  function() {
                this.sandbox.emit('husky.dropdown.options.toggle');
            }, this);

            // optionsmenu clicked
            this.sandbox.on('husky.dropdown.options.item.click', function(event) {
                if (event.type === "delete") {
                    this.sandbox.emit('sulu.contacts.contacts.delete');
                }
            },this);

            // add button in headerbar
            this.sandbox.emit('husky.header.button-type', 'add');

            this.sandbox.on('husky.button.add.click', function() {
                this.sandbox.emit('sulu.contacts.contacts.new');
            }, this);
        }
    };
});
