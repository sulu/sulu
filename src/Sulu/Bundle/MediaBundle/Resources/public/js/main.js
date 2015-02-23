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
        "type/mediaSelection": '../../sulumedia/js/validation/types/mediaSelection'
    }
});

define({

    name: "SuluMediaBundle",

    initialize: function(app) {

        'use strict';
        var sandbox = app.sandbox;

        app.components.addSource('sulumedia', '/bundles/sulumedia/js/components');

        // show a single collection with files and upload
        sandbox.mvc.routes.push({
            route: 'media/collections/edit::id/:content',
            callback: function(id, content) {
                this.sandbox.emit('husky.navigation.select-item', 'media/collection');

                this.html(
                    '<div data-aura-component="collections/components/content@sulumedia" data-aura-content="' + content + '" data-aura-id="' + id + '"/>'
                );
            }
        });
    }
});
