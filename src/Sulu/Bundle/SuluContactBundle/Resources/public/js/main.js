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
        sulucontact: '../../sulucontact/js'
    }
});

define({

    name: "Sulu Contact Bundle",

    initialize: function (app) {
        var sandbox = app.sandbox;

        app.components.addSource('sulucontact', '/bundles/sulucontact/js/components');


        // list all contacts
        sandbox.mvc.routes.push({
            route: 'contacts/contacts',
            callback: function(){
                this.html('<div data-aura-component="contacts@sulucontact" data-aura-display="list"/>');
            }
        });

        // show form for new contacts
        sandbox.mvc.routes.push({
            route: 'contacts/contacts/add',
            callback: function(){
                this.html('<div data-aura-component="contacts@sulucontact" data-aura-display="form"/>');
            }
        });

        // show form for editing a contact
        sandbox.mvc.routes.push({
            route: 'contacts/contacts/edit::id',
            callback: function(id){
                this.html(
                    '<div data-aura-component="contacts@sulucontact" data-aura-display="form" data-aura-id="' +id + '"/>'
                );
            }
        });

        // list all accounts
        sandbox.mvc.routes.push({
            route: 'contacts/accounts',
            callback: function(){
                this.html('<div data-aura-component="accounts@sulucontact" data-aura-display="list"/>');
            }
        });

        //show for a new account
        sandbox.mvc.routes.push({
            route: 'contacts/accounts/add',
            callback: function(){
                this.html('<div data-aura-component="accounts@sulucontact" data-aura-display="form"/>');
            }
        });

        //show for for editing an account
        sandbox.mvc.routes.push({
            route: 'contacts/accounts/edit::id',
            callback: function(id){
                this.html(
                    '<div data-aura-component="accounts@sulucontact" data-aura-display="form" data-aura-id="' +id + '"/>'
                );
            }
        });
    }
});
