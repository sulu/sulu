/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backbonerelational', 'sulutranslate/model/code'], function(BackboneRelational, Translation) {
    return Backbone.RelationalModel.extend({
        urlRoot: '/translate/api/codes',
        defaults: {
            id: null,
            name: '',
            translations: []
        },
        relations: [
            {
                type: Backbone.HasMany,
                key: 'translations',
                relatedModel: Translation
            }
        ]
    });
});
