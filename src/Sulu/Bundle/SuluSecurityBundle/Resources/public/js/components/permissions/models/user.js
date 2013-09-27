/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalmodel', 'mvc/hasmany', './role'], function(RelationalModel, HasMany, Role) {

    'use strict';

    return new RelationalModel({
        urlRoot: '/security/api/users',

        defaults: function() {
            return {
                name: '',
                password: '',
                email: '',
                roles: []
            };
        }, relations: [
            {
                type: HasMany,
                key: 'roles',
                relatedModel: Role
            }
        ]
    });
});