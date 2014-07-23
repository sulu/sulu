/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalmodel',
    'mvc/hasmany',
    'sulucontact/model/account',
    'sulucontact/model/email',
    'sulucontact/model/phone',
    'sulucontact/model/address',
    'sulucontact/model/url',
    'sulucontact/model/note',
    'mvc/hasone',
    'sulucontact/model/accountCategory',
    'sulucontact/model/accountContact',
    'sulucontact/model/bankAccount',
    'sulucontact/model/contact'
], function(RelationalModel, HasMany, Account, Email, Phone, Address, Url, Note, HasOne, AccountCategory, AccountContact, BankAccount, Contact) {

    'use strict';
    
    return RelationalModel({
        urlRoot: '/admin/api/accounts',
        defaults: function() {
            return {
                id: null,
                name: '',
                corporation: '',
                emails: [],
                phones: [],
                addresses: [],
                notes: [],
                bankAccount: [],
                urls: [],
                accountContacts: [],
                termsOfPayment: null,
                termsOfDelivery: null,
                responsiblePerson: null,
                mainContact: null
            };
        }, relations: [
            {
                type: HasMany,
                key: 'emails',
                relatedModel: Email
            },
            {
                type: HasMany,
                key: 'phones',
                relatedModel: Phone
            },
            {
                type: HasMany,
                key: 'addresses',
                relatedModel: Address
            },
            {
                type: HasMany,
                key: 'urls',
                relatedModel: Url
            },
            {
                type: HasMany,
                key: 'bankAccounts',
                relatedModel: BankAccount
            },
            {
                type: HasMany,
                key: 'notes',
                relatedModel: Note
            },
            {
                type: HasMany,
                key: 'accountContacts',
                relatedModel: AccountContact
            },
            {
                type: HasOne,
                key: 'accountCategory',
                relatedModel: AccountCategory
            },
            {
                type: HasOne,
                key: 'responsiblePerson',
            },
            {
                key: 'mainContact',
                relatedModel: Contact
            }
        ]
    });
});
