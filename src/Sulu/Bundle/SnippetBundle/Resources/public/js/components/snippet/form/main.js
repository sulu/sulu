/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulusnippet/components/snippet/main'
], function(Snippet) {

    'use strict';

    var component = {
        initialize: function() {
            this.bindModelEvents();

            this.render();
        },

        render: function() {
        }
    };

    // inheritance
    component.__proto__ = Snippet;

    return component;
});
