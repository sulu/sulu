/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var instance = null;

    function AccountRouter() {
        this.initialize();
    }

    AccountRouter.prototype = {

        initialize: function() {
            this.sandbox = window.App; // TODO: inject context. find better solution
        },

        /**
         * Navigates to the edit of an account
         * @param id The id of the account to edit
         */
        toEdit: function(id) {
            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + id + '/details');
        },

        /**
         * Navigates to the add-page of a new account
         */
        toAdd: function() {
            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/add');
        },

        /**
         * Navigates to the accounts list
         */
        toList: function() {
            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts');
        }
    };

    AccountRouter.getInstance = function() {
        if (instance == null) {
            instance = new AccountRouter();
        }
        return instance;
    };

    return AccountRouter.getInstance();
});
