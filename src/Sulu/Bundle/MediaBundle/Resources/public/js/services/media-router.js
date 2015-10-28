/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/husky/mediator'], function(Mediator) {

    'use strict';

    var instance = null;

    /** @constructor **/
    function AccountRouter() {
    }

    AccountRouter.prototype = {

        /**
         * Navigates to collection view of given collectionId
         * @param collectionId
         */
        toCollection: function(collectionId) {
            if (!!collectionId) {
                Mediator.emit('sulu.router.navigate', 'media/collections/edit:' + collectionId + '/files', true, true);
            } else {
                this.toRoot();
            }
        },

        /**
         * Navigates to the collection root view
         */
        toRoot: function() {
            Mediator.emit('sulu.router.navigate', 'media/collections/root', true, true);
        }
    };

    AccountRouter.getInstance = function() {
        if (instance === null) {
            instance = new AccountRouter();
        }
        return instance;
    };

    return AccountRouter.getInstance();
});
