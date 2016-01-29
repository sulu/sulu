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
        sulucollaboration: '../../sulucollaboration/js',
    }
});

define(function() {

    'use strict';

    return {

        name: 'SuluCollaborationBundle',

        initialize: function(app) {
            app.components.addSource('sulucollaboration', '/bundles/sulucollaboration/js/components');
        }
    };
});
