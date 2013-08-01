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
        Router.route('settings/translate', 'translate:package:list', function () {
            require(['sulutranslate/controller/package/list'], function (List) {
                new List({
                    el: App.$content
                });
            });
        });

        Router.route('settings/translate/form', 'translate:package:form', function () {
            require(['sulutranslate/controller/package/form'], function (Form) {
                new Form({
                    el: App.$content
                });
            });
        });

        Router.route('settings/translate/form/:id', 'translate:package:form:id', function (id) {
            require(['sulutranslate/controller/package/form'], function (Form) {
                new Form({
                    el: App.$content,
                    id: id
                })
            });
        });
    };

    return {
        initialize: initialize
    }
});