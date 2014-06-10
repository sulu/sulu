/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontact/model/account',
    'accountsutil/header'
], function(Account, AccountsUtilHeader) {

    'use strict';

    var templates = {
        dialogEntityFoundTemplate: [
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
            this.account = null;
            this.accountType = null;
            this.accountTypes = null;

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm().then(function() {
                    AccountsUtilHeader.setHeader.call(this, this.account, this.options.accountType);
                }.bind(this));
            } else if (this.options.display === 'contacts') {
                this.renderContacts().then(function() {
                    AccountsUtilHeader.setHeader.call(this, this.account, this.options.accountType);
                }.bind(this));
            } else if (this.options.display === 'financials') {
                this.renderFinancials().then(function() {
                    AccountsUtilHeader.setHeader.call(this, this.account, this.options.accountType);
                }.bind(this));
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('sulu.contacts.account.delete', this.del.bind(this));

            // save the current package
            this.sandbox.on('sulu.contacts.accounts.save', this.save.bind(this));

            // wait for navigation events
            this.sandbox.on('sulu.contacts.accounts.load', this.load.bind(this));

            // wait for navigation events
            this.sandbox.on('sulu.contacts.contact.load', this.loadContact.bind(this));

            // add new contact
            this.sandbox.on('sulu.contacts.accounts.new', this.add.bind(this));

            // delete selected contacts
            this.sandbox.on('sulu.contacts.accounts.delete', this.delAccounts.bind(this));

            // saves financial infos
            this.sandbox.on('sulu.contacts.accounts.financials.save', this.saveFinancials.bind(this));

            // load list view
            this.sandbox.on('sulu.contacts.accounts.list', function(type, noReload) {
                var typeString = '';
                if (!!type) {
                    typeString = '/type:' + type;
                }
                this.sandbox.emit('sulu.router.navigate', 'contacts/accounts' + typeString, !noReload ? true : false, true);
            }, this);

            this.sandbox.on('sulu.contacts.account.types', function(data) {
                this.accountType = data.accountType;
                this.accountTypes = data.accountTypes;
            }.bind(this));

            this.sandbox.on('sulu.contacts.account.get.types', function(callback) {
                if (typeof callback === 'function') {
                    callback(this.accountType, this.accountTypes);
                }
            }.bind(this));

            this.sandbox.on('sulu.contacts.account.convert', function(data) {
                this.convertAccount(data);
            }.bind(this));
        },

        /**
         * Converts an account
         */
        convertAccount: function(data) {
            this.confirmConversionDialog(function(wasConfirmed) {
                this.account.set({type: data.id});
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    this.sandbox.util.ajax('/admin/api/accounts/'+this.account.id+'?action=convertAccountType&type='+data.name, {

                        type: 'POST',

                        success: function(response){
                            var model = response;
                            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + model.id + '/details', false);
                            this.sandbox.emit('sulu.header.toolbar.item.enable', 'options-button');

                            // update tabs and breadcrumb
                            this.account.set({type: model.type});
                            AccountsUtilHeader.setHeader.call(this, this.account, this.options.accountType);

                            // update toolbar
                            this.sandbox.emit('sulu.account.type.converted');
                        }.bind(this),

                        error: function(){
                            this.sandbox.logger.log("error while saving profile");
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        /**
         * @var ids - array of ids to delete
         * @var callback - callback function returns true or false if data got deleted
         */
        confirmConversionDialog: function(callbackFunction) {

            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            // show dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'contact.accounts.type.conversion.message',
                callbackFunction.bind(this, false),
                callbackFunction.bind(this, true)
            );
        },

        // show confirmation and delete account
        del: function() {
            this.confirmSingleDeleteDialog(this.options.id, function(wasConfirmed, removeContacts) {
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
            }.bind(this));
        },

        // saves an account
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

        // saves financial infos
        saveFinancials: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            this.account.set(data);
            this.account.save(null, {
                patch: true,
                success: function(response) {
                    var model = response.toJSON();
                    this.sandbox.emit('sulu.contacts.accounts.financials.saved', model);
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

        loadContact: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + id + '/details');
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

        renderFinancials: function() {
            var $form = this.sandbox.dom.createElement('<div id="accounts-form-container"/>'),
                dfd = this.sandbox.data.deferred();
            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                this.account.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/financials@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                        dfd.resolve();
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                        dfd.reject();
                    }.bind(this)
                });
            }
            return dfd.promise();

        },

        renderForm: function() {
            // load data and show form
            this.account = new Account();

            var accTypeId,
                $form = this.sandbox.dom.createElement('<div id="accounts-form-container"/>'),
                dfd = this.sandbox.data.deferred();
            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                //account = this.getModel(this.options.id);
                this.account.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/form@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                        dfd.resolve();
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                        dfd.reject();
                    }.bind(this)
                });
            } else {
                accTypeId = AccountsUtilHeader.getAccountTypeIdByTypeName.call(this, this.options.accountType);
                this.account.set({type: accTypeId});
                this.sandbox.start([
                    {name: 'accounts/components/form@sulucontact', options: { el: $form, data: this.account.toJSON()}}
                ]);
                dfd.resolve();
            }
            return dfd.promise();
        },

        renderContacts: function() {
            var $form = this.sandbox.dom.createElement('<div id="accounts-contacts-container"/>'),
                dfd = this.sandbox.data.deferred();
            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                this.account.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/contacts@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                        dfd.resolve();
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                        dfd.reject();
                    }.bind(this)
                });
            }
            return dfd.promise();
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

            var content = 'contact.accounts.delete.desc',
                overlayType = 'show-warning',
                title = 'sulu.overlay.be-careful',
                okCallback = function() {
                    var deleteContacts = this.sandbox.dom.find('#overlay-checkbox').length && this.sandbox.dom.prop('#overlay-checkbox', 'checked');
                    callbackFunction.call(this, true, deleteContacts);
                }.bind(this);

            // sub-account exists => deletion is not allowed
            if (parseInt(values.numChildren, 10) > 0) {
                overlayType = 'show-error';
                title = 'sulu.overlay.error';
                okCallback = undefined;
                // parse sub-account template
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
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
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
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
                title,
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

            var content = 'contact.accounts.delete.desc',
                title = 'sulu.overlay.be-careful',
                overlayType = 'show-warning',
                okCallback = function() {
                    var deleteContacts = this.sandbox.dom.find('#delete-contacts').length && this.sandbox.dom.prop('#delete-contacts', 'checked');
                    callbackFunction(true, deleteContacts);
                }.bind(this);

            // sub-account exists => deletion is not allowed
            if (parseInt(values.numChildren, 10) > 0) {
                overlayType = 'show-error';
                title = 'sulu.overlay.error';
                okCallback = undefined;
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.sub-found'),
                    description: this.sandbox.translate('contact.accounts.delete.sub-found-desc')
                });
            }
            // related contacts exist => show checkbox
            else if (parseInt(values.numContacts, 10) > 0) {
                // create message
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.contacts-found'),
                    numChildren: parseInt(values.numContacts, 10),
                    description: this.sandbox.translate('contact.accounts.delete.contacts-question'),
                    checkboxText: this.sandbox.util.template(this.sandbox.translate('contact.accounts.delete.contacts-checkbox'), {number: parseInt(values.numContacts, 10)})
                });
            }

            // show dialog
            this.sandbox.emit('sulu.overlay.' + overlayType,
                title,
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
