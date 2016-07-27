/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/husky/mediator'], function(Mediator) {

    'use strict';

    return {

        /**
         * Navigates to the edit-form.
         *
         * @param id The id of the role to edit
         */
        toEdit: function(id) {
            Mediator.emit('sulu.router.navigate', 'settings/roles/edit:' + id + '/details');
        },

        /**
         * Navigates to the add-form.
         */
        toAdd: function() {
            Mediator.emit('sulu.router.navigate', 'settings/roles/new');
        },

        /**
         * Navigates to the roles list.
         */
        toList: function() {
            Mediator.emit('sulu.router.navigate', 'settings/roles');
        }
    };
});
