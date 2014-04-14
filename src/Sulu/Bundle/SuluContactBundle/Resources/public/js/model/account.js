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
    'sulucontact/model/note'
], function(RelationalModel, HasMany, Account, Email, Phone, Address, Url, Note) {
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
                urls: []
            }
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
                key: 'notes',
                relatedModel: Note
            }
        ]
    });
});
