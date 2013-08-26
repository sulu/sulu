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
    'sulucontact/model/address'], function (BackboneRelational, Account, Email, Phone, Address) {
    return Backbone.RelationalModel.extend({
        urlRoot: '/contact/api/contacts',
        defaults: {
            id: null,
            fname: '',
            lname: '',
            title: '',
            position: '',
            Account: null,
            emails: [],
            phones: []
        }, relations: [
            {
                type: Backbone.HasOne,
                key: 'account',
                relatedModel: 'Account'
            },
            {
                type: Backbone.HasMany,
                key: 'emails',
                relatedModel: 'Email'
            },
            {
                type: Backbone.HasMany,
                key: 'phones',
                relatedModel: 'Phone'
            },
            {
                type: Backbone.HasMany,
                key: 'addresses',
                relatedModel: 'Address'
            }
        ]
    });
});
