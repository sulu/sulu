/**
 * This file is part of Husky frontend development framework.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @module husky/components/geolocation-content
 */

define([], function() {

    'use strict';

    var defaults = {
        },
        templates = {
            skeleton: [
                '<div class="geoloc-content-container form-element">',
                '<div class="geoloc-header"></div>',
                '<div class="geoloc-content"></div>'
            ].join(''),
            overlayContent: {
                main: [
                    '<div class="geoloc-overlay-content">',
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

