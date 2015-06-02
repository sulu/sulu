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
            navigation: {
                collapsed: false
            },
            content: {
                width: 'max',
                topSpace: false,
                leftSpace: false,
                rightSpace: false
            },
            sidebar: false
        },

        header: false,

        /**
         * Initialize the component
         */
        initialize: function() {
        }
    };
});
