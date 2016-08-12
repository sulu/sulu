/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/husky/mediator',
    'services/sulumedia/user-settings-manager'
], function(Mediator, UserSettingsManager) {

    'use strict';

    var instance = null;

    /** @constructor **/
    function AccountRouter() {
    }

    AccountRouter.prototype = {

        /**
         * Navigates to collection view of given collectionId.
         *
         * @param collectionId
         * @param locale
         * @param mediaId
         */
        toCollection: function(collectionId, locale, mediaId) {
            locale = locale || UserSettingsManager.getMediaLocale();
            if (!!collectionId) {
                var mediaEditAppendix = (!!mediaId) ? '/edit:' + mediaId : '';
                Mediator.emit(
                    'sulu.router.navigate',
                    'media/collections/' + locale + '/edit:' + collectionId + '/files' + mediaEditAppendix, true, true
                );
            } else {
                this.toRootCollection(locale);
            }
        },

        /**
         * Navigates to the collection root view.
         *
         * @param locale
         */
        toRootCollection: function(locale) {
            locale = locale || UserSettingsManager.getMediaLocale();
            Mediator.emit('sulu.router.navigate', 'media/collections/' + locale, true, true);
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
