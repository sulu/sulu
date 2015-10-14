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
            dropdownPageSize = 'mediaDropdownPageSize',
            infinityPageSize = 'mediaInfinitePageSize',
            mediaListViewKey = 'collectionEditListView',
            mediaListPaginationKey = 'collectionEditListPagination',
            lastVisitedCollectionKey = 'last-visited-collection';

        /** @constructor **/
        function UserSettingsManager() {
        }

        UserSettingsManager.prototype = {
            getMediaLocale: function() {
                return Husky.sulu.getUserSetting(mediaLanguageKey) || Husky.sulu.user.locale;
            },

            setMediaLocale: function(locale) {
                Husky.sulu.saveUserSetting(mediaLanguageKey, locale);
            },

            getMediaListView: function() {
                return Husky.sulu.getUserSetting(mediaListViewKey) || 'datagrid/decorators/masonry-view';
            },

            setMediaListView: function(viewId) {
                Husky.sulu.saveUserSetting(mediaListViewKey, viewId);
            },

            getMediaListPagination: function() {
                return Husky.sulu.getUserSetting(mediaListPaginationKey) || 'infinite-scroll';
            },

            setMediaListPagination: function(paginationId) {
                Husky.sulu.saveUserSetting(mediaListPaginationKey, paginationId);
            },

            setLastVisitedCollection: function(collectionId) {
                Husky.sulu.saveUserSetting(lastVisitedCollectionKey, collectionId);
            },

            getDropdownPageSize: function() {
                return Husky.sulu.getUserSetting(dropdownPageSize) || 20;
            },

            getInfinityPageSize: function() {
                return Husky.sulu.getUserSetting(infinityPageSize) || 50;
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
