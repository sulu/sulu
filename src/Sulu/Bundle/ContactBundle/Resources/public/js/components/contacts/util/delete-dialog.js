/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    /**
     * @var ids - array of ids to delete
     * @var callback - callback function returns true or false if data got deleted
     */
    var confirmDeleteDialog = function(callbackFunction) {
        // check if callback is a function
        if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
            throw 'callback is not a function';
        }
        // show dialog
        this.sandbox.emit('sulu.overlay.show-warning',
            'sulu.overlay.be-careful',
            'sulu.overlay.delete-desc',
            callbackFunction.bind(this, false),
            callbackFunction.bind(this, true)
        );
    };

    return {

        /**
         * Shows a dialog when a contact should be deleted
         * @param sandbox
         * @param contact
         */
        show: function(sandbox, contact) {
            if (!!sandbox && !!contact) {
                this.sandbox = sandbox;
                confirmDeleteDialog.call(this, function(wasConfirmed) {
                    if (wasConfirmed) {
                        this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                        contact.destroy({
                            success: function() {
                                this.sandbox.emit('sulu.router.navigate', 'contacts/contacts');
                            }.bind(this)
                        });
                    }
                }.bind(this));
            }
        }
    };
});
