/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulucontact/account-manager',
    'services/husky/util',
    'services/husky/mediator',
    'services/husky/translator'
    ], function(AccountManager, util, mediator, translator) {

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
        },

        template = {
            dependencyListContacts: function(contacts) {
                var list = "<% _.each(contacts, function(contact) { %> <li><%= contact.firstName %> <%= contact.lastName %></li> <% }); %>";
                return util.template(list, {contacts: contacts});
            },
            dependencyListAccounts: function(accounts) {
                var list = "<% _.each(accounts, function(account) { %> <li><%= account.name %></li> <% }); %>";
                return util.template(list, {accounts: accounts});
            }
        },

        renderConfirmSingleDeleteDialog = function(deleteInfo, okCallback) {
            var content = 'contact.accounts.delete.desc',
                furtherChildren,
                furtherContacts,
                overlayType = 'show-warning',
                title = util.capitalizeFirstLetter(translator.translate('public.delete')) + '?',
                okCallbackWrapper = function() {
                    var deleteContacts = !!($('#overlay-checkbox').length && $('#overlay-checkbox').prop('checked'));
                    okCallback(deleteContacts);
                };

            // sub-account exists => deletion is not allowed
            if (parseInt(deleteInfo.numChildren, 10) > 0) {

                furtherChildren = deleteInfo.numChildren - deleteInfo.children.length;
                overlayType = 'show-error';
                title = 'sulu.overlay.error';
                okCallbackWrapper = undefined;
                // parse sub-account template
                content = util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: translator.translate('contact.accounts.delete.sub-found'),
                    list: template.dependencyListAccounts( deleteInfo.children),
                    numChildren: parseInt(deleteInfo.numChildren, 10),
                    andMore: util.template(translator.translate('public.and-number-more'), {number: furtherChildren}),
                    description: translator.translate('contact.accounts.delete.sub-found-desc')
                });
            }

            // related contacts exist => show checkbox
            else if (parseInt(deleteInfo.numContacts, 10) > 0) {
                furtherContacts = deleteInfo.numContacts - deleteInfo.contacts.length;

                // create message
                content = util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: translator.translate('contact.accounts.delete.contacts-found'),
                    list: template.dependencyListContacts(deleteInfo.contacts),
                    numChildren: parseInt(deleteInfo.numContacts, 10),
                    andMore: util.template(translator.translate('public.and-number-more'), {number: furtherContacts}),
                    description: translator.translate('contact.accounts.delete.contacts-question'),
                    checkboxText: util.template(translator.translate('contact.accounts.delete.contacts-checkbox'), {number: parseInt(deleteInfo.numContacts, 10)})
                });
            }

            // show dialog
            mediator.emit('sulu.overlay.' + overlayType,
                title,
                content,
                null,
                okCallbackWrapper,
                {
                    okDefaultText: 'public.delete'
                }
            );
        },

        renderConfirmMultipleDeleteDialog = function(deleteInfo, okCallback) {
            var content = 'contact.accounts.delete.desc',
                title = 'sulu.overlay.be-careful',
                overlayType = 'show-warning',
                okCallbackWrapper = function() {
                    var deleteContacts = !!($('#overlay-checkbox').length && $('#overlay-checkbox').prop('checked'));
                    okCallback(deleteContacts);
                };

            // sub-account exists => deletion is not allowed
            if (parseInt(deleteInfo.numChildren, 10) > 0) {
                overlayType = 'show-error';
                title = 'sulu.overlay.error';
                okCallbackWrapper = undefined;
                content = util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: translator.translate('contact.accounts.delete.sub-found'),
                    description: translator.translate('contact.accounts.delete.sub-found-desc')
                });
            }
            // related contacts exist => show checkbox
            else if (parseInt(deleteInfo.numContacts, 10) > 0) {
                // create message
                content = util.template(templates.dialogEntityFoundTemplate, {
                    foundMessage: translator.translate('contact.accounts.delete.contacts-found'),
                    numChildren: parseInt(deleteInfo.numContacts, 10),
                    description: translator.translate('contact.accounts.delete.contacts-question'),
                    checkboxText: util.template(translator.translate('contact.accounts.delete.contacts-checkbox'), {number: parseInt(deleteInfo.numContacts, 10)})
                });
            }

            // show dialog
            mediator.emit('sulu.overlay.' + overlayType,
                title,
                content,
                null,
                okCallbackWrapper,
                {
                    okDefaultText: 'public.ok'
                }
            );
        };

    return {
        /**
         * Show account confirm-delete dialog
         * @param ids accounts which are deleted, if dialog is confirmed
         * @param okCallback function which is executed, if dialog is confirmed
         */
        showDialog: function(ids, okCallback) {
            if (!$.isArray(ids)){
                ids = [ids]; //enable integer input
            }

            if (ids.length === 0) {
                // no account selected, do not show dialog
                return;
            } else if (ids.length === 1) {
                // if only one account was selected - get related sub-companies and contacts (and show the first 3 ones)
                AccountManager.loadDeleteInfo(ids[0]).then(function(deleteInfo) {
                    renderConfirmSingleDeleteDialog(deleteInfo, okCallback);
                });
            } else {
                // if multiple accounts were selected, get related sub-companies and show simplified message
                AccountManager.loadMultipleDeleteInfo(ids).then(function(deleteInfo) {
                    renderConfirmMultipleDeleteDialog(deleteInfo, okCallback);
                });
            }
        }
    };
});
