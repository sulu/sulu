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
        'type/bic-input': '../../sulucontact/js/validation/types/bic-input',
        'type/vat-input': '../../sulucontact/js/validation/types/vat-input',
        'type/iban-input': '../../sulucontact/js/validation/types/iban-input',
        'contactsutil/delete-dialog': '../../sulucontact/js/components/contacts/util/delete-dialog',
        'accountsutil/delete-dialog': '../../sulucontact/js/components/accounts/util/delete-dialog',

        'extensions/iban': '../../sulucontact/js/extensions/iban',
        'vendor/iban-converter':'../../sulucontact/js/vendor/iban-converter/iban',

        'services/sulucontact/contact-manager': '../../sulucontact/js/services/contact-manager',
        'services/sulucontact/account-manager': '../../sulucontact/js/services/account-manager',
        'services/sulucontact/account-router': '../../sulucontact/js/services/account-router',
        'services/sulucontact/contact-router': '../../sulucontact/js/services/contact-router',
        'services/sulucontact/account-delete-dialog': '../../sulucontact/js/services/account-delete-dialog',
        'services/sulucontact/contact-delete-dialog': '../../sulucontact/js/services/contact-delete-dialog'
    },

    shim: {
        'vendor/iban-converter': {
            exports: 'IBAN'
        }
    }
});

define(['config', 'extensions/iban'], function(Config, IbanExtension) {

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
                routeToList: 'contacts/contacts'
            });

            Config.set('suluresource.filters.type.accounts', {
                routeToList: 'contacts/accounts'
            });

            app.components.addSource('sulucontact', '/bundles/sulucontact/js/components');

            // list all contacts
            sandbox.mvc.routes.push({
                route: 'contacts/contacts',
                callback: function() {
                    return '<div data-aura-component="contacts/list@sulucontact"/>';
                }
            });

            // show form for new contacts
            sandbox.mvc.routes.push({
                route: 'contacts/contacts/add',
                callback: function() {
                    return '<div data-aura-component="contacts/edit@sulucontact"/>';
                }
            });

            // show form for editing a contact
            sandbox.mvc.routes.push({
                route: 'contacts/contacts/edit::id/:content',
                callback: function(id) {
                    return '<div data-aura-component="contacts/edit@sulucontact" data-aura-id="' + id + '"/>';
                }
            });

            // list all accounts
            sandbox.mvc.routes.push({
                route: 'contacts/accounts',
                callback: function() {
                    return '<div data-aura-component="accounts/list@sulucontact"/>';
                }
            });

            //show for a new account
            sandbox.mvc.routes.push({
                route: 'contacts/accounts/add',
                callback: function() {
                    return '<div data-aura-component="accounts/edit@sulucontact"/>';
                }
            });

            //show for for editing an account
            sandbox.mvc.routes.push({
                route: 'contacts/accounts/edit::id/:content',
                callback: function(id) {
                    return '<div data-aura-component="accounts/edit@sulucontact" data-aura-id="' + id + '"/>';
                }
            });
        }
    };
});
