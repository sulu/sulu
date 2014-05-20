/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        sulucontact: '../../sulucontact/js',
        'accountsutil/header': '../../sulucontact/js/components/accounts/util/header'
    }
});

define({

    name: "Sulu Contact Bundle",

    initialize: function(app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('sulucontact', '/bundles/sulucontact/js/components');


        // list all contacts
        sandbox.mvc.routes.push({
            route: 'contacts/contacts',
            callback: function() {
                this.html('<div data-aura-component="contacts@sulucontact" data-aura-display="list"/>');
            }
        });

        // show form for new contacts
        sandbox.mvc.routes.push({
            route: 'contacts/contacts/add',
            callback: function(content) {
                this.html(
                    '<div data-aura-component="contacts/components/content@sulucontact" data-aura-display="content" data-aura-content="form" />'
                );
            }
        });

        // show form for editing a contact
        sandbox.mvc.routes.push({
            route: 'contacts/contacts/edit::id/:content',
            callback: function(id, content) {
                this.html(
                    '<div data-aura-component="contacts/components/content@sulucontact" data-aura-display="content" data-aura-content="' + content + '" data-aura-id="' + id + '"/>'
                );
            }
        });

        // list all accounts
        sandbox.mvc.routes.push({
            route: 'contacts/accounts',
            callback: function() {
                this.html('<div data-aura-component="accounts@sulucontact" data-aura-display="list"/>');
            }
        });

        // list all accounts
        sandbox.mvc.routes.push({
            route: 'contacts/accounts/type::typeid',
            callback: function(accountType) {
                this.html('<div data-aura-component="accounts@sulucontact" data-aura-display="list" data-aura-account-type="' + accountType + '" />');
            }
        });

        //show for a new account
        sandbox.mvc.routes.push({
            route: 'contacts/accounts/add',
            callback: function() {
                this.html(
                    '<div data-aura-component="accounts/components/content@sulucontact"/>'
                );
            }
        });


        //show for a new account
        sandbox.mvc.routes.push({
            route: 'contacts/accounts/add/type::id',
            callback: function(accountType) {
                this.html(
                    '<div data-aura-component="accounts/components/content@sulucontact" data-aura-account-type="' + accountType + '" />'
                );
            }
        });

        //show for for editing an account
        sandbox.mvc.routes.push({
            route: 'contacts/accounts/edit::id/:content',
            callback: function(id, content) {
                this.html(
                    '<div data-aura-component="accounts/components/content@sulucontact" data-aura-content="' + content + '" data-aura-id="' + id + '"/>'
                );
            }
        });

    }
});
