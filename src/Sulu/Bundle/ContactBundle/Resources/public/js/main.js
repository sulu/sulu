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
        'accountsutil/header': '../../sulucontact/js/components/accounts/util/header',
        'type/bic-input': '../../sulucontact/js/input-type/bic-input',
        'type/vat-input': '../../sulucontact/js/input-type/vat-input'
    }
});

define(['config'], function(Config) {

    'use strict';

    return {

        name: "Sulu Contact Bundle",

        initialize: function(app) {

            var sandbox = app.sandbox;

            Config.set('sulucontact.components.autocomplete.default.contact', {
                remoteUrl: '/admin/api/contacts?searchFields=id,fullName&flat=true&fields=id,fullName',
                getParameter: 'search',
                resultKey: 'contacts',
                valueKey: 'fullName',
                value: '',
                instanceName: 'contacts',
                noNewValues: true,
                fields: [
                    {
                        id: 'id',
                        width: '40px'
                    },
                    {
                        id: 'fullName'
                    }
                ]
            });

            Config.set('sulucontact.components.autocomplete.default.account', {
                remoteUrl: '/admin/api/accounts?searchFields=name,number&flat=true&fields=id,number,name,corporation',
                resultKey: 'accounts',
                getParameter: 'search',
                valueKey: 'name',
                value: '',
                instanceName: 'accounts',
                noNewValues: true,
                fields: [
                    {
                        id: 'number',
                        width: '60px'
                    },
                    {
                        id: 'name',
                        width: '220px'
                    },
                    {
                        id: 'corporation',
                        width: '220px'
                    }
                ]
            });

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
    };
});
