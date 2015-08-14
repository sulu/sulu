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
    'sulucontact/models/account',
    'sulucontact/models/email',
    'sulucontact/models/phone',
    'sulucontact/models/address',
    'sulucontact/models/url',
    'sulucontact/models/note',
    'mvc/hasone',
    'sulucontact/models/accountContact',
    'sulucontact/models/bankAccount',
    'sulucontact/models/contact',
    'sulucontact/models/accountMedia',
    'sulucategory/model/category'
], function(RelationalModel, HasMany, Account, Email, Phone, Address, Url, Note, HasOne, AccountContact, BankAccount, Contact, Media, Category) {

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
                mainContact: null,
                medias: [],
                categories: []
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
                key: 'mainContact',
                relatedModel: Contact
            },
            {
                type: HasMany,
                key: 'medias',
                relatedModel: Media
            },
            {
                type: HasMany,
                key: 'categories',
                relatedModel: Category
            }
        ]
    });
});
