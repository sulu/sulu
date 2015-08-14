/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalmodel'], function(RelationalModel) {
    return RelationalModel({
        urlRoot: '/admin/api/account/categories',
        defaults: {
            id: null,
            category: ''
        }
    });
});
