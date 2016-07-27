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
        suludocumentmanager: '../../suludocumentmanager/js'
    }
});

define(['suludocumentmanager/extensions/sulu-buttons'], function(SuluButtons) {

    'use strict';

    return {

        name: 'Sulu Document Manager Bundle',

        initialize: function(app) {
            app.sandbox.sulu.buttons.push(SuluButtons.getButtons(app));
            app.sandbox.sulu.buttons.dropdownItems.push(SuluButtons.getDropdownItems(app));
        }
    };
});
