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
        sulucustomurl: '../../sulucustomurl/js',
        sulucustomurlcss: '../../sulucustomurl/css',

        'type/custom-url': '../../sulucustomurl/js/components/webspace/settings/custom-url/input/custom-url',

        'services/sulucustomurl/custom-url-manager': '../../sulucustomurl/js/services/custom-url-manager'
    }
});

define(['css!sulucustomurlcss/main.css'], function() {

    'use strict';

    return {

        name: 'SuluCustomUrl',

        initialize: function(app) {
            app.components.addSource('sulucustomurl', '/bundles/sulucustomurl/js/components');
        }
    };
});
