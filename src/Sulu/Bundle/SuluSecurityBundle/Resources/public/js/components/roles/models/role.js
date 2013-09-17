/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalmodel'], function(RelationalModel) {
    return RelationalModel({
        urlRoot: '/security/api/roles',

        defaults: function() {
            return {
                id: null,
                name: '',
                system: ''
            }
        }
    });
});