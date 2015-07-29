/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalmodel', 'mvc/hasmany', './permission'], function(relationalModel, HasMany, Permission) {

    'use strict';

    return relationalModel({
        urlRoot: '/admin/api/roles',

        defaults: function() {
            return {
                name: '',
                system: '',
                identifier: '',
                permissions: []
            };
        }, relations: [
            {
                type: HasMany,
                key: 'permissions',
                relatedModel: Permission
            }
        ]
    });
});
