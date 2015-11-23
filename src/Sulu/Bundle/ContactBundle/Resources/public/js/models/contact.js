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
    'mvc/hasone',
    'sulucontact/models/account',
    'sulucontact/models/email',
    'sulucontact/models/phone',
    'sulucontact/models/address',
    'sulucontact/models/note',
    'sulucontact/models/accountContact',
    'sulucontact/models/contactMedia',
    'sulucontact/models/bankAccount',
    'sulucategory/model/category'
], function(RelationalModel, HasMany, HasOne, Account, Email, Phone, Address, Note, AccountContact, Media, BankAccount, Category) {

    'use strict';

    return RelationalModel({
        urlRoot: '/admin/api/contacts',
        defaults: function() {
            return {
                id: null,
                firstName: '',
                middleName: '',
                lastName: '',
                birthday: '',
                title: '',
                position: '',
                account: null,
                emails: [],
                accountContacts: [],
                phones: [],
                notes: [],
                addresses: [],
                formOfAddress: '',
                salutation: '',
                medias: [],
                bankAccount: [],
                categories: []
            };
        }, relations: [
            {
                type: HasOne,
                key: 'account',
                relatedModel: Account
            },
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
                key: 'accountContacts',
                relatedModel: AccountContact
            },
            {
                type: HasMany,
                key: 'notes',
                relatedModel: Note
            },
            {
                type: HasMany,
                key: 'medias',
                relatedModel: Media
            },
            {
                type: HasMany,
                key: 'bankAccounts',
                relatedModel: BankAccount
            },
            {
                type: HasMany,
                key: 'categories',
                relatedModel: Category
            }
        ]
    });
});
