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
            route: 'contacts/people',
            callback: function(){
                this.startComponent({
                    name: 'contacts@sulucontact',
                    options: {
                        el: '#content',
                        display: 'list'
                    }
                });
            }
        });

        // show form for new contacts
        sandbox.mvc.routes.push({
            route: 'contacts/people/add',
            callback: function(){
                this.startComponent({
                    name: 'contacts@sulucontact',
                    options: {
                        el: '#content',
                        display: 'form'
                    }
                });
            }
        });

        // show form for editing a contact
        sandbox.mvc.routes.push({
            route: 'contacts/people/edit::id',
            callback: function(id){
                this.startComponent({
                    name: 'contacts@sulucontact',
                    options: {
                        el: '#content',
                        id: id,
                        display: 'form'
                    }
                });
            }
        });

        // list all accounts
        sandbox.mvc.routes.push({
            route: 'contacts/companies',
            callback: function(){
                this.startComponent({
                    name: 'accounts@sulucontact',
                    options: {
                        el: '#content',
                        display: 'list'
                    }
                });
            }
        });

        //show for a new account
        sandbox.mvc.routes.push({
            route: 'contacts/companies/add',
            callback: function(){
                this.startComponent({
                    name: 'accounts@sulucontact',
                    options: {
                        el: '#content',
                        display: 'form'
                    }
                });
            }
        });

        //show for for editing an account
        sandbox.mvc.routes.push({
            route: 'contacts/companies/edit::id',
            callback: function(id){
                this.startComponent({
                    name: 'accounts@sulucontact',
                    options: {
                        el: '#content',
                        id: id,
                        display: 'form'
                    }
                });
            }
        });
    }
});
