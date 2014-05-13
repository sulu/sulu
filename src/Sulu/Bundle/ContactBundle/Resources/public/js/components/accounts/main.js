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

    var templates = {
        entityFoundTemplate: [
            '<p><%= foundMessage %>:</p>',
            '<% if (typeof list !== "undefined") { %>',
            '<ul><%= list %></ul>',
            '<% } %>',
            '<% if (typeof numChildren !== "undefined" && numChildren > 3 && typeof andMore !== "undefined") { %>',
            '<p><%= andMore %></p>',
            '<% } %>',
            '<p><%= description %></p>',
            '<% if (typeof checkboxText !== "undefined") { %>',
            '<p>',
            '   <label for="overlay-checkbox">',
            '       <div class="custom-checkbox">',
            '           <input type="checkbox" id="overlay-checkbox" class="form-element" />',
            '           <span class="icon"></span>',
            '       </div>',
            '       <%= checkboxText %>',
            '</label>',
            '</p>',
            '<% } %>'
        ].join('')

    };

    return {

        initialize: function() {
            this.bindCustomEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else if (this.options.display === 'contacts') {
                this.renderContacts();
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
                this.sandbox.emit('sulu.router.navigate', 'contacts/accounts' + typeString, !noReload ? true : false, true);
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
                // TODO: translations
                this.sandbox.emit('sulu.overlay.show-error', 'sulu.overlay.delete-no-items');
                return;
            }
            this.showDeleteConfirmation(ids, function(wasConfirmed, removeContacts) {
                if (wasConfirmed) {
                    // TODO: show loading icon
                    ids.forEach(function(id) {
                        var account = new Account({id: id});
                        account.destroy({
                            data: {removeContacts: !!removeContacts},
                            processData: true,

                            success: function() {
                                this.sandbox.emit('husky.datagrid.row.remove', id);
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

        renderContacts: function() {

            var $form = this.sandbox.dom.createElement('<div id="accounts-contacts-container"/>');
            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                this.account.fetch({
                    // pass include parameter when fetching
//                    data: {'include': 'contacts'},
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/contacts@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                    }.bind(this)
                });
            }
        },

        showDeleteConfirmation: function(ids, callbackFunction) {
            if (ids.length === 0) {
                return;
            } else if (ids.length === 1) {
                // if only one account was selected - get related sub-companies and contacts (and show the first 3 ones)
                this.confirmSingleDeleteDialog(ids[0], callbackFunction);
            } else {
                // if multiple accounts were selected, get related sub-companies and show simplified message
                this.confirmMultipleDeleteDialog(ids, callbackFunction);
            }
        },

        confirmSingleDeleteDialog: function(id, callbackFunction) {
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

            var content = 'sulu.accounts.delete.desc',
                overlayType = 'show-warning',
                okCallback = function() {
                    var deleteContacts = this.sandbox.dom.find('#overlay-checkbox').length && this.sandbox.dom.prop('#overlay-checkbox', 'checked');
                    callbackFunction.call(this, true, deleteContacts);
                }.bind(this);

            // sub-account exists => deletion is not allowed
            if (parseInt(values.numChildren, 10) > 0) {
                overlayType = 'show-error';
                okCallback = undefined;
                // parse sub-account template
                content = this.sandbox.util.template(templates.entityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.sub-found'),
                    list: this.template.dependencyListAccounts.call(this, values.children),
                    numChildren: parseInt(values.numChildren, 10),
                    andMore: this.sandbox.util.template(this.sandbox.translate('public.and-number-more'), {number: '<strong><%= values.numChildren - values.children.length) %></strong>'}),
                    description: this.sandbox.translate('contact.accounts.delete.sub-found-desc')
                });
            }
            // related contacts exist => show checkbox
            else if (parseInt(values.numContacts, 10) > 0) {
                // create message
                content = this.sandbox.util.template(templates.entityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.contacts-found'),
                    list: this.template.dependencyListContacts.call(this, values.contacts),
                    numChildren: parseInt(values.numContacts, 10),
                    andMore: this.sandbox.util.template(this.sandbox.translate('public.and-number-more'), {number: '<strong><%= values.numContacts - values.contacts.length) %></strong>'}),
                    description: this.sandbox.translate('contact.accounts.delete.contacts-question'),
                    checkboxText: this.sandbox.util.template(this.sandbox.translate('contact.accounts.delete.contacts-checkbox'), {number: parseInt(values.numContacts, 10)})
                });
            }

            // show dialog
            this.sandbox.emit('sulu.overlay.' + overlayType,
                'sulu.overlay.be-careful',
                content,
                callbackFunction.bind(this, false),
                okCallback
            );
        },

        confirmMultipleDeleteDialog: function(ids, callbackFunction) {
            var url = '/admin/api/accounts/multipledeleteinfo';
            this.sandbox.util.ajax({
                headers: {
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
        },

        showConfirmMultipleDeleteDialog: function(values, ids, callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            var content = 'sulu.accounts.delete.desc',
                overlayType = 'show-warning',
                okCallback = function() {
                    var deleteContacts = this.sandbox.dom.find('#delete-contacts').length && this.sandbox.dom.prop('#delete-contacts', 'checked');
                    callbackFunction(true, deleteContacts);
                }.bind(this);


            // sub-account exists => deletion is not allowed
            if (parseInt(values.numChildren, 10) > 0) {
                overlayType = 'show-error';
                okCallback = undefined;
                content = this.sandbox.util.template(templates.entityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.sub-found'),
                    description: this.sandbox.translate('contact.accounts.delete.sub-found-desc')
                });
            }
            // related contacts exist => show checkbox
            else if (parseInt(values.numContacts, 10) > 0) {
                // create message
                content = this.sandbox.util.template(templates.entityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.contacts-found'),
                    numChildren: parseInt(values.numContacts, 10),
                    description: this.sandbox.translate('contact.accounts.delete.contacts-question'),
                    checkboxText: this.sandbox.util.template(this.sandbox.translate('contact.accounts.delete.contacts-checkbox'), {number: parseInt(values.numContacts, 10)})
                });
            }

            // show dialog
            this.sandbox.emit('sulu.overlay.' + overlayType,
                'sulu.overlay.be-careful',
                content,
                callbackFunction.bind(this, false),
                okCallback
            );
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
