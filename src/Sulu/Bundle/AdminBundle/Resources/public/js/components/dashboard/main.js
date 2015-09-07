/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class Dashboard
 * @constructor
 */

define(function() {

    'use strict';

    return {

        layout: {
            content: {
                width: 'max'
            }
        },

        /**
         * Initialize the component
         */
        initialize: function() {
            this.sandbox.start([
                {
                    name: 'search-results@sulusearch',
                    options: {
                        el: this.$el,
                        displayLogo: true
                    }
                }
            ], {reset: true});
        }
    };
});
