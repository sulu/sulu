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

                if (!this.options.id) {
                    this.setModel(new Account());
                    this.initTemplate(this.getModel().toJSON(), Template);
                } else {
                    this.setModel(new Account({id: this.options.id}));
                    this.getModel().fetch({
                        success: function(account) {
                            this.initTemplate(account.toJSON(), Template);
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
                    this.initDialogBox(response, this.options.id);
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
        initDialogBox: function(values, id){

            var defaults = function (){
                return {
                    templateType: null,
                    title: 'Warning!',
                    content: 'Do you really want to delete the selected company? All data is going to be lost.',
                    buttonCancelText: 'Cancel',
                    buttonSaveText: 'Delete'
                }
            }

            var values = defaults();

            // TODO set template in husky

            // FIXME translation

            // sub-account exists => deletion is not allowed
            if (parseInt(values['numChildren']) > 0)
            {
                var dependencies = this.template.dependencyListAccounts(values['children']);
                values.title = 'Warning! Sub-Companies detected!';

                values.templateType = 'okDialog';
                values.buttonCancelText = "Ok";

                var content = [];
                content.push('<p>Existing sub-companies found:</p><ul>'+dependencies+'</ul>');
                content.push(values['numChildren']>3 ?'<p>and <strong>'+ (parseInt(values['numChildren'])-values['children'].length) + '</strong> more.</p>' : '');
                content.push('<p>A company cannot be deleted as long it has sub-companies. Please delete the sub-companies or remove the relation.</p>');
                values.content = content.join(" ");
            }
            // related contacts exist => show checkbox
            else if (parseInt(values['numContacts']) > 0)
            {
                dependencies= this.template.dependencyListContacts(values['contacts']);
                values.title = 'Warning! Related contacts detected';

                var content= [];
                content.push('<p>Related contacts found:</p><ul>'+dependencies+'</ul>');
                content.push(values['numContacts']>3 ?'<p>and <strong>'+ (parseInt(values['numContacts'])-values['contacts'].length) + '</strong> more.</p>' : '');
                content.push('<p>Would you like to delete them with the selected company?</p>');
                content.push('<p><input type="checkbox" id="checkDeleteContacts"> <label for="checkDeleteContacts">Delete all '+parseInt(values['numContacts'])+' related contacts.</label></p>');
                values.content = content.join(" ");
            }


            // set values to dialog box
            this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                templateType: values.templateType,
                data: {
                    content: {
                        title: values.title,
                        content: values.content
                    },
                    footer: {
                        buttonCancelText: values.buttonCancelText,
                        buttonSaveText: values.buttonSaveText
                    }
                }
            });


            // events on dialogbox

            // TODO
            this.$dialog.off();

            this.$dialog.data('Husky.Ui.Dialog').on('dialog:backdrop:clicked', function() {
                this.$deleteButton.removeClass('loading-black');
                this.$saveButton.show();
            }.bind(this));

            // abort/close
            this.$dialog.on('click', '.closeButton', function() {
                this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');

                this.$deleteButton.removeClass('loading-black');
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


                dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', event);
                this.$dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
                var account = new Account({id: id});
                account.destroy({data: {removeContacts: removeContacts}, processData:true});
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
