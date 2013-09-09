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

                dataGrid = this.$('#people-list').huskyDataGrid({
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

                this.$el.on('click', '.dropdown-toggle', function(event) {
                    $('.dropdown-menu').toggle();
                });

                this.$el.on('click', '#remove-people', function(event) {
                    this.initDialogBoxRemoveMultiple(dataGrid.data('Husky.Ui.DataGrid').selectedItemIds);
                }.bind(this));

                // create dialog box
                $dialog = $('#dialog').huskyDialog({
                    backdrop: true,
                    width: '650px'
                });
            }.bind(this));

            this.initOperations();
        },

        initOperations: function() {

            var $optionsRight = $('#headerbar-mid-right');
            $optionsRight.empty();
            var $optionsLeft = $('#headerbar-mid-left');
            $optionsLeft.empty();
            $optionsLeft.append(this.template.button('Add', '#contacts/people/add'));

        },

        // fills dialogbox
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
            button: function(text, route) {
                return '<a class="btn" href="'+route+'">'+text+'</a>';
            }
        }
    });
});