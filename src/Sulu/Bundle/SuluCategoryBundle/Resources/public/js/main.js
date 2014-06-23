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
        sulucategory: '../../sulucategory/js'
    }
});

define({

    name: "SuluCategoryBundle",

    initialize: function(app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('sulucategory', '/bundles/sulucategory/js/components');

        sandbox.mvc.routes.push({
            route: 'settings/categories',
            callback: function(){
                this.html('<div data-aura-component="categories@sulucategory" data-aura-display="list"/>');
            }
        });
    }
});
