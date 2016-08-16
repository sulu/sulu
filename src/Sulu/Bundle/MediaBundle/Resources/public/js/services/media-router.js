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

    var mediaRoutes = {
        rootCollection: 'media/collections/:locale',
        rootCollectionWithoutLocale: 'media/collections',
        editCollection: 'media/collections/:locale/edit::id/:content',
        editCollectionWithoutLocale: 'media/collections/edit::id/:content',
        editMedia: 'media/collections/:locale/edit::id/:content/edit::mediaId',
        editMediaWithoutLocale: 'media/collections/edit::id/:content/edit::mediaId'
    };

    return {
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
                    mediaRoutes.editCollection
                        .replace(':locale', locale)
                        .replace(':id', collectionId)
                        .replace(':content', 'files') + mediaEditAppendix,
                    true,
                    true
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
            Mediator.emit('sulu.router.navigate', mediaRoutes.rootCollection.replace(':locale', locale), true, true);
        },

        /**
         * Initializes the routes for this bundle.
         *
         * @param {Array} routes An array to push the routes onto
         */
        initialize: function(routes) {
            // list the top collections with all media
            routes.push({
                route: mediaRoutes.rootCollection,
                callback: function(locale) {
                    return '<div data-aura-component="collections/edit@sulumedia" data-aura-locale="' + locale + '"/>';
                }
            });

            // list the top collections with all media (without locale)
            routes.push({
                route: mediaRoutes.rootCollectionWithoutLocale,
                callback: function() {
                    if (!!UserSettingsManager.getLastVisitedCollection()) {
                        this.toCollection(
                            UserSettingsManager.getLastVisitedCollection(),
                            UserSettingsManager.getMediaLocale()
                        );
                    } else {
                        this.toRootCollection(UserSettingsManager.getMediaLocale());
                    }
                }.bind(this)
            });

            // show a single collection with files and upload
            routes.push({
                route: mediaRoutes.editCollection,
                callback: function(locale, id) {
                    return '<div data-aura-component="collections/edit@sulumedia" data-aura-id="' + id + '" data-aura-locale="' + locale + '"/>';
                }
            });

            // show a single collection with files and upload (without locale)
            routes.push({
                route: mediaRoutes.editCollectionWithoutLocale,
                callback: function(id) {
                    this.toCollection(id);
                }.bind(this)
            });

            // show a single collection with files and upload
            routes.push({
                route: mediaRoutes.editMedia,
                callback: function(locale, id, content, mediaId) {
                    return '<div data-aura-component="collections/edit@sulumedia" data-aura-id="' + id + '" data-aura-locale="' + locale + '" data-aura-edit-id="' + mediaId + '"/>';
                }
            });

            // show a single collection with files and upload (without locale)
            routes.push({
                route: mediaRoutes.editMediaWithoutLocale,
                callback: function(id, content, mediaId) {
                    this.toCollection(id, UserSettingsManager.getMediaLocale(), mediaId);
                }.bind(this)
            });
        }
    };
});
