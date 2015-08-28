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
             * Delete contact by given id
             * @param contactId contact to delete
             * @returns {*}
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
            startCreateCollectionOverlay: function(sandbox, parentCollection) {
                var parentId = (!!parentCollection && !!parentCollection.id) ? parentCollection.id : null,
                    $container = getOverlayContainer('create-collection-overlay');

                sandbox.start([{
                    name: 'collections/create-overlay@sulumedia',
                    options: {
                        el: $container,
                        parent: parentId,
                    }
                }]);
            },

            startSelectCollectionOverlayMedia: function(sandbox, disableIds) {
                if (!$.isArray(disableIds)) {
                    disableIds = [disableIds];
                }
                var $container = getOverlayContainer('select-collection-overlay');

                sandbox.start([{
                    name: 'collections/select-overlay@sulumedia',
                    options: {
                        el: $container,
                        instanceName: 'move-media',
                        title: sandbox.translate('sulu.media.move.overlay-title'),
                        disableIds: disableIds
                    }
                }]);
            },

            startSelectCollectionOverlayCollection: function(sandbox, disableIds) {
                if (!$.isArray(disableIds)) {
                    disableIds = [disableIds];
                }
                var $container = getOverlayContainer('select-collection-overlay');

                sandbox.start([{
                    name: 'collections/select-overlay@sulumedia',
                    options: {
                        el: $container,
                        instanceName: 'move-collection',
                        title: sandbox.translate('sulu.collection.move.overlay-title'),
                        rootCollection: true,
                        disableIds: disableIds,
                        disabledChildren: true
                    }
                }]);
            },

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
