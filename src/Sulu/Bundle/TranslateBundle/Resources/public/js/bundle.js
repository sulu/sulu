/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['router'], function(Router) {

    'use strict';

    var initialize = function() {
        //add routes
        Router.route('settings/translate', 'translate', function() {
            console.log('Translate called!');
        });
    };

    return {
        initialize: initialize
    }
});