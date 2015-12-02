/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    return {
        initialize: function() {
            this.data = this.options.data();

            this.render();
            this.bindCustomEvents();
        },

        render: function() {
            this.html(this.data.title);
        },

        bindCustomEvents: function() {
        }
    };
});
