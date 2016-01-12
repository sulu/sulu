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
        sulucustomurl: '../../sulucustomurl/js'
    }
});

define(function() {

    'use strict';

    return {

        name: 'SuluCustomUrl',

        initialize: function(app) {
            app.components.addSource('sulucustomurl', '/bundles/sulucustomurl/js/components');
        }
    };
});

