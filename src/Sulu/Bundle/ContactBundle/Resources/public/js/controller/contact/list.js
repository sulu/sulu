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
], function($, Backbone, Router, Contact) {

    'use strict';

    var $dialog, dataGrid;

    return Backbone.View.extend({

        initialize: function() {
            this.render();
        },

        render: function() {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            this.$el.removeData('Husky.Ui.DataGrid');

            require(['text!/contact/template/contact/list'], function(Template) {
                var template = _.template(Template);
                this.$el.html(template);

                dataGrid = this.$('#peopleList').huskyDataGrid({
                    url: '/contact/api/contacts/list?fields=id,title,firstName,lastName,position',
                    pagination: false,
                    selectItemType: 'checkbox',
                    tableHead: [
                        {content: 'Title'},
                        {content: 'Firstname'},
                        {content: 'Lastname'},
                        {content: 'Position'}
                    ],
                    excludeFields: ['id']
                });

                dataGrid.data('Husky.Ui.DataGrid').on('data-grid:item:click', function(item) {
                    Router.navigate('contacts/people/edit:' + item);
                });

                this.$el.on('click', '.remove-row > span', function(event) {
                    var $element = $(event.currentTarget);
                    var $parent = $element.parent().parent();
                    var id = $parent.data('id');

                    this.removeItem(id);
                }.bind(this));

                this.$el.on('click', '#removePeople', function(event) {
                    this.initDialogBoxRemoveMultiple(dataGrid.data('Husky.Ui.DataGrid').selectedItemIds);
                    return false;
                }.bind(this));

                // create dialog box
                $dialog = $('#dialog').huskyDialog({
                    backdrop: true,
                    width: '650px'
                });
            }.bind(this));
        },

        removeItem: function(id) {
            // check if delation should be performed
            this.initDialogBoxRemoveOne(id, event);

        },

        // fills dialogbox and displays existing references
        initDialogBoxRemoveOne: function(id, event) {

            $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                template: {
                    content: '<h3><%= title %></h3><p><%= content %></p>',
                    footer: '<button class="btn btn-black closeButton"><%= buttonCancelText %></button><button class="btn btn-black deleteButton"><%= buttonSaveText %></button>',
                    header: '<button type="button" class="close">×</button>'
                },
                data: {
                    content: {
                        title: "Warning",
                        content: "Do you really want to delete the contact? All data is going to be lost."
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

            $dialog.on('click', '.deleteButton', function() {

                dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', event);

                var contact = new Contact({id: id});
                contact.destroy({
                    success: function() {
                        console.log('deleted model');
                    }
                });

                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            });
        },

        // fills dialogbox and displays existing references
        initDialogBoxRemoveMultiple: function(ids, event) {

            $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                template: {
                    content: '<h3><%= title %></h3><p><%= content %></p>',
                    footer: '<button class="btn btn-black closeButton"><%= buttonCancelText %></button><button class="btn btn-black deleteButton"><%= buttonSaveText %></button>',
                    header: '<button type="button" class="close">×</button>'
                },
                data: {
                    content: {
                        title: "Warning",
                        content: "Do you really want to delete <b>many</b> contacts? All data is going to be lost."
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

            $dialog.on('click', '.deleteButton', function() {

                //dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', event);
                ids.forEach(function(id) {
                    var contact = new Contact({id: id});
                    contact.destroy({
                        success: function() {
                            console.log('deleted model');
                        }
                    });
                }.bind(this));

                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            });
        }
    });
});