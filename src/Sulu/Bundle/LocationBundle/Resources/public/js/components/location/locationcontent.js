/**
 * This file is part of Husky frontend development framework.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @module husky/components/location-content
 */

define([], function() {

    'use strict';

    var defaults = {
        },
        templates = {
            skeleton: [
                '<div class="loc-content-container form-element">',
                '<div class="loc-header"></div>',
                '<div class="loc-content"></div>'
            ].join(''),
            overlayContent: {
                main: [
                    '<div class="loc-overlay-content">',
                    '</div>'
                ].join('')
            }
        }

    return {

        initialize: function() {
            this.sandbox.logger.log('initialize', this);

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.createComponent();
        },

        createComponent: function () {
            alert('foobar');
        }
    }
})

