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
    'sulucontact/model/bankAccount'
], function(RelationalModel, HasMany, Account, Email, Phone, Address, Url, Note, HasOne, AccountCategory, BankAccount) {

    return RelationalModel({
        urlRoot: '/admin/api/accounts',
        defaults: function() {
            return {
                id: null,
                name: '',
                emails: [],
                phones: [],
                addresses: [],
                notes: [],
                bankAccount: [],
                urls: [],
                termsOfPayment: null,
                termsOfDelivery: null
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
                type: HasOne,
                key: 'accountCategory',
                relatedModel: AccountCategory
            }
        ]
    });
});
