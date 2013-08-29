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
    'sulutranslate/model/catalogue',
    'sulutranslate/model/code'], function(BackboneRelational, Catalogue, Code) {
    return Backbone.RelationalModel.extend({
        urlRoot: '/translate/api/translation',
        defaults: {
            id: null,
            value: '',
            code: null,
            catalogue: null
        },
        relations: [
            {
                type: Backbone.HasOne,
                key: 'catalogue',
                relatedModel: Catalogue
            }
//            },
//            {
//                type: Backbone.HasOne,
//                key: 'code',
//                relatedModel: Code
//            }
        ]
    });
});
