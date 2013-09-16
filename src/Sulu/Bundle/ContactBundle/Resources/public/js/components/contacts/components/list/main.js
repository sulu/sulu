/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!/contact/template/contact/list'], function(listTemplate) {

    'use strict';

    var sandbox;

    return {

        view: true,

        initialize: function() {
            this.render();
        },

        render: function() {


            // TODO: relational backbone && remove data
//            this.sandbox.mvc.Relational.store.reset(); //FIXME really necessary?
            this.$el.removeData('Husky.Ui.DataGrid'); // FIXME: jquery


            //  template as part of sandbox
//            var template =  this.sandbox.template(listTemplate);
            var template = this.sandbox.template.parse(listTemplate);
            this.$el.html(template); // FIXME: jquery


            this.sandbox.start([
                {name: 'datagrid@husky', options: {
                    el: this.$el.find('#people-list'),
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
                }}
            ]);

            this.sandbox.on('husky.datagrid.item.click', function(item) {
                // TODO: route to
                this.sandbox.emit('sulu.router.navigate', 'contacts/people/edit:' + item);
            }.bind(this));

            this.sandbox.on('husky.dropdown.clicked',  function(event) {
                // TODO: communicate with dropdown
//                $('.dropdown-menu').toggle();
            });

            this.sandbox.on('husky.dropdown.delete.clicked', function(event) {
                // TODO: close dropdown & init dialogbox
//                $('.dropdown-menu').hide();
//                this.initDialogBoxRemoveMultiple(dataGrid.data('Husky.Ui.DataGrid').selectedItemIds);
            });

            // TODO
//            // create dialog box
//            var $dialog = $('#dialog').huskyDialog({
//                backdrop: true,
//                width: '650px'
//            });



            // TODO: FIXME
//            this.sandbox.emit('husky.header.button-type', 'saveDelete');



        },

        // fills dialogbox
        initDialogBoxRemoveMultiple: function(ids, event) {

            $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                data: {
                    content: {
                        title: "Warning",
                        content: "Do you really want to delete the selected contacts? All data is going to be lost."
                    },
                    footer: {
                        buttonCancelText: "Abort",
                        buttonSaveText: "Delete"
                    }
                }
            });

            // TODO
            $dialog.off();

            $dialog.on('click', '.closeButton', function() {
                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            });

            $dialog.on('click', '.saveButton', function() {
                // TODO remove by row
                ids.forEach(function(id) {
                    var contact = new Contact({id: id});
                    contact.destroy({
                        success: function() {
                            console.log('deleted model');
                            dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', id);
                        }
                    });
                }.bind(this));

                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            });
        },

        template: {
            addButton: function(text, route) {
                var $button = $('<div id="addButton" class="pull-left pointer"><span class="icon-add pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">' + text + '</span></div>');
                $button.on('click', function() {
                    Router.navigate(route);
                });
                return $button;
            }
        }

    };
});
