/*
 * This file is part of Sulu.
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

define(['config', 'suluautomation/extensions/sulu-buttons', 'css!suluautomationcss/main'], function(config, buttons) {
    return {

        name: "SuluAutomationBundle",

        initialize: function(app) {

            'use strict';

            config.set('sulu_automation.enabled', true);

            app.components.addSource('suluautomation', '/bundles/suluautomation/js/components');

            app.sandbox.sulu.buttons.dropdownItems.push(buttons.getDropdownItems());
        }
    };
});
