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
    'mvc/relationalstore',
    'sulucontact/model/contact'   // FIXME: fix this
], function(listTemplate, RelationalStore, Contact) {

    'use strict';


    return {

        view: true,

        initialize: function() {
            this.render();
        },

        render: function() {

            RelationalStore.reset(); //FIXME really necessary?
            this.$el.removeData('Husky.Ui.DataGrid'); // FIXME: jquery


            //  template as part of sandbox
//            var template =  this.sandbox.template(listTemplate);
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
                    url: '/contact/api/contacts/list?fields=id,title,firstName,lastName,position'
                        ,
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
                this.sandbox.emit('sulu.contacts.load', item);
            }, this);


            this.sandbox.on('husky.dropdown.options.clicked',  function() {
                this.sandbox.emit('husky.dropdown.options.toggle');
            });

            // optionsmenu clicked
            this.sandbox.on('husky.dropdown.options.item.click', function(event) {
                if (event.type == "delete") {
                    this.sandbox.emit('husky.dropdown.options.hide');

                    // get selected ids and show dialog
                    this.sandbox.on('husky.datagrid.items.selected', function(selectedIds) {
                        this.initDialogBoxRemoveMultiple(selectedIds);
                    }, this);
                    this.sandbox.emit('husky.datagrid.items.get-selected');
                }
            },this);

            // add button in headerbar
            this.sandbox.emit('husky.header.button-type', 'add');

            this.sandbox.on('husky.button.add.click', function() {
                this.sandbox.emit('sulu.contacts.new');
            }, this);




        },

        // fills dialogbox
        initDialogBoxRemoveMultiple: function(ids) {

            var hideDialog = function() {

            };

            // create dialog box
            this.sandbox.start([{
                name: 'dialog@husky',
                options: {
                    el: '#dialog',
                    backdrop: true,
                    width: '650px',
                    data: {
                        content: {
                            title: "Be careful!",
                            content: "<p>The operation you are about to do will delete data.<br/>This is not undoable!</p><p>Please think about it and accept or decline.</p>"
                        },
                        footer: {
                            buttonCancelText: "Don't do it",
                            buttonSubmitText: "Do it, I understand"
                        }
                    }
                }
            }]);

//            $('#dialog').off(); // FIXME: jquery


            // cancel clicked - close dialog
            this.sandbox.on('husky.dialog.cancel', function() {
                this.sandbox.emit('husky.dialog.hide');
            });

            // delete clicked - delete contact
            this.sandbox.on('husky.dialog.submit', function() {
                ids.forEach(function(id) {
                    var contact = new Contact({id: id});
                    contact.destroy({
                        success: function() {
                            this.sandbox.emit('husky.datagrid.row.remove');
                        }
                    });
                },this);
                this.sandbox.emit('husky.dialog.hide');
            }, this);


        }

    };
});
