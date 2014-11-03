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

        // list all collections
        sandbox.mvc.routes.push({
            route: 'media/collections',
            callback: function() {
                this.html('<div data-aura-component="collections@sulumedia" data-aura-display="list"/>');
            }
        });

        // show a single collection with files and upload
        sandbox.mvc.routes.push({
            route: 'media/collections/edit::id/:content',
            callback: function(id, content) {
                this.html(
                    '<div data-aura-component="collections/components/content@sulumedia" data-aura-content="' + content + '" data-aura-id="' + id + '"/>'
                );
            }
        });
    }
});
