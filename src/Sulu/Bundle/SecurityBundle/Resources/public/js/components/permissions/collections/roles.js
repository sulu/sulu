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

    return new Collection({

        model: Role,

        url: '/admin/api/roles',

        parse: function(resp) {
            return resp._embedded.roles;
        },

        save: function(sandbox, roles) {

            sandbox.util.ajax({

                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },

                type: "PATCH",
                url: this.url(),
                data: JSON.stringify(roles),

                success: function() {
                    sandbox.logger.log("patch successful");
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    sandbox.logger.log("error during patch: " + textStatus, errorThrown);
                },
                complete: function() {
                    sandbox.logger.log("completed patch");
                }

            });
        }
    });
});
