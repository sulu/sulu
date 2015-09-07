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
    'sulucontact/models/faxType'
], function(RelationalModel, HasOne, FaxType) {
    return RelationalModel({
        urlRoot: '',
        defaults: {
            id: null,
            fax: '',
            faxType: null
        }, relations: [
            {
                type: HasOne,
                key: 'faxType',
                relatedModel: FaxType
            }
        ]
    });
});
