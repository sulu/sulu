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
        suluautomation: '../../suluautomation/js',
        suluautomationcss: '../../suluautomation/css',

        'services/suluautomation/task-manager': '../../suluautomation/js/services/task-manager'
    }
});

define(['css!suluautomationcss/main'], function() {
    return {

        name: "SuluAutomationBundle",

        initialize: function(app) {

            'use strict';

            app.components.addSource('suluautomation', '/bundles/suluautomation/js/components');
        }
    };
});
