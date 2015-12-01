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
        overlayOpened = false,

        /**
         * Create div-container with given containerId and append it to the body
         * @param containerId
         * @returns {*|jQuery|HTMLElement} created div-container
         */
        getOverlayContainer = function(containerId) {
            var $element = $('<div id="' + containerId + '"/>');
            $('body').append($element);

            return $element;
        },

        /**
         * Register overlay as opened and listen for close-event. This user to prevent
         * opening multiple overlays at the same time.
         * @param sandbox
         * @param closeEvent
         */
        registerOpenedOverlay = function(closeEvent) {
            overlayOpened = true;

            this.sandbox.once(closeEvent, function() {
                overlayOpened = false;
            }.bind(this));
        };


    /** @constructor **/
    function OverlayManager() {
    }

    OverlayManager.prototype = {
        /**
         * Start collection-create overlay
         * @param parentCollection of the created collection
         */
        startCreateCollectionOverlay: function(parentCollection) {
            if (!!overlayOpened) {
                return false;
            }

            var parentId = (!!parentCollection && !!parentCollection.id) ? parentCollection.id : null,
                $container = getOverlayContainer('create-collection-overlay');

            this.sandbox.start([{
                name: 'collections/collection-create-overlay@sulumedia',
                options: {
                    el: $container,
                    parent: parentId
                }
            }]);
            registerOpenedOverlay.call(this, 'sulu.collection-add.closed');
        },

        /**
         * Start collection-select overlay for moving medias
         * @param disableIds collectionIds which cannot get selected
         * @param locale to display collection titles
         */
        startMoveMediaOverlay: function(disableIds, locale) {
            if (!!overlayOpened) {
                return false;
            }

            if (!$.isArray(disableIds)) {
                disableIds = [disableIds];
            }
            var $container = getOverlayContainer('select-collection-overlay');

            this.sandbox.start([{
                name: 'collections/collection-select-overlay@sulumedia',
                options: {
                    el: $container,
                    instanceName: 'move-media',
                    title: this.sandbox.translate('sulu.media.move.overlay-title'),
                    locale: locale,
                    disableIds: disableIds
                }
            }]);
            registerOpenedOverlay.call(this, 'sulu.collection-select.move-media.closed');
        },

        /**
         * Start collection-select overlay for moving collection
         * @param disableIds collectionIds which cannot get selected
         * @param locale to display collection titles
         */
        startMoveCollectionOverlay: function(disableIds, locale) {
            if (!!overlayOpened) {
                return false;
            }

            if (!$.isArray(disableIds)) {
                disableIds = [disableIds];
            }
            var $container = getOverlayContainer('select-collection-overlay');

            this.sandbox.start([{
                name: 'collections/collection-select-overlay@sulumedia',
                options: {
                    el: $container,
                    instanceName: 'move-collection',
                    title: this.sandbox.translate('sulu.collection.move.overlay-title'),
                    rootCollection: true,
                    disableIds: disableIds,
                    disabledChildren: true,
                    locale: locale
                }
            }]);
            registerOpenedOverlay.call(this, 'sulu.collection-select.move-collection.closed');
        },

        /**
         * Start media-edit overlay to edit given mediaIds
         * @param mediaIds medias to edit in overlay
         * @param locale medias are saved for given locale
         */
        startEditMediaOverlay: function(mediaIds, locale) {
            if (!!overlayOpened) {
                return false;
            }

            if (!$.isArray(mediaIds)) {
                mediaIds = [mediaIds];
            }
            var $container = getOverlayContainer('edit-media-overlay');

            this.sandbox.start([{
                name: 'collections/media-edit-overlay@sulumedia',
                options: {
                    el: $container,
                    mediaIds: mediaIds,
                    locale: locale
                }
            }]);
            registerOpenedOverlay.call(this, 'sulu.media-edit.closed');
        },

        /**
         * Start collection-edit overlay for given collectionId
         * @param collectionId collection to edit
         * @param locale collection data is saved for the given locale
         */
        startEditCollectionOverlay: function(collectionId, locale) {
            if (!!overlayOpened) {
                return false;
            }

            var $container = getOverlayContainer('edit-collection-overlay');

            this.sandbox.start([{
                name: 'collections/collection-edit-overlay@sulumedia',
                options: {
                    el: $container,
                    collectionId: collectionId,
                    locale: locale
                }
            }]);
            registerOpenedOverlay.call(this, 'sulu.collection-edit.closed');
        },

        /**
         * Start collection-edit overlay for given collectionId
         * @param collectionId collection to edit
         * @param locale collection data is saved for the given locale
         */
        startPermissionSettingsOverlay: function(id, type, securityContext) {
            if (!!overlayOpened) {
                return false;
            }

            var $container = getOverlayContainer('permission-settings-overlay');

            this.sandbox.start([{
                name: 'collections/permission-settings-overlay@sulumedia',
                options: {
                    el: $container,
                    id: id,
                    type: type,
                    securityContext: securityContext
                }
            }]);
            registerOpenedOverlay.call(this, 'sulu.permission-settings.closed');
        },

        /**
         * Start play-video overlay
         * @param videoId Id of the video that should be opened in overlay
         * @param locale Locale the video should be loaded in
         */
        startPlayVideoOverlay: function(videoId, locale) {
            if (!!overlayOpened) {
                return false;
            }

            var $container = getOverlayContainer('play-video-overlay');

            this.sandbox.start([{
                name: 'collections/media-play-video-overlay@sulumedia',
                options: {
                    el: $container,
                    videoId: videoId,
                    locale: locale
                }
            }]);

            registerOpenedOverlay.call(this, 'sulu.media-play-video.play-video-overlay.closed');
        }
    };

    OverlayManager.getInstance = function() {
        if (instance === null) {
            instance = new OverlayManager();
        }
        return instance;
    };

    return OverlayManager.getInstance();
});
