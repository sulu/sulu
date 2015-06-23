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
        'type/vat-input': '../../sulucontact/js/input-type/vat-input',
        'contactsutil/delete-dialog': '../../sulucontact/js/components/contacts/util/delete-dialog',
        'accountsutil/delete-dialog': '../../sulucontact/js/components/accounts/util/delete-dialog',

        'aura_extensions/iban': '../../sulucontact/js/aura_extensions/iban',
        'vendor/iban-converter':'../../sulucontact/js/vendor/iban-converter/iban',
        'type/iban-input': '../../sulucontact/js/input-type/iban-input',
    },
    shim: {
        'vendor/iban-converter': {
            exports: 'IBAN'
        }
    }
});

define(['config', 'aura_extensions/iban'], function(Config, IbanExtension) {

    'use strict';

    return {

        name: "Sulu Contact Bundle",

        initialize: function(app) {

            IbanExtension.initialize(app);

            var sandbox = app.sandbox;

            sandbox.urlManager.setUrl('contact', 'contacts/contacts/edit:<%= id %>/details');
            sandbox.urlManager.setUrl('account', 'contacts/accounts/edit:<%= id %>/details');

            Config.set('sulucontact.components.autocomplete.default.contact', {
                remoteUrl: '/admin/api/contacts?searchFields=id,fullName&flat=true&fields=id,fullName&limit=25',
                getParameter: 'search',
                resultKey: 'contacts',
                valueKey: 'fullName',
                value: '',
                instanceName: 'contacts',
                noNewValues: true,
                limit: 25,
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
                remoteUrl: '/admin/api/accounts?searchFields=name,number&flat=true&fields=id,number,name,corporation&limit=25',
                resultKey: 'accounts',
                getParameter: 'search',
                valueKey: 'name',
                value: '',
                instanceName: 'accounts',
                noNewValues: true,
                limit: 25,
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

            // filter integration
            Config.set('suluresource.filters.type.contacts', {
                breadCrumb: [
                    {title: 'navigation.contacts'},
                    {title: 'contact.contacts.title'}
                ],
                fields: 'admin/api/contacts/fields'
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

            //show for a new account
            sandbox.mvc.routes.push({
                route: 'contacts/accounts/add',
                callback: function() {
                    this.html(
                        '<div data-aura-component="accounts/components/content@sulucontact"/>'
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
