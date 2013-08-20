/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backbonerelational', 'sulutranslate/model/catalogue'], function(BackboneRelational, Catalogue) {
    return Backbone.RelationalModel.extend({
        urlRoot: '/translate/api/packages',
        defaults: {
            id: null,
            name: '',
            catalogues: []
        },
        relations: [
            {
                type: Backbone.HasMany,
                key: 'catalogues',
                relatedModel: Catalogue
            }
        ]
    });
});
