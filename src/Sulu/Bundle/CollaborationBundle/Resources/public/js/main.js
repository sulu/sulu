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
        sulucollaboration: '../../sulucollaboration/js'
    }
});

define(['underscore', 'app-config'], function(_, AppConfig) {

    'use strict';

    return {

        name: 'SuluCollaborationBundle',

        initialize: function(app) {
            app.components.addSource('sulucollaboration', '/bundles/sulucollaboration/js/components');

            /**
             * Gets executed every time BEFORE a component gets initialized.
             * Start collaboration component if the property exists.
             */
            app.components.before('initialize', function() {
                var config;
                if (!this.collaboration || !(config = this.collaboration())) {
                    return;
                }

                var $element = $('<div id="content-column-collaboration"/>'),
                    options = _.defaults(config, {el: $element, userId: AppConfig.getUser().id});

                this.$el.prepend($element);
                this.sandbox.start([
                    {
                        name: 'collaboration@sulucollaboration',
                        options: options
                    }
                ]);
            });
        }
    };
});
