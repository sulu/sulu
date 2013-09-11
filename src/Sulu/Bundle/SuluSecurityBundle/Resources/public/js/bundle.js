/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['router'], function (Router) {

    'use strict';

    var initialize = function () {

        // list all roles
        Router.route('settings/roles', 'security:role:list', function() {
            require(['sulusecurity/controller/role/list'], function(List) {
                new List({
                    el: App.$content
                });
            });
        });


    };

    return {
        initialize: initialize
    }
});
