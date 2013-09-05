/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'backbone',
    'router',
    'sulucontact/model/contact'
], function ($, Backbone, Router, Contact) {

    'use strict';

    var $dialog, dataGrid;

    return Backbone.View.extend({

        initialize: function () {
            this.render();
        },

        render: function () {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            this.$el.removeData('Husky.Ui.DataGrid');


            require(['text!sulucontact/templates/contact/table-row.html'], function (RowTemplate) {
                dataGrid = this.$el.huskyDataGrid({
                    url: '/contact/api/contacts/list?field=id,fistName,lastName',
                    pagination: true,
                    showPages: 6,
                    pageSize: 4,
                    template: {
                        row: RowTemplate
                    },
                    selectItemType: 'radio'
                });

                dataGrid.data('Husky.Ui.DataGrid').on('data-grid:item:select', function (item) {
                    Router.navigate('contacts/people/edit:' + item);
                });

                this.$el.on('click', '.remove-row > span', function (event) {

                    var $element = $(event.currentTarget);
                    var $parent = $element.parent().parent();
                    var id = $parent.data('id');

                    // check if delation should be performed
                    this.initDialogBox(id,event);

                }.bind(this));

                // create dialog box
                $dialog = $('#dialog').huskyDialog({
                    backdrop: true,
                    width: '650px'
                });

            }.bind(this));


        },

        // fills dialogbox and displays existing references
        initDialogBox: function(id, event){

            $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                template: {
                    content: '<h3><%= title %></h3><p><%= content %></p>',
                    footer: '<button class="btn btn-black closeButton"><%= buttonCancelText %></button><button class="btn btn-black deleteButton"><%= buttonSaveText %></button>',
                    header: '<button type="button" class="close">×</button>'
                },
                data: {
                    content: {
                        title:  "Warning" ,
                        content: "Do you really want to delete the contact? All data is going to be lost."
                    },
                    footer: {
                        buttonCancelText: "Abort",
                        buttonSaveText: "Delete"
                    }
                }
            });

            $dialog.on('click', '.closeButton', function() {
                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            });

            $dialog.on('click', '.deleteButton', function() {

                dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', event);

                var contact = new Contact({id: id});
                contact.destroy({
                    success: function () {
                        console.log('deleted model');
                    }
                });

                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            });
        }
    });
});