/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        sulumedia: '../../sulumedia/js',
        'extensions/masonry':'../../sulumedia/js/extensions/masonry',
        'extensions/sulu-buttons-mediabundle': '../../sulumedia/js/extensions/sulu-buttons',

        'services/sulumedia/media-router': '../../sulumedia/js/services/media-router',
        'services/sulumedia/overlay-manager': '../../sulumedia/js/services/overlay-manager',
        'services/sulumedia/collection-manager': '../../sulumedia/js/services/collection-manager',
        'services/sulumedia/media-manager': '../../sulumedia/js/services/media-manager',
        'services/sulumedia/user-settings-manager': '../../sulumedia/js/services/user-settings-manager',

        "type/mediaSelection": '../../sulumedia/js/validation/types/mediaSelection',
        'decorators/masonry': '../../sulumedia/js/components/collections/masonry-decorator/masonry-view'
    }
});

define(['services/sulumedia/media-router',
    'services/sulumedia/overlay-manager',
    'extensions/masonry',
    'extensions/sulu-buttons-mediabundle'], function(MediaRouter, OverlayManager, MasonryExtension, MediaButtons){

    'use strict';

    return {
        name: "SuluMediaBundle",

        initialize: function(app) {
            var sandbox = app.sandbox;

            app.components.addSource('sulumedia', '/bundles/sulumedia/js/components');

            MasonryExtension.initialize(app);
            sandbox.sulu.buttons.push(MediaButtons.getButtons());
            sandbox.sulu.buttons.dropdownItems.push(MediaButtons.getDropdownItems());

            // list all collections
            sandbox.mvc.routes.push({
                route: 'media/collections/root',
                callback: function() {
                    return '<div data-aura-component="collections/root@sulumedia"/>';
                }
            });

            // show a single collection with files and upload
            sandbox.mvc.routes.push({
                route: 'media/collections/edit::id/:content',
                callback: function(id) {
                    return '<div data-aura-component="collections@sulumedia" data-aura-display="edit" data-aura-id="' + id + '"/>';
                }
            });

            // show a single collection with files and upload
            sandbox.mvc.routes.push({
                route: 'media/collections/edit::id/:content/edit::mediaId',
                callback: function(id, content, mediaId) {
                    sandbox.sulu.viewStates['media-file-edit-id'] = parseInt(mediaId);
                    sandbox.emit('sulu.router.navigate', 'media/collections/edit:' + id + '/' + content);
                }
            });

            app.components.before('initialize', function() {
                if (this.name !== 'Sulu App') {
                    return;
                }

                this.sandbox.on('husky.data-navigation.collections.select', function(item) {
                    if (item === null) {
                        MediaRouter.toRoot();
                    }
                }.bind(this));

                this.sandbox.on('husky.data-navigation.collections.add', function(item) {
                    OverlayManager.startCreateCollectionOverlay(this.sandbox, item);
                }.bind(this));
            });
        }
    };
});
