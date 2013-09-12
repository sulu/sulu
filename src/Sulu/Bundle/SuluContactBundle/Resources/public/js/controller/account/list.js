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
], function($, Backbone, Router, Account) {

    'use strict';

    var dataGrid;

    return Backbone.View.extend({

        initialize: function() {
            this.render();
        },

        render: function() {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            this.$el.removeData('Husky.Ui.DataGrid');

            require(['text!/contact/template/account/list'], function(Template) {
                var template = _.template(Template);
                this.$el.html(template);

                dataGrid = this.$('#companies-list').huskyDataGrid({
                    url: '/contact/api/accounts/list?fields=id,name',
                    pagination: false,
                    selectItem: {
                        type: 'checkbox'
                    },
                    tableHead: [
                        {content: 'Company Name'}
                        //{content: 'E-Mail'}
                    ],
                    excludeFields: ['id']
                });

                dataGrid.data('Husky.Ui.DataGrid').on('data-grid:item:click', function(item) {
                    Router.navigate('contacts/companies/edit:' + item);
                });

                this.$el.on('click', '.dropdown-toggle', function(event) {
                    $('.dropdown-menu').toggle();
                });

                // edit menu listener
                this.$el.on('click', '#edit-remove', function(event) {
                    $('.dropdown-menu').hide();
                    this.fetchDeleteInfoMutliple(dataGrid.data('Husky.Ui.DataGrid').selectedItemIds);
                }.bind(this));

                // initialize dialog box
                this.$dialog = $('#dialog').huskyDialog({
                    backdrop: true,
                    width: '650px'
                });
            }.bind(this));

            this.initOptions();
        },

        initOptions: function() {
            var $optionsRight = $('#headerbar-mid-right');
            $optionsRight.off();
            $optionsRight.empty();
            var $optionsLeft = $('#headerbar-mid-left');
            $optionsLeft.off();
            $optionsLeft.empty();
            $optionsLeft.append(this.template.addButton('Add', '#contacts/companies/add'));

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
                    title: 'Warning!',
                    content: 'Do you really want to delete the selected companies? All data is going to be lost.',
                    buttonCancelText: 'Cancel',
                    buttonSubmitText: 'Delete'
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
                params.title = 'Warning! Related contacts detected';
                var content = [];
                content.push('<p>Related contacts found:</p><ul>'+dependencies+'</ul>');
                content.push(values['numContacts']>3 ?'<p>and <strong>'+ (parseInt(values['numContacts'])-values['contacts'].length) + '</strong> more.</p>' : '');
                content.push('<p>Would you like to delete them with the selected company?</p>');
                content.push('<p><input type="checkbox" id="checkDeleteContacts"> <label for="checkDeleteContacts">Delete all '+parseInt(values['numContacts'])+' related contacts.</label></p>');
                params.content = content.join("");
            }


            // set values to dialog box
            this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
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
            });

            // events on dialogbox

            // TODO
            this.$dialog.off();

            // abort/close
            this.$dialog.on('click', '.dialogButtonCancel', function() {
                this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            }.bind(this));

            // perform action
            this.$dialog.on('click', '.dialogButtonSubmit', function() {

                var removeContacts = false;

                // check if related contacts should be deleted
                if ($('#checkDeleteContacts').length && $('#checkDeleteContacts').prop('checked')) {
                    // delete all contacts
                    removeContacts = true;
                }


                dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', event);
                this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
                var account = new Account({id: id});
                account.destroy({data: {removeContacts: removeContacts}, processData:true});
            }.bind(this));
        },

        // initializes the dialogbox and displays existing references
        initDialogBoxRemoveMultiple: function(values, ids) {


            var defaults = function (){
                return {
                    templateType: null,
                    title: 'Warning!',
                    content: 'Do you really want to delete the selected companies? All data is going to be lost.',
                    buttonCancelText: 'Cancel',
                    buttonSubmitText: 'Delete'
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
                content.push('<p>One or more companies still have related contacts. Would you like to delete them with the selected companies?</p>');
                content.push('<p><input type="checkbox" id="checkDeleteContacts"> <label for="checkDeleteContacts">Delete all ' + parseInt(values["numContacts"]) + ' related contacts.</label></p>');
                params.content = content.join("");
            }

            // set values to dialog box
            this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                templateType: params.templateType,
                data: {
                    content: {
                        title: params.title,
                        content: params.content
                    },
                    footer: {
                        buttonCancelText:   params.buttonCancelText,
                        buttonSubmitText:     params.buttonSubmitText
                    }
                }
            });


            // events on dialogbox

            // TODO
            this.$dialog.off();

            // abort/close
            this.$dialog.on('click', '.dialogButtonCancel', function() {
                this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            }.bind(this));

            // perform action
            this.$dialog.on('click', '.dialogButtonSubmit', function() {

                var removeContacts = false;

                // check if related contacts should be deleted
                if ($('#checkDeleteContacts').length && $('#checkDeleteContacts').prop('checked')) {
                    // delete all contacts
                    removeContacts = true;
                }

                this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');

                // remove contacts
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
    });
});
