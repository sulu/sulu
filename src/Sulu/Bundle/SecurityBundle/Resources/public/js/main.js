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

define({

    name: 'Sulu Security Bundle',

    initialize: function (app) {
        var sandbox = app.sandbox;

        app.components.addSource('sulusecurity', '/bundles/sulusecurity/js/components');

        // list all roles
        sandbox.mvc.routes.push({
                route: 'settings/roles',
                components: [
                    {
                        name: 'role/list@sulusecurity',
                        options: { el: '#content' }
                    }
                ]
            }
        );
    }
});
