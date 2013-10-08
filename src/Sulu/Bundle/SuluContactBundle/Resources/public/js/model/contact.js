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
    'sulucontact/model/account',
    'sulucontact/model/email',
    'sulucontact/model/phone',
    'sulucontact/model/address'
], function(RelationalModel, HasMany, HasOne, Account, Email, Phone, Address) {
    return RelationalModel({
        urlRoot: '/contact/api/contacts',
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
                phones: [],
                addresses: []
            }
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
            }
        ]
    });
});
