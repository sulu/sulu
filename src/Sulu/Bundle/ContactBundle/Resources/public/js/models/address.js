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
    'mvc/hasone',
    'sulucontact/models/country',
    'sulucontact/models/addressType'
], function(RelationalModel, HasOne, Country, AddressType) {
    return RelationalModel({
        urlRoot: '',
        defaults: {
            id: null,
            street: '',
            number: '',
            addition: '',
            zip: '',
            city: '',
            state: '',
            country: null,
            addressType: null,
            primaryAddress: null,
            billingAddress: null,
            deliveryAddress: null,
            postboxNumber: null,
            postboxPostcode: null,
            postboxCity: null
        }, relations: [
            {
                type: HasOne,
                key: 'country',
                relatedModel: Country
            },
            {
                type: HasOne,
                key: 'addressType',
                relatedModel: AddressType
            }
        ]
    });
});
