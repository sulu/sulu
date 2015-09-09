/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/husky/util',
    'services/husky/mediator',
    'services/husky/translator'
], function(util, mediator, translator) {

    'use strict';
    return {

        /**
         * Show contact confirm-delete dialog
         * @param ids contacts which are deleted, if dialog is confirmed
         * @param okCallback function which is executed, if dialog is confirmed
         */
        showDialog: function(ids, okCallback) {
            if (!$.isArray(ids)){
                ids = [ids]; //enable integer input
            }

            // no account selected, do not show dialog
            if (ids.length === 0) {
                return;
            }

            // show warning dialog
            mediator.emit(
                'sulu.overlay.show-warning',
                util.capitalizeFirstLetter(translator.translate('public.delete')) + '?',
                'sulu.overlay.delete-desc',
                null,
                okCallback.bind(this, true),
                {
                    okDefaultText: 'public.delete'
                }
            );
        }
    };
});
