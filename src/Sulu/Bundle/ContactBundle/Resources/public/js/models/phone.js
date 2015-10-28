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
    'sulucontact/models/phoneType'
], function(RelationalModel, HasOne, PhoneType) {
    return RelationalModel({
        urlRoot: '',
        defaults: {
            id: null,
            phone: '',
            phoneType: null
        }, relations: [
            {
                type: HasOne,
                key: 'phoneType',
                relatedModel: PhoneType
            }
        ]
    });
});
