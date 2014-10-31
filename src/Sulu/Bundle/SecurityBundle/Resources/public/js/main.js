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
        sulusecurity: '../../sulusecurity/js'
    }
});

define(function() {

    'use strict';

    return {

        name: 'Sulu Security Bundle',

        initialize: function(app) {
            var sandbox = app.sandbox;

            app.components.addSource('sulusecurity', '/bundles/sulusecurity/js/components');

            // list all roles
            sandbox.mvc.routes.push({
                route: 'settings/roles',
                callback: function() {
                    this.html('<div data-aura-component="roles@sulusecurity" data-aura-display="list"/>');
                }
            });

            // show form for a new role
            sandbox.mvc.routes.push({
                route: 'settings/roles/new',
                callback: function() {
                    this.html('<div data-aura-component="roles/components/content@sulusecurity" data-aura-display="form"/>');
                }
            });


            // show form for editing a role
            sandbox.mvc.routes.push({
                route: 'settings/roles/edit::id/:content',
                callback: function(id) {
                    this.html(
                        '<div data-aura-component="roles/components/content@sulusecurity" data-aura-title="sulu.roles.permissions" data-aura-display="form" data-aura-id="' + id + '"/>'
                    );
                }
            });
        }
    };

});
