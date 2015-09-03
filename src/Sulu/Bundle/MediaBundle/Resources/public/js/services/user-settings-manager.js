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

        var instance = null,
            mediaLanguageKey = 'mediaLanguage',
            mediaListViewKey = 'collectionEditListView',
            lastVisitedCollectionKey = 'last-visited-collection';

        /** @constructor **/
        function UserSettingsManager() {
        }

        UserSettingsManager.prototype = {
            getMediaLocale: function() {
                return app.sandbox.sulu.getUserSetting(mediaLanguageKey) || this.sandbox.sulu.user.locale;
            },

            setMediaLocale: function(locale) {
                app.sandbox.sulu.saveUserSetting(mediaLanguageKey, locale);
            },

            getMediaListView: function() {
                return app.sandbox.sulu.getUserSetting(mediaListViewKey) || 'decorators/masonry';
            },

            setMediaListView: function(viewId) {
                app.sandbox.sulu.saveUserSetting(mediaListViewKey, viewId);
            },

            setLastVisitedCollection: function(collectionId) {
                app.sandbox.sulu.saveUserSetting(lastVisitedCollectionKey, collectionId);
            }
        };

        UserSettingsManager.getInstance = function() {
            if (instance === null) {
                instance = new UserSettingsManager();
            }
            return instance;
        };

        return UserSettingsManager.getInstance();
    }
);
