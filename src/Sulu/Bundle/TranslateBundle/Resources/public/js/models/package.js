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
    'mvc/hasmany',
    'sulutranslate/models/catalogue'
], function(relationalModel, HasMany, Catalogue) {

    'use strict';

    return relationalModel({
        urlRoot: '/admin/api/packages',
        idAttribute: 'id',
        defaults: {
            id: null,
            name: '',
            catalogues: [],
            codes: []
        },
        relations: [
            {
                type: HasMany,
                key: 'catalogues',
                relatedModel: Catalogue
            }
        ]
    });

});
