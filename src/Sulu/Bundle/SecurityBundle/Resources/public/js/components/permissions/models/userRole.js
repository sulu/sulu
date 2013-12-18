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
    'mvc/hasone',
    'sulusecurity/models/role'
], function(relationalModel, HasOne, Role) {

    'use strict';

    return relationalModel({
        urlRoot: '/admin/api/roles',

        defaults: function() {
            return {
                locale: '',
                role: null
            };
        }, relations: [
            {
                type: HasOne,
                key: 'role',
                relatedModel: Role
            }
        ]
    });
});
