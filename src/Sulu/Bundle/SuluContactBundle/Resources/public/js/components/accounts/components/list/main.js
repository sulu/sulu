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
                       if (selectedIds.length == 0) {
                           // no items selected
                       } else if (selectedIds.length == 1) {
                           this.fetchDeleteInfo(selectedIds[0]);
                       } else {
                           this.fetchDeleteInfoMutliple(selectedIds);
                       }

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
        // fetches additional needed information for the dialogbox
        fetchDeleteInfo: function(id){

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
                    this.initDialogBoxRemoveOne(response, id);
                },
                error : function(jqXHR, textStatus, errorThrown) {
                    console.log("error during get request: " + textStatus, errorThrown);
                }
            });
        },

        // get deleteinfo for multiple ids
        fetchDeleteInfoMutliple: function(ids) {

            // no contacts selected
            if (ids.length == 0) {
                // do nothing
                return;
            }
            // if only one contact gets deleted
            else if (ids.length == 1){
                this.fetchDeleteInfo(ids[0]);
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
                        this.initDialogBoxRemoveMultiple(response, ids);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log("error during get request: " + textStatus, errorThrown);
                    }
                });
            }
        },

        // initializes the dialogbox and displays existing references
        initDialogBoxRemoveOne: function(values, id){

            var defaults = function (){
                return {
                    templateType: null,
                    title: 'Be careful!',
                    content: '<p>The operation you are about to do will delete data.<br/>This is not undoable!</p><p>Please think about it and accept or decline.</p>',
                    buttonCancelText: "Don't do it",
                    buttonSubmitText: "Do it, I understand"
                }
            }
            var params = defaults();

            // sub-account exists => deletion is not allowed
            if (parseInt(values['numChildren']) > 0)
            {
                var dependencies = this.template.dependencyListAccounts(values['children']);
                params.title = 'Warning! Sub-Companies detected!';
                var content = [];
                content.push('<p>Existing sub-companies found:</p><ul>'+dependencies+'</ul>');
                content.push(values['numChildren']>3 ?'<p>and <strong>'+ (parseInt(values['numChildren'])-values['children'].length) + '</strong> more.</p>' : '');
                content.push('<p>A company cannot be deleted as long it has sub-companies. Please delete the sub-companies or remove the relation.</p>');
                params.content = content.join("");
                params.templateType = 'okDialog';
                params.buttonCancelText = "Ok";
            }
            // related contacts exist => show checkbox
            else if (parseInt(values['numContacts']) > 0)
            {
                dependencies= this.template.dependencyListContacts(values['contacts']);
                params.title = 'Be careful! Related contacts detected';
                var content = [];
                content.push('<p>Related contacts found:</p><ul>'+dependencies+'</ul>');
                content.push(values['numContacts']>3 ?'<p>and <strong>'+ (parseInt(values['numContacts'])-values['contacts'].length) + '</strong> more.</p>' : '');
                content.push('<p>Would you like to delete them with the selected company?<br/>This is not undoable!</p>');
                content.push('<p>Please think about it and accept or decline.</p>');
                content.push('<p><input type="checkbox" id="checkDeleteContacts"> <label for="checkDeleteContacts">Delete all '+parseInt(values['numContacts'])+' related contacts.</label></p>');
                params.content = content.join("");
            }

            // create dialog box
            this.sandbox.start([{
                name: 'dialog@husky',
                options: {
                    el: '#dialog',
                    backdrop: true,
                    width: '650px',
                    templateType: params.templateType,
                    data: {
                        content: {
                            title: params.title,
                            content: params.content
                        },
                        footer: {
                            buttonCancelText: params.buttonCancelText,
                            buttonSubmitText: params.buttonSubmitText
                        }
                    }
                }
            }]);


            // cancel clicked - close dialog
            this.sandbox.on('husky.dialog.cancel', function() {
                this.sandbox.emit('husky.dialog.hide');
            }, this);

            // delete clicked - delete contact
            this.sandbox.on('husky.dialog.submit', function() {

                var removeContacts = false;
                // check if related contacts should be deleted
                if ($('#checkDeleteContacts').length && $('#checkDeleteContacts').prop('checked')) {
                    // delete all contacts
                    removeContacts = true;
                }

                this.sandbox.emit('husky.dialog.hide');
                this.sandbox.emit('husky.datagrid.row.remove',id)

                var account = new Account({id: id});
                account.destroy({data: {removeContacts: removeContacts}, processData:true});
            }, this);
        },

        // initializes the dialogbox and displays existing references
        initDialogBoxRemoveMultiple: function(values, ids) {


            var defaults = function (){
                return {
                    templateType: null,
                    title: 'Be careful!',
                    content: '<p>The operation you are about to do will delete data.<br/>This is not undoable!</p><p>Please think about it and accept or decline.</p>',
                    buttonCancelText: "Don't do it",
                    buttonSubmitText: "Do it, I understand"
                }
            }
            var params = defaults();

            // sub-account exists => deletion is not allowed
            if (parseInt(values['numChildren']) > 0) {
                params.title = 'Warning! Sub-Companies detected!';

                var content = [];
                content.push('<p>One or more related sub-companies found.</p>');
                content.push('<p>A company cannot be deleted as long it has sub-companies. Please delete the sub-companies or remove the relation.</p>');
                params.content = content.join("");
                params.templateType = 'okDialog';
                params.buttonCancelText = "Ok";
            }
            // related contacts exist => show checkbox
            else if (parseInt(values['numContacts']) > 0) {
                params.title = 'Warning! Related contacts detected';

                var content = [];
                content.push('<p>One or more companies still have related contacts. Would you like to delete them with the selected companies?<br/>This is not undoable!</p><p>Please think about it and accept or decline.</p>');
                content.push('<p><input type="checkbox" id="checkDeleteContacts"> <label for="checkDeleteContacts">Delete all ' + parseInt(values["numContacts"]) + ' related contacts.</label></p>');
                params.content = content.join("");
            }

            // create dialog box
            this.sandbox.start([{
                name: 'dialog@husky',
                options: {
                    el: '#dialog',
                    backdrop: true,
                    width: '650px',
                    templateType: params.templateType,
                    data: {
                        content: {
                            title: params.title,
                            content: params.content
                        },
                        footer: {
                            buttonCancelText: params.buttonCancelText,
                            buttonSubmitText: params.buttonSubmitText
                        }
                    }
                }
            }]);


            // cancel clicked - close dialog
            this.sandbox.on('husky.dialog.cancel', function() {
                this.sandbox.emit('husky.dialog.hide');
            }, this);

            // delete clicked - delete contact
            this.sandbox.on('husky.dialog.submit', function() {

                var removeContacts = false;
                // check if related contacts should be deleted
                if ($('#checkDeleteContacts').length && $('#checkDeleteContacts').prop('checked')) {
                    // delete all contacts
                    removeContacts = true;
                }


                this.sandbox.emit('husky.dialog.hide');

                ids.forEach(function(item) {
                    this.sandbox.emit('husky.datagrid.row.remove',item)
                    var account = new Account({id: item});
                    account.destroy({data: {removeContacts: removeContacts}, processData: true});
                }.bind(this));
            }, this);
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
