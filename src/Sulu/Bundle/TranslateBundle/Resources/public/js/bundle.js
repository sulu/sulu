/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['router'], function(Router) {

    'use strict';

    var initialize = function() {
        // list all translation packages
        Router.route('settings/translate', 'translate:package:list', function() {
            require(['sulutranslate/controller/package/list'], function(List) {
                new List({
                    el: App.$content
                });
            });
        });

        // show form for new translation package
        Router.route('settings/translate/add', 'translate:package:form', function() {
            require(['sulutranslate/controller/package/form'], function(Form) {
                new Form({
                    el: App.$content
                });
            });
        });

        // show form for editing a translation package
        Router.route('settings/translate/edit::id/settings', 'translate:package:form:id', function(id) {
            require(['sulutranslate/controller/package/form'], function(Form) {
                new Form({
                    el: App.$content,
                    id: id
                })
            });
        });

        // show form for editing codes
        Router.route('settings/translate/edit::id/details', 'translate:package:form:id:details', function(id) {
            require(['sulutranslate/controller/translation/form'], function(Form) {
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
