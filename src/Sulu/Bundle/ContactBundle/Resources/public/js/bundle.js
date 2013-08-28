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
        // list all contacts
        Router.route('contacts/people', 'contacts:people:list', function() {
            require(['sulucontact/controller/contact/list'], function(List) {
                new List({
                    el: App.$content
                });
            });
        });

        // show form for new translation package
        Router.route('contacts/people/add', 'contacts:people:form', function() {
            require(['sulucontact/controller/contact/form'], function(Form) {
                new Form({
                    el: App.$content
                });
            });
        });

        // show form for editing a contact
        Router.route('contacts/people/edit::id', 'contacts:people:form:id', function(id) {
            require(['sulucontact/controller/contact/form'], function(Form) {
                new Form({
                    el: App.$content,
                    id: id
                })
            });
        });

        // list all accounts
        Router.route('contacts/accounts', 'contacts:accounts:list', function() {
            console.log('list all accounts message');
        });
    };

    return {
        initialize: initialize
    }
});