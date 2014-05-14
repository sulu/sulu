/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontact/model/account'
], function(Account) {

    'use strict';

    return {

        initialize: function() {
            this.bindCustomEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('sulu.contacts.account.delete', function() {
                this.del();
            }, this);

            // save the current package
            this.sandbox.on('sulu.contacts.accounts.save', function(data) {
                this.save(data);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.contacts.accounts.load', function(id) {
                this.load(id);
            }, this);

            // add new contact
            this.sandbox.on('sulu.contacts.accounts.new', function(type) {
                this.add(type);
            }, this);

            // delete selected contacts
            this.sandbox.on('sulu.contacts.accounts.delete', function(ids) {
                this.delAccounts(ids);
            }, this);

            // load list view
            this.sandbox.on('sulu.contacts.accounts.list', function(type, noReload) {
                var typeString = '';
                if (!!type) {
                    typeString = '/type:' + type;
                }
                this.sandbox.emit('sulu.router.navigate', 'contacts/accounts' + typeString, !noReload ? true : false , true);
            }, this);
        },

        del: function() {
            this.confirmSingleDeleteDialog(function(wasConfirmed, removeContacts) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    this.account.destroy({
                        data: {removeContacts: !!removeContacts},
                        processData: true,
                        success: function() {
                            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts');
                        }.bind(this)
                    });
                }
            }.bind(this), this.options.id);
        },

        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            this.account.set(data);
            this.account.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!data.id) {
                        this.sandbox.emit('sulu.contacts.accounts.saved', model);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + model.id + '/details');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        load: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + id + '/details');
        },

        add: function(type) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/add/type:' + type);

        },

        delAccounts: function(ids) {
            if (ids.length < 1) {
                this.sandbox.emit('sulu.dialog.error.show', 'No contacts selected for Deletion');
                return;
            }
            this.confirmMultipleDeleteDialog(ids, function(wasConfirmed, removeContacts) {
                if (wasConfirmed) {
                    // TODO: show loading icon
                    ids.forEach(function(id) {
                        var account = new Account({id: id});
                        account.destroy({
                            data: {removeContacts: !!removeContacts},
                            processData: true,

                            success: function() {
                                this.sandbox.emit('husky.datagrid.record.remove', id);
                            }.bind(this)
                        });
                    }.bind(this));
                }
            }.bind(this));
        },

        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="accounts-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {
                    name: 'accounts/components/list@sulucontact',
                    options: {
                        el: $list,
                        accountType: this.options.accountType ? this.options.accountType : null
                    }
                }
            ]);

        },

        renderForm: function() {
            // load data and show form
            this.account = new Account();

            var $form = this.sandbox.dom.createElement('<div id="accounts-form-container"/>');
            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                //account = this.getModel(this.options.id);
                this.account.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/form@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                    }.bind(this)
                });
            } else {
                this.sandbox.start([
                    {name: 'accounts/components/form@sulucontact', options: { el: $form, data: this.account.toJSON(), accountTypeName: this.options.accountType}}
                ]);
            }
        },

        confirmSingleDeleteDialog: function(callbackFunction, id) {
            var url = '/admin/api/accounts/' + id + '/deleteinfo';

            this.sandbox.util.ajax({
                headers: {
                    'Content-Type': 'application/json'
                },

                context: this,
                type: 'GET',
                url: url,

                success: function(response) {
                    this.showConfirmSingleDeleteDialog(response, id, callbackFunction);
                }.bind(this),

                error: function(jqXHR, textStatus, errorThrown) {
                    this.sandbox.logger.error("error during get request: " + textStatus, errorThrown);
                }.bind(this)
            });
        },

        showConfirmSingleDeleteDialog: function(values, id, callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            var params = {
                templateType: null,
                title: 'Warning!',
                content: 'Do you really want to delete the selected company? All data is going to be lost.',
                buttonCancelText: 'Cancel',
                buttonSubmitText: 'Delete'
            };

            // FIXME translation

            // sub-account exists => deletion is not allowed
            if (parseInt(values.numChildren, 10) > 0) {
                params.title = 'Warning! Sub-Companies detected!';

                params.templateType = 'okDialog';
                params.buttonCancelText = 'Ok';

                params.content = [
                    '<p>Existing sub-companies found:</p><ul>',
                    this.template.dependencyListAccounts.call(this, values.children),
                    '</ul>',
                    values.numChildren > 3 ? ['<p>and <strong>', (parseInt(values.numChildren, 10) - values.children.length), '</strong> more.</p>'].join('') : '',
                    '<p>A company cannot be deleted as long it has sub-companies. Please delete the sub-companies or remove the relation.</p>'
                ].join('');
            }
            // related contacts exist => show checkbox
            else if (parseInt(values.numContacts, 10) > 0) {
                params.title = 'Warning! Related contacts detected';

                params.content = [
                    '<p>Related contacts found:</p>',
                    '<ul>',
                    this.template.dependencyListContacts.call(this, values.contacts),
                    '</ul>',
                    values.numContacts > 3 ? ['<p>and <strong>', parseInt(values.numContacts, 10) - values.contacts.length, '</strong> more.</p>'].join('') : '',
                    '<p>Would you like to delete them with the selected company?</p>',
                    '<p>',
                    '<input type="checkbox" id="delete-contacts" />',
                    '<label for="delete-contacts">Delete all ', parseInt(values.numContacts, 10), ' related contacts.</label>',
                    '</p>'
                ].join('');
            }

            // show dialog
            this.sandbox.emit('sulu.dialog.confirmation.show', {
                content: {
                    title: params.title,
                    content: params.content
                },
                footer: {
                    buttonCancelText: params.buttonCancelText,
                    buttonSubmitText: params.buttonSubmitText
                },
                callback: {
                    submit: function() {
                        var deleteContacts = this.sandbox.dom.find('#delete-contacts').length && this.sandbox.dom.prop('#delete-contacts', 'checked');
                        this.sandbox.emit('husky.dialog.hide');

                        // call callback function
                        if (!!callbackFunction) {
                            callbackFunction(true, deleteContacts);
                        }
                    }.bind(this),
                    cancel: function() {
                        this.sandbox.emit('husky.dialog.hide');

                        // call callback function
                        if (!!callbackFunction) {
                            callbackFunction(false);
                        }
                    }.bind(this)
                }
            }, params.templateType);
        },

        confirmMultipleDeleteDialog: function(ids, callbackFunction) {

            if (ids.length === 0) {
                return;
            } else if (ids.length === 1) {
                this.confirmSingleDeleteDialog(callbackFunction, ids[0]);
            } else {
                var url = '/admin/api/accounts/multipledeleteinfo';

                this.sandbox.util.ajax({
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },

                    context: this,
                    type: 'GET',
                    url: url,
                    data: {ids: ids},

                    success: function(response) {
                        this.showConfirmMultipleDeleteDialog(response, ids, callbackFunction);
                    }.bind(this),

                    error: function(jqXHR, textStatus, errorThrown) {
                        this.sandbox.logger.error("error during get request: " + textStatus, errorThrown);
                    }.bind(this)
                });
            }
        },

        showConfirmMultipleDeleteDialog: function(values, ids, callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            var params = {
                templateType: null,
                title: 'Warning!',
                content: 'Do you really want to delete the selected companies? All data is going to be lost.',
                buttonCancelText: 'Cancel',
                buttonSubmitText: 'Delete'
            };

            // FIXME translation

            // sub-account exists => deletion is not allowed
            if (parseInt(values.numChildren, 10) > 0) {
                params.title = 'Warning! Sub-Companies detected!';

                params.templateType = 'okDialog';
                params.buttonCancelText = 'OK';

                params.content = [
                    '<p>One or more related sub-companies found.</p>',
                    '<p>A company cannot be deleted as long it has sub-companies. Please delete the sub-companies or remove the relation.</p>'
                ].join('');
            }
            // related contacts exist => show checkbox
            else if (parseInt(values.numContacts, 10) > 0) {
                params.title = 'Warning! Related contacts detected';

                params.content = [
                    '<p>One or more companies still have related contacts. Would you like to delete them with the selected companies?</p>',
                    '<p>',
                    '<input type="checkbox" id="delete-contacts" />',
                    '<label for="delete-contacts">Delete all ', parseInt(values.numContacts, 10), ' related contacts.</label>',
                    '</p>'
                ].join('');
            }

            // show dialog
            this.sandbox.emit('sulu.dialog.confirmation.show', {
                content: {
                    title: params.title,
                    content: params.content
                },
                footer: {
                    buttonCancelText: params.buttonCancelText,
                    buttonSubmitText: params.buttonSubmitText
                },
                callback: {
                    submit: function() {
                        var deleteContacts = this.sandbox.dom.find('#delete-contacts').length && this.sandbox.dom.prop('#delete-contacts', 'checked');
                        this.sandbox.emit('husky.dialog.hide');

                        // call callback function
                        if (!!callbackFunction) {
                            callbackFunction(true, deleteContacts);
                        }
                    }.bind(this),
                    cancel: function() {
                        this.sandbox.emit('husky.dialog.hide');

                        // call callback function
                        if (!!callbackFunction) {
                            callbackFunction(false);
                        }
                    }.bind(this)
                }
            }, params.templateType);
        },

        template: {
            dependencyListContacts: function(contacts) {
                var list = "<% _.each(contacts, function(contact) { %> <li><%= contact.firstName %> <%= contact.lastName %></li> <% }); %>";
                return this.sandbox.template.parse(list, {contacts: contacts});
            },
            dependencyListAccounts: function(accounts) {
                var list = "<% _.each(accounts, function(account) { %> <li><%= account.name %></li> <% }); %>";
                return this.sandbox.template.parse(list, {accounts: accounts});
            }
        }

    };
});
