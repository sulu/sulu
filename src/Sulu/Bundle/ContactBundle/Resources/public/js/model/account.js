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
    'sulucontact/model/accountContact',
    'sulucontact/model/bankAccount',
    'sulucontact/model/contact',
    'sulucontact/model/termsOfDelivery',
    'sulucontact/model/termsOfPayment',
    'sulucontact/model/accountMedia',
    'sulucategory/model/category'
], function(RelationalModel, HasMany, Account, Email, Phone, Address, Url, Note, HasOne, AccountContact, BankAccount, Contact, TermsOfDelivery, TermsOfPayment, Media, Category) {

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
                type: HasOne,
                key: 'termsOfDelivery',
                relatedModel: TermsOfDelivery
            },
            {
                type: HasOne,
                key: 'termsOfPayment',
                relatedModel: TermsOfPayment
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
