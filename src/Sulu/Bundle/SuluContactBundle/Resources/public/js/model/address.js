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
    'sulucontact/model/country',
    'sulucontact/model/addressType'], function (BackboneRelational, Country, AddressType) {
    return Backbone.RelationalModel.extend({
        urlRoot: '/contact/api/addresses',
        defaults: {
            id: null,
            street: '',
            number: '',
            addition: '',
            zip: '',
            city: '',
            state: '',
            country: null,
            addressType: null
        }, relations: [
            {
                type: Backbone.HasOne,
                key: 'country',
                relatedModel: 'Country'
            },
            {
                type: Backbone.HasOne,
                key: 'addressType',
                relatedModel: 'AddressType'
            }
        ]
    });
});
