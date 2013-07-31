/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app', 'router'], function (App, Router) {

    'use strict';

    var initialize = function () {
        //add routes
        Router.route('settings/translate', 'translate', function () {
            require(['sulutranslate/controller/list'], function (List) {
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