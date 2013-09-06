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
    'sulucontact/model/account'
], function ($, Backbone, Router, Account) {

    'use strict';

    var $dialog, dataGrid;

    return Backbone.View.extend({

        initialize: function () {
            this.render();
        },

        render: function () {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            this.$el.removeData('Husky.Ui.DataGrid');

            require(['text!sulucontact/templates/account/table-row.html'], function (RowTemplate) {

                dataGrid = this.$el.huskyDataGrid({
                    url: '/contact/api/accounts/list?field=name',
                    pagination: true,
                    showPages: 6,
                    pageSize: 4,
                    template: {
                        row: RowTemplate
                    },
                    selectItemType: 'radio'
                });

                dataGrid.data('Husky.Ui.DataGrid').on('data-grid:item:select', function (item) {
                    Router.navigate('contacts/companies/edit:' + item);
                });

                // fetch delete info when clicking on delete
                this.$el.on('click', '.remove-row > span', function (event) {
                    var $element = $(event.currentTarget);
                    var $parent = $element.parent().parent();
                    var id = $parent.data('id');

                    this.fetchDeleteInfo(id,event);

                }.bind(this));


                $dialog = $('#dialog').huskyDialog({
                    backdrop: true,
                    width: '650px'
                });

                this.initOperationsRight();

            }.bind(this));
        },

        // fetches additional needed information needed for the dialogbox
        fetchDeleteInfo: function(id,event){

            var url = '/contact/api/accounts/'+id+'/deleteinfo';

            $.ajax({

                    headers : {
                        'Accept' : 'application/json',
                        'Content-Type' : 'application/json'
                    },
                    context: this,
                    type: "GET",
                    url: url,

                    success : function(response, textStatus, jqXhr) {
                        console.log("get request successful");
                        this.initDialogBox(response, id,event);
                    },
                    error : function(jqXHR, textStatus, errorThrown) {
                        console.log("error during get request: " + textStatus, errorThrown);
                    },
                    complete : function(response) {
                        console.log("completed request");
                    }
            });
        },

        // initializes the dialogbox and displays existing references
        initDialogBox: function(values, id, event){

            var title   = 'Warning!';
            var content = 'All data is going to be lost';
            var template = 'info' ;

            // TODO set template in husky

            // deletion is not allowed
            var dependencies = this.template.dependencyListAccounts(values['children']);
            if (parseInt(values['numChildren']) > 0) {
                title = 'Warning!';
                content = '<p>Existing Sub-companies found:</p><ul>'+dependencies+'</ul>' +
                    '<p>A company cannot be deleted as long it has sub-companies. Please remove the sub-companies ' +
                    'or unlink them.</p>';
                template = 'info';
            }


            var dependencies;
            if(values['contacts'].length > 0) {
                dependencies= this.template.dependencyListContacts(values['contacts']);
            }


            $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                template: {
                    content: '<h3><%= title %></h3><p><%= content %></p>',
                    footer: '<button class="btn btn-black closeButton"><%= buttonCancelText %></button><button class="btn btn-black deleteButton"><%= buttonSaveText %></button>',
                    header: '<button type="button" class="close">×</button>'
                },
                data: {
                    content: {
                        title: title,
                        content: content
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
                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
                var account = new Account({id: id});
                account.destroy();
            });
        },

        // displays the add button on the top
        initOperationsRight: function(){

            var $operationsRight = $('#headerbar-mid-right');
            $operationsRight.empty();
            $operationsRight.append(this.template.button('#contacts/companies/add','Add...'));
        },

        template: {
            button: function(url, name){
                return '<a class="btn" href="'+url+'" target="_top" title="Add">'+name+'</a>';
            },
            dependencyListContacts: function(contacts) {
                var list = "<% _.each(contacts, function(contact) { %> <li><%= contact.firstName %> <%= contact.lastName %></li> <% }); %>";
                return _.template(list,{contacts:contacts});
            },
            dependencyListAccounts: function(accounts) {
                var list = "<% _.each(accounts, function(account) { %> <li><%= account.name %></li> <% }); %>";
                return _.template(list,{accounts:accounts});
            }
        }
    });
});
