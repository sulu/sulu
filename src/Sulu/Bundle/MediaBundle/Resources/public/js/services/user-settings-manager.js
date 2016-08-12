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
                return app.sandbox.sulu.getUserSetting(mediaLanguageKey) || app.sandbox.sulu.getDefaultContentLocale();
            },

            setMediaLocale: function(locale) {
                app.sandbox.sulu.saveUserSetting(mediaLanguageKey, locale);
            },

            getMediaListView: function() {
                return app.sandbox.sulu.getUserSetting(mediaListViewKey) || 'datagrid/decorators/masonry-view';
            },

            setMediaListView: function(viewId) {
                app.sandbox.sulu.saveUserSetting(mediaListViewKey, viewId);
            },

            getMediaListPagination: function() {
                return app.sandbox.sulu.getUserSetting(mediaListPaginationKey) || 'infinite-scroll';
            },

            setMediaListPagination: function(paginationId) {
                app.sandbox.sulu.saveUserSetting(mediaListPaginationKey, paginationId);
            },

            setLastVisitedCollection: function(collectionId) {
                app.sandbox.sulu.saveUserSetting(lastVisitedCollectionKey, collectionId);
            },

            getDropdownPageSize: function() {
                return app.sandbox.sulu.getUserSetting(dropdownPageSize) || 20;
            },

            getInfinityPageSize: function() {
                return app.sandbox.sulu.getUserSetting(infinityPageSize) || 50;
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
