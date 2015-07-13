/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['sulucontact/model/account'], function(Account) {
    'use strict';

    var constants = {
            datagridInstanceName: 'accounts'
        },
        templates = {
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
        },

        confirmSingleDeleteDialog = function(id, callbackFunction) {
            var url = '/admin/api/accounts/' + id + '/deleteinfo';

            this.sandbox.util.ajax({
                headers: {
                    'Content-Type': 'application/json'
                },

                context: this,
                type: 'GET',
                url: url,

                success: function(response) {
                    showConfirmSingleDeleteDialog.call(this, response, id, callbackFunction);
                }.bind(this),

                error: function(jqXHR, textStatus, errorThrown) {
                    this.sandbox.logger.error("error during get request: " + textStatus, errorThrown);
                }.bind(this)
            });
        },

        showConfirmSingleDeleteDialog = function(values, id, callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            var content = 'contact.accounts.delete.desc',
                furtherChildren,
                furtherContacts,
                overlayType = 'show-warning',
                title = 'sulu.overlay.be-careful',
                okCallback = function() {
                    var deleteContacts = this.sandbox.dom.find('#overlay-checkbox').length && this.sandbox.dom.prop('#overlay-checkbox', 'checked');
                    callbackFunction.call(this, true, deleteContacts);
                }.bind(this);

            // sub-account exists => deletion is not allowed
            if (parseInt(values.numChildren, 10) > 0) {

                furtherChildren = values.numChildren - values.children.length;
                overlayType = 'show-error';
                title = 'sulu.overlay.error';
                okCallback = undefined;
                // parse sub-account template
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.sub-found'),
                    list: template.dependencyListAccounts.call(this, values.children),
                    numChildren: parseInt(values.numChildren, 10),
                    andMore: this.sandbox.util.template(this.sandbox.translate('public.and-number-more'), {number: furtherChildren}),
                    description: this.sandbox.translate('contact.accounts.delete.sub-found-desc')
                });
            }
            // related contacts exist => show checkbox
            else if (parseInt(values.numContacts, 10) > 0) {

                furtherContacts = values.numContacts - values.contacts.length;

                // create message
                content = this.sandbox.util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: this.sandbox.translate('contact.accounts.delete.contacts-found'),
                    list: template.dependencyListContacts.call(this, values.contacts),
                    numChildren: parseInt(values.numContacts, 10),
                    andMore: this.sandbox.util.template(this.sandbox.translate('public.and-number-more'), {number: furtherContacts}),
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

        template = {
            dependencyListContacts: function(contacts) {
                var list = "<% _.each(contacts, function(contact) { %> <li><%= contact.firstName %> <%= contact.lastName %></li> <% }); %>";
                return this.sandbox.template.parse(list, {contacts: contacts});
            },
            dependencyListAccounts: function(accounts) {
                var list = "<% _.each(accounts, function(account) { %> <li><%= account.name %></li> <% }); %>";
                return this.sandbox.template.parse(list, {accounts: accounts});
            }
        },

        deleteAccount = function(wasConfirmed, removeContacts) {
            if (wasConfirmed) {
                this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                this.account.destroy({
                    data: {removeContacts: !!removeContacts},
                    processData: true,
                    success: function() {
                        if(!!this.recordRemove){
                            this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.record.remove', this.account.get('id'));
                        }
                        this.sandbox.emit('sulu.router.navigate', 'contacts/accounts');
                    }.bind(this)
                });
            }
        },

        showConfirmMultipleDeleteDialog = function(values, ids, callbackFunction) {
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

        deleteAccounts = function(wasConfirmed, removeContacts) {
            if (wasConfirmed) {
                // TODO: show loading icon
                this.ids.forEach(function(id) {
                    var account = Account.findOrCreate({id: id});
                    account.destroy({
                        data: {removeContacts: !!removeContacts},
                        processData: true,

                        success: function() {
                            this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.record.remove', id);
                        }.bind(this)
                    });
                }.bind(this));
            }
        };

    return {

        /**
         * Shows delete dialog for a single account
         * @param sandbox
         * @param account
         * @param id
         * @param recordRemove
         */
        showForSingle: function(sandbox, account, id, recordRemove) {
            if (!!sandbox && !!account && !!id) {
                this.sandbox = sandbox;
                this.account = account;
                this.recordRemove = recordRemove;
                confirmSingleDeleteDialog.call(this, id, deleteAccount.bind(this));
            }
        },

        /**
         * Shows delete dialog for multiple accounts
         * @param sandbox
         * @param ids
         */
        showForMultiple: function(sandbox, ids) {
            if (!!sandbox && !!ids) {
                this.sandbox = sandbox;
                this.ids = ids;

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
                        showConfirmMultipleDeleteDialog.call(this, response, ids, deleteAccounts.bind(this));
                    }.bind(this),

                    error: function(jqXHR, textStatus, errorThrown) {
                        this.sandbox.logger.error("error during get request: " + textStatus, errorThrown);
                    }.bind(this)
                });
            }
        }
    };
});
