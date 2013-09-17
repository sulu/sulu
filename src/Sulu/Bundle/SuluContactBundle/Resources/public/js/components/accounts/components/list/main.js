/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/contact/template/account/list',
    'mvc/relationalstore',
    'sulucontact/model/account'
], function(listTemplate, RelationalStore, Account) {

    'use strict';


    return {

        view: true,

        initialize: function() {
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
                    el: this.sandbox.dom.find('#companies-list', this.$el),
                    url: '/contact/api/accounts/list?fields=id,name'
                    ,
                    pagination: false,
                    selectItem: {
                        type: 'checkbox'
                    },
                    removeRow: false,
                    tableHead: [
                        {content: 'Company Name'}
                    ],
                    excludeFields: ['id']
                }
            }]);

            // navigate to edit contact
            this.sandbox.on('husky.datagrid.item.click', function(item) {
                this.sandbox.emit('sulu.companies.load', item);
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
                        this.fetchDeleteInfoMutliple(selectedIds);
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

        // get deleteinfo for multiple ids
        fetchDeleteInfoMutliple: function(ids) {

            // no contacts selected
            if (ids.length == 0) {
                //  do nothing
                return;
            }
            // get delete info for multiple accounts
            else {
                var url = '/contact/api/accounts/multipledeleteinfo';

                $.ajax({
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    context: this,
                    type: "GET",
                    url: url,
                    data: {
                        ids: ids
                    },

                    success: function(response, textStatus, jqXhr) {
                        console.log("get request successful");
                        this.initDialogBoxRemoveMultiple(response, ids);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log("error during get request: " + textStatus, errorThrown);
                    },
                    complete: function(response) {
                        console.log("completed request");
                    }
                });
            }
        },

        // initializes the dialogbox and displays existing references
        initDialogBoxRemoveMultiple: function(values, ids) {

            var title = 'Warning!';
            var content = 'All data is going to be lost';
            var buttonCancelText = "Abort";


            // variables to set content
            var set_title, set_content, set_template, set_buttonCancelText;


            // TODO set template in husky


            // sub-account exists => deletion is not allowed
            if (parseInt(values['numChildren']) > 0) {
                var dependencies = this.template.dependencyListAccounts(values['children']);
                set_title = 'Warning! Sub-Companies detected!';
                set_content = '<p>One or more related sub-companies found.</p>';
                set_content += '<p>A company cannot be deleted as long it has sub-companies. Please delete the sub-companies ' +
                    'or remove the relation.</p>';
                set_template = 'okDialog';
                set_buttonCancelText = "Ok";
            }
            // related contacts exist => show checkbox
            else if (parseInt(values['numContacts']) > 0) {
                dependencies = this.template.dependencyListContacts(values['contacts']);
                set_title = 'Warning! Related contacts detected';
                set_content = '<p>One or more companies still have related contacts. Would you like to delete them with the selected companies?</p>';
                set_content += '<p><input type="checkbox" id="checkDeleteContacts"> <label for="checkDeleteContacts">Delete all ' + parseInt(values["numContacts"]) + ' related contacts.</label></p>';
            }


            // set values to dialog box
            this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                templateType: set_template ? set_template : null,
                data: {
                    content: {
                        title: set_title ? set_title : title,
                        content: set_content ? set_content : content
                    },
                    footer: {
                        buttonCancelText: set_buttonCancelText ? set_buttonCancelText : buttonCancelText,
                        buttonSaveText: "Delete"
                    }
                }
            });


            // events on dialogbox

            // TODO
            this.$dialog.off();

            // abort/close
            this.$dialog.on('click', '.closeButton', function() {
                this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            }.bind(this));

            // perform action
            this.$dialog.on('click', '.saveButton', function() {

                var removeContacts = false;

                // check if related contacts should be deleted
                if ($('#checkDeleteContacts').length && $('#checkDeleteContacts').prop('checked')) {
                    // delete all contacts
                    removeContacts = true;
                }

                this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');

                ids.forEach(function(item) {
                    dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', item);
                    var account = new Account({id: item});
                    account.destroy({data: {removeContacts: removeContacts}, processData: true});
                }.bind(this));
            }.bind(this));
        },

        template: {
            button: function(url, name) {
                return '<a class="btn" href="' + url + '" target="_top" title="Add">' + name + '</a>';
            },
            dependencyListContacts: function(contacts) {
                var list = "<% _.each(contacts, function(contact) { %> <li><%= contact.firstName %> <%= contact.lastName %></li> <% }); %>";
                return _.template(list, {contacts: contacts});
            },
            dependencyListAccounts: function(accounts) {
                var list = "<% _.each(accounts, function(account) { %> <li><%= account.name %></li> <% }); %>";
                return _.template(list, {accounts: accounts});
            },
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
