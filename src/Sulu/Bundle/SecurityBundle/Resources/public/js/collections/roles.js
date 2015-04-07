/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/collection',
    'sulusecurity/models/role'
], function(Collection, Role) {

    'use strict';

    return Collection({

        model: Role,

        url: '/admin/api/roles',

        parse: function(response) {
            return response._embedded.roles;
        }
    });
});
