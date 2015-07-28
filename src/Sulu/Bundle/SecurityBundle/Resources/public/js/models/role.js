/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalmodel', 'mvc/hasmany', './permission'], function(RelationalModel, HasMany, Permission) {

    'use strict';

    return RelationalModel({
        urlRoot: '/admin/api/roles',

        defaults: function() {
            return {
                name: '',
                system: '',
                identifier: '',
                permissions: []
            };
        }
        // TODO: fix bug (doesn't save with relations uncommented
        /*, relations: [
            {
                type: HasMany,
                key: 'permissions',
                relatedModel: Permission
            }
        ]*/
    });
});
