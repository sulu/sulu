/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalmodel', 'mvc/hasmany', './userRole'], function(RelationalModel, HasMany, UserRole) {

    'use strict';

    return new RelationalModel({

        urlRoot: '/security/api/users',

        defaults:  {
                username: '',
                password: '',
                userRoles: []

        }, relations: [
            {
                type: HasMany,
                key: 'userRoles',
                relatedModel: UserRole
            }
        ]
    });
});