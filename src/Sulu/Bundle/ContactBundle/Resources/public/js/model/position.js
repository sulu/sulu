/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalmodel'], function(RelationalModel) {
    'use strict';
    return RelationalModel({
        urlRoot: '/admin/api/contact/positions',
        defaults: function() {
            return {
                id: null,
                position: ''
            };
        }
    });
});
