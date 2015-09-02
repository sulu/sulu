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
        'services/sulumedia/media-router'
    ], function(util,
                mediator,
                MediaRouter) {

        'use strict';

        var instance = null,

            /**
             * Create div-container with given containerId and append it to the body
             * @param containerId
             * @returns {*|jQuery|HTMLElement} created div-container
             */
            getOverlayContainer = function(containerId) {
                var $element = $('<div id="' + containerId + '"/>');
                $('body').append($element);

                return $element;
            };


        /** @constructor **/
        function OverlayManager() {
        }

        OverlayManager.prototype = {
            /**
             * Start collection-create overlay
             * @param sandbox parent sandbox for the overlay
             * @param parentCollection of the created collection
             */
            startCreateCollectionOverlay: function(sandbox, parentCollection) {
                var parentId = (!!parentCollection && !!parentCollection.id) ? parentCollection.id : null,
                    $container = getOverlayContainer('create-collection-overlay');

                sandbox.start([{
                    name: 'collections/collection-create-overlay@sulumedia',
                    options: {
                        el: $container,
                        parent: parentId
                    }
                }]);
            },

            /**
             * Start collection-select overlay for moving medias
             * @param sandbox parent sandbox for the overlay
             * @param disableIds collectionIds which cannot get selected
             * @param locale to display collection titles
             */
            startMoveMediaOverlay: function(sandbox, disableIds, locale) {
                if (!$.isArray(disableIds)) {
                    disableIds = [disableIds];
                }
                var $container = getOverlayContainer('select-collection-overlay');

                sandbox.start([{
                    name: 'collections/collection-select-overlay@sulumedia',
                    options: {
                        el: $container,
                        instanceName: 'move-media',
                        title: sandbox.translate('sulu.media.move.overlay-title'),
                        locale: locale,
                        disableIds: disableIds
                    }
                }]);
            },

            /**
             * Start collection-select overlay for moving collection
             * @param sandbox parent sandbox for the overlay
             * @param disableIds collectionIds which cannot get selected
             * @param locale to display collection titles
             */
            startMoveCollectionOverlay: function(sandbox, disableIds, locale) {
                if (!$.isArray(disableIds)) {
                    disableIds = [disableIds];
                }
                var $container = getOverlayContainer('select-collection-overlay');

                sandbox.start([{
                    name: 'collections/collection-select-overlay@sulumedia',
                    options: {
                        el: $container,
                        instanceName: 'move-collection',
                        title: sandbox.translate('sulu.collection.move.overlay-title'),
                        rootCollection: true,
                        disableIds: disableIds,
                        disabledChildren: true,
                        locale: locale
                    }
                }]);
            },

            /**
             * Start media-edit overlay to edit given mediaIds
             * @param sandbox parent sandbox for the overlay
             * @param mediaIds medias to edit in overlay
             * @param locale medias are saved for given locale
             */
            startEditMediaOverlay: function(sandbox, mediaIds, locale) {
                if (!$.isArray(mediaIds)) {
                    mediaIds = [mediaIds];
                }
                var $container = getOverlayContainer('edit-media-overlay');

                sandbox.start([
                    {
                        name: 'collections/media-edit-overlay@sulumedia',
                        options: {
                            el: $container,
                            mediaIds: mediaIds,
                            locale: locale
                        }
                    }
                ]);
            },

            /**
             * Start collection-edit overlay for given collectionId
             * @param sandbox parent sandbox for the overlay
             * @param collectionId collection to edit
             * @param locale collection data is saved for the given locale
             */
            startEditCollectionOverlay: function(sandbox, collectionId, locale) {
                var $container = getOverlayContainer('edit-collection-overlay');

                sandbox.start([
                    {
                        name: 'collections/collection-edit-overlay@sulumedia',
                        options: {
                            el: $container,
                            collectionId: collectionId,
                            locale: locale
                        }
                    }
                ]);
            }
        };

        OverlayManager.getInstance = function() {
            if (instance === null) {
                instance = new OverlayManager();
            }
            return instance;
        };

        return OverlayManager.getInstance();
    }
);
