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
        sulucontent: '../../sulucontent/js',
        "type/resourceLocator": '../../sulucontent/js/validation/types/resourceLocator'
    }
});

define({

    name: "Sulu Content Bundle",

    initialize: function (app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('sulucontent', '/bundles/sulucontent/js/components');


        // list all contacts
        sandbox.mvc.routes.push({
            route: 'content/content',
            callback: function(){
                this.html('<div data-aura-component="content@sulucontent" data-aura-display="list"/>');
            }
        });

        // show form for new contacts
        sandbox.mvc.routes.push({
            route: 'content/content/add',
            callback: function(){
                this.html('<div data-aura-component="content@sulucontent" data-aura-display="form"/>');
            }
        });
    }
});
