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

        app.components.addSource('sulucontact', '/bundles/sulucontact/js/aura_components');

        // list all contacts
        sandbox.mvc.routes.push({
                route: 'contacts/people',
                components: [
                    {
                        name: 'contact/list@sulucontact',
                        options: { el: '#content' }
                    }
                ]
            }
        );

        // show form for new contacts
        sandbox.mvc.routes.push({
                route: 'contacts/people/add',
                components: [
                    {
                        name: 'contact/form@sulucontact',
                        options: { el: '#content' }
                    }
                ]
            }
        );

        // show form for editing a contact
        sandbox.mvc.routes.push({
                route: 'contacts/people/edit::id',
                components: [
                    {
                        name: 'contact/form@sulucontact',
                        options: { el: '#content' }
                    }
                ]
            }
        );

        // list all accounts
        sandbox.mvc.routes.push({
                route: 'contacts/companies',
                components: [
                    {
                        name: 'account/list@sulucontact',
                        options: { el: '#content' }
                    }
                ]
            }
        );

        //show for a new account
        sandbox.mvc.routes.push({
                route: 'contacts/companies/add',
                components: [
                    {
                        name: 'account/form@sulucontact',
                        options: { el: '#content' }
                    }
                ]
            }
        );

        //show for for editing an account
        sandbox.mvc.routes.push({
                route: 'contacts/companies/edit::id',
                components: [
                    {
                        name: 'account/form@sulucontact',
                        options: { el: '#content' }
                    }
                ]
            }
        );
    }
});
