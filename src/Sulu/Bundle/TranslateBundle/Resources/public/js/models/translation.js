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
    'sulutranslate/models/catalogue',
    'sulutranslate/models/code',
    'mvc/hasone'
], function(relationalModel, Catalogue, Code, HasOne) {

    'use strict';

    return relationalModel({
        urlRoot: '/admin/api/translation',
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
        ]
    });
});
