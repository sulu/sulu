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
                        createdCallback: function(collection) {
                            MediaRouter.toCollection(collection.id);
                            sandbox.emit(
                                'husky.data-navigation.collections.set-url',
                                '/admin/api/collections/' + collection.id + '?depth=1'
                            );
                        }.bind(this)
                    }
                }]);
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
