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
    'sulucontact/models/urlType'
], function(RelationalModel, HasOne, UrlType) {
    return RelationalModel({
        defaults: {
            id: null,
            url: '',
            urlType: null
        }, relations: [
            {
                type: HasOne,
                key: 'urlType',
                relatedModel: UrlType
            }
        ]
    });
});
