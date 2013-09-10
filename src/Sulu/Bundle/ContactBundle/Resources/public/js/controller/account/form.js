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
    'sulucontact/controller/form',
    'sulucontact/model/account',
    'sulucontact/model/url'
], function($, Backbone, Router, Form, Account, Url) {

    'use strict';

    return Form.extend({
        initialize: function() {
            this.setListUrl('contacts/companies');
            this.render();
            if (!!this.options.id) {
                this.setExcludeItem({id: this.options.id});
            }
        },

        render: function() {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/contact/template/account/form'], function(Template) {
                var template;

                var accountJson = $.extend(true, {}, Account.prototype.defaults);

                if (!this.options.id) {
                    this.setModel(new Account());
                    this.initTemplate(accountJson, template, Template);
                } else {
                    this.setModel(new Account({id: this.options.id}));
                    this.getModel().fetch({
                        success: function(account) {
                            var accountJson = account.toJSON();
                            this.initTemplate(accountJson, template, Template);
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        setStatic: function() {
            this.getModel().set({
                name: this.$('#name').val(),
                parent: {id: this.$('#company .name-value').data('id')}
            });

            var url = this.getModel().get('urls').at(0);
            if (!url) {
                url = new Url();
            }
            var urlValue = this.$('#url').val();
            if (urlValue) {
                url.set({
                    url: urlValue,
                    urlType: {id: defaults.urlType.id} //FIXME Read correct value
                });

                this.getModel().get('urls').add(url);
            }
        },

        // fills dialogbox
        initRemoveDialog: function() {
            var url = '/contact/api/accounts/' + this.options.id + '/deleteinfo';

            $.ajax({
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                context: this,
                type: "GET",
                url: url,

                success: function(response, textStatus, jqXhr) {
                    //console.log("get request successful");
                    this.initDialogBox(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log("error during get request: " + textStatus, errorThrown);
                },
                complete: function(response) {
                    //console.log("completed request");
                }
            });
        },


        // initializes the dialogbox and displays existing references
        initDialogBox: function(values) {

            var title = 'Warning!';
            var content = 'All data is going to be lost';
            var template = {
                content: '<h3><%= title %></h3><p><%= content %></p>',
                footer: '<button class="btn btn-black closeButton"><%= buttonCancelText %></button><button class="btn btn-black deleteButton"><%= buttonSaveText %></button>',
                header: '<button type="button" class="close">×</button>'
            };
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
                set_template = {
                    content: '<h3><%= title %></h3><p><%= content %></p>',
                    footer: '<button class="btn btn-black closeButton"><%= buttonCancelText %></button>',
                    header: '<button type="button" class="close">×</button>'
                };
                set_buttonCancelText = "Ok";
            }
            // related contacts exist => show checkbox
            else if (parseInt(values['numContacts']) > 0) {
                dependencies = this.template.dependencyListContacts(values['contacts']);
                set_title = 'Warning! Related contacts detected';
                set_content = '<p>This company still have related contacts. Would you like to delete them with this company?</p>';
                set_content += '<p><input type="checkbox" id="checkDeleteContacts"> <label for="checkDeleteContacts">Delete all ' + parseInt(values["numContacts"]) + ' related contacts.</label></p>';
            }


            // set values to dialog box
            this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                template: set_template ? set_template : template,
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
                this.$deleteButton.removeClass('loading');
                this.$saveButton.show();
            }.bind(this));

            // perform action
            this.$dialog.on('click', '.deleteButton', function() {

                var removeContacts = false;

                // check if related contacts should be deleted
                if ($('#checkDeleteContacts').length && $('#checkDeleteContacts').prop('checked')) {
                    // delete all contacts
                    removeContacts = true;
                }

                this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');

                //dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', item);
                var account = this.getModel();
                account.destroy({
                    data: {removeContacts: removeContacts},
                    processData: true,
                    success:function() {
                        this.gotoList();
                    }.bind(this)
                });
            }.bind(this));
        },

        template: {
            dependencyListContacts: function(contacts) {
                var list = "<% _.each(contacts, function(contact) { %> <li><%= contact.firstName %> <%= contact.lastName %></li> <% }); %>";
                return _.template(list, {contacts: contacts});
            },
            dependencyListAccounts: function(accounts) {
                var list = "<% _.each(accounts, function(account) { %> <li><%= account.name %></li> <% }); %>";
                return _.template(list, {accounts: accounts});
            }
        }
    });
});