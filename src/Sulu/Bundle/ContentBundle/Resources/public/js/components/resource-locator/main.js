/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * handles resource locator
 *
 * @class AutoComplete
 * @constructor
 */
define([], function() {

    'use strict';

    var defaults = {},
        skeleton = function() {
            return [
                '<div class="resource-locator">',
                '   <p>VERY NICE</p>',
                '</div>'
            ].join('');
        },
        render = function() {
            this.sandbox.dom.html(skeleton());
        };

    return {
        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend({}, defaults, this.options);

            render.call(this);
        }
    };
});
