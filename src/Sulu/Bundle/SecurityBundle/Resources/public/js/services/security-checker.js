/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    return {
        /**
         * Checks if the current data object grants the given permission for the current user.
         * @param data {object} The data object containing the permissions
         * @param permission {string} The type of permission
         * @returns {boolean} True if the system grants the given permission, otherwise false
         */
        hasPermission: function(data, permission) {
            if (!data.hasOwnProperty('_permissions')) {
                return true;
            }

            return !!(data._permissions.hasOwnProperty(permission) && !!data._permissions[permission]);
        }
    };
});
