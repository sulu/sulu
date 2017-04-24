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
        suluroute: '../../suluroute/js'
    }
});

define(function() {

    'use strict';

    return {

        name: 'Sulu Route Bundle',

        initialize: function(app) {
            app.components.addSource('suluroute', '/bundles/suluroute/js/components');
        }
    }
});
