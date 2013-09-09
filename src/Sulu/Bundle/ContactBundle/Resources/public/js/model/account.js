/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'backbonerelational',
    'sulucontact/model/account',
    'sulucontact/model/email',
    'sulucontact/model/phone',
    'sulucontact/model/address',
    'sulucontact/model/url'
], function(BackboneRelational, Account, Email, Phone, Address, Url) {
    return Backbone.RelationalModel.extend({
        urlRoot: '/contact/api/accounts',
        defaults: {
            id: null,
            name: '',
            emails: [],
            phones: [],
            addresses: [],
            urls: []
        }, relations: [
            {
                type: Backbone.HasMany,
                key: 'emails',
                relatedModel: Email
            },
            {
                type: Backbone.HasMany,
                key: 'phones',
                relatedModel: Phone
            },
            {
                type: Backbone.HasMany,
                key: 'addresses',
                relatedModel: Address
            },
            {
                type: Backbone.HasMany,
                key: 'urls',
                relatedModel: Url
            }
        ]
    });
});
