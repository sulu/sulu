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
    'sulutranslate/model/catalogue',
    'sulutranslate/components/translations/models/code',
    'mvc/hasone'], function(RelationalModel, Catalogue, Code, HasOne) {
    return RelationalModel({
        urlRoot: '/translate/api/translation',
        defaults: {
            id: null,
            value: '',
            code: null,
            catalogue: null
        },
        relations: [
            {
                type: HasOne,
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
