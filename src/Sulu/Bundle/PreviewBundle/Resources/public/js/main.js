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
        sulupreview: '../../sulupreview/js',
        sulupreviewcss: '../../sulupreview/css',

        'services/sulupreview/preview': '../../sulupreview/js/services/preview'
    }
});

define(['sulupreview/extensions/sulu-buttons', 'css!sulupreviewcss/main'], function(SuluButtons) {

    'use strict';

    return {

        name: 'Sulu Preview Bundle',

        initialize: function(app) {
            app.components.addSource('sulupreview', '/bundles/sulupreview/js/components');

            app.sandbox.sulu.buttons.push(SuluButtons.getButtons());
            app.sandbox.sulu.buttons.dropdownItems.push(SuluButtons.getDropdownItems());
        }
    };
});
