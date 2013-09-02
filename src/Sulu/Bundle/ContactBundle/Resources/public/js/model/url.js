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
    'sulucontact/model/urlType'
], function (BackboneRelational, UrlType) {
    return Backbone.RelationalModel.extend({
        defaults: {
            id: null,
            url: '',
            urlType: null
        }, relations: [
            {
                type: Backbone.HasOne,
                key: 'urlType',
                relatedModel: UrlType
            }
        ]
    });
});
