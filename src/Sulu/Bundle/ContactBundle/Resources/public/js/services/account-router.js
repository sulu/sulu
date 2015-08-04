/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/husky/mediator'], function(mediator) {

    'use strict';

    var instance = null;

    /** @constructor **/
    function AccountRouter() {}

    AccountRouter.prototype = {

        /**
         * Navigates to the edit of an account
         * @param id The id of the account to edit
         */
        toEdit: function(id) {
            mediator.emit('sulu.router.navigate', 'contacts/accounts/edit:' + id + '/details');
        },

        /**
         * Navigates to the add-page of a new account
         */
        toAdd: function() {
            mediator.emit('sulu.router.navigate', 'contacts/accounts/add');
        },

        /**
         * Navigates to the accounts list
         */
        toList: function() {
            mediator.emit('sulu.router.navigate', 'contacts/accounts');
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
