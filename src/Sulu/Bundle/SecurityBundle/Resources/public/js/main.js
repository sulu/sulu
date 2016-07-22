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
        sulusecurity: '../../sulusecurity/js',

        'services/sulusecurity/role-router': '../../sulusecurity/js/services/role-router',
        'services/sulusecurity/role-manager': '../../sulusecurity/js/services/role-manager'
    }
});

define(['config'], function(Config) {

    'use strict';

    return {

        name: 'Sulu Security Bundle',

        initialize: function(app) {
            var sandbox = app.sandbox;

            Config.set(
                'sulusecurity.permissions',
                [
                    {value: 'view', icon: 'eye', title: 'security.permissions.view'},
                    {value: 'add', icon: 'plus-circle', title: 'security.permissions.add'},
                    {value: 'edit', icon: 'pencil', title: 'security.permissions.edit'},
                    {value: 'delete', icon: 'trash-o', title: 'security.permissions.delete'},
                    {value: 'live', icon: 'signal', title: 'security.permissions.live'},
                    {value: 'security', icon: 'unlock-alt', title: 'security.permissions.security'}
                ]
            );

            Config.set('suluresource.filters.type.roles', {
                breadCrumb: [
                    {title: 'navigation.settings'},
                    {title: 'security.roles.title', link: 'settings/roles'}
                ],
                routeToList: 'settings/roles'
            });

            app.components.addSource('sulusecurity', '/bundles/sulusecurity/js/components');

            // list all roles
            sandbox.mvc.routes.push({
                route: 'settings/roles',
                callback: function() {
                    return '<div data-aura-component="roles/list@sulusecurity"/>';
                }
            });

            // show form for a new role
            sandbox.mvc.routes.push({
                route: 'settings/roles/new',
                callback: function() {
                    return '<div data-aura-component="roles/edit@sulusecurity" />';
                }
            });


            // show form for editing a role
            sandbox.mvc.routes.push({
                route: 'settings/roles/edit::id/:content',
                callback: function(id) {
                    return '<div data-aura-component="roles/edit@sulusecurity" data-aura-id="' + id + '"/>';
                }
            });
        }
    };
});
