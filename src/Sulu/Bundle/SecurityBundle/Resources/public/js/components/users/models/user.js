/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalmodel', 'mvc/hasmany', './userRole', 'sulucontact/models/contact', 'mvc/hasone'
], function(RelationalModel, HasMany, UserRole, Contact, HasOne) {

    'use strict';

    return new RelationalModel({

        urlRoot: '/admin/api/users',

        defaults: {
            username: '',
            password: '',
            email: '',
            locale: 'en',
            contact: [],
            userRoles: []

        }, relations: [
            {
                type: HasMany,
                key: 'userRoles',
                relatedModel: UserRole
            },
            {
                type: HasOne,
                key: 'contact',
                relatedModel: Contact
            }
        ]
    });
});
