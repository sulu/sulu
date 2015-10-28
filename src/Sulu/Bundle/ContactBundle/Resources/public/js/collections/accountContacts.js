/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/collection',
    'sulucontact/models/contact'
], function (Collection, Contact) {
    'use strict';

    return new Collection({

        model: Contact,

        flatResponse: false,

        accountId: null,

        setAccountId: function (accountId) {
            this.accountId = accountId;
        },

        parse: function (response) {
            if (!!response._embedded) {
                return response._embedded.contacts;
            } else {
                return response;
            }
        },

        initialize: function(options) {
            this.setAccountId(options.accountId);
        },

        url: function () {
            return '/admin/api/accounts/' + this.accountId
                + '/contacts'
                + '?flat=' + this.flatResponse;
        }
    });
});
