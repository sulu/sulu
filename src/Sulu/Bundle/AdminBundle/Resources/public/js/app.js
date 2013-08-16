/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'husky', 'router'], function($, Husky, Router) {

    'use strict';

    var $navigation = $('#navigation');

    var $content = $('#content');

    var initialize = function() {
        var App = window.App || {};

        Router.initialize(App);

        $navigation.huskyNavigation({
            url: 'navigation'
        });

        $navigation.data('Husky.Ui.Navigation').on('navigation:item:content:show', function(item){
            Router.navigate(item.get('action'));
        });

        // Make some Shortcuts globally available
        App.$content = $content;
        App.$navigation = $navigation.data('Husky.Ui.Navigation');

        window.App = App;
    };

    return {
        initialize: initialize
    }
});