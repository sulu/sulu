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
    'sulucontact/model/phoneType'
], function (BackboneRelational, PhoneType) {
    return Backbone.RelationalModel.extend({
        urlRoot: '/contact/api/phones',
        defaults: {
            id: null,
            phone: '',
            phoneType: null
        }, relations: [
            {
                type: Backbone.HasOne,
                key: 'phoneType',
                relatedModel: PhoneType
            }
        ]
    });
});
