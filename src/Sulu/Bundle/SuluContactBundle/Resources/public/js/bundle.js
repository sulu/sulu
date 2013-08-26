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
        // show form for new translation package
        Router.route('contacts/people/add', 'contact:form', function() {
            require(['sulucontact/controller/contact/form'], function(Form) {
                new Form({
                    el: App.$content
                });
            });
        });
    };

    return {
        initialize: initialize
    }
});