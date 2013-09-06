/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'husky', 'router'], function($, Husky, Router) {

    'use strict';

    var $navigation = $('#navigation'),
        $content = $('#content'),

        initialize = function() {
            var App = window.App || {};

            Router.initialize(App);

            $navigation.huskyNavigation({
                url: 'navigation'
            });

            $navigation.data('Husky.Ui.Navigation').on('navigation:item:content:show', function(event) {
                // FIXME abstract?
                // 45px margin to navigation at start
                $('.demo-container').css('margin-left', (event.data.navWidth + 45) + "px");
                // mid div has margin-left 25px at start
                $('#headerbar-mid').css('margin-left', (event.data.navWidth - 275 + 45) + "px");
                // navigation width is 300px at start
                $('#headerbar-right').css('width', (220 - (event.data.navWidth - 300 + 45)) + "px");

                Router.navigate(event.item.get('action'));
            });

            // Make some Shortcuts globally available
            App.$content = $content;
            App.Navigation = $navigation.data('Husky.Ui.Navigation');

            window.App = App;
        };

    return {
        initialize: initialize
    }
});