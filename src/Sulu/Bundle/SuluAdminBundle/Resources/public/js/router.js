/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['backbone'], function (Backbone) {

    'use strict';

    var router;

    var AppRouter = Backbone.Router.extend({

    });

    var initialize = function(App) {
        router = new AppRouter();

        router.route('settings/translate', 'translateBundle');
        router.on('route:translateBundle', function() {
            console.log('Here starts the TranslateBundle');
        });

        App.Router = router;

        Backbone.history.start();
    };

    return {
        initialize: initialize
    };
});