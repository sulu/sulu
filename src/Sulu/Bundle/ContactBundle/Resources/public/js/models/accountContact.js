/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalmodel',
    'mvc/hasone',
    'sulucontact/models/account',
    'sulucontact/models/contact'
], function(RelationalModel, HasOne, Account, Contact) {
    return RelationalModel({
        url: function() {
            return '/admin/api/accounts/' + this.account.id + '/contacts/' + this.contact.id;
        },

        initialize: function(options) {
            this.account = options.account;
            this.contact = options.contact;
        },

        defaults: {
            id: null,
            contact: null,
            account: null,
            main: null,
            position: ''
        }, relations: [
            {
                type: HasOne,
                key: 'account',
                relatedModel: Account
            },
            {
                type: HasOne,
                key: 'contact',
                relatedModel: Contact
            }
        ]
    });
});
