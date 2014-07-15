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
                '<div class="location-content-container form-element">',
                '<div class="location-header"><a class="fa-gears" href="#"/></div>',
                '<div class="location-content"></div>'
            ].join(''),
            content: [
                    '<div class="grid-row">',
                        '<div class="grid-col-6 container">',
                            '<iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d5400.563708014352!2d9.761512727812047!3d47.406443294080205!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1smassiveart+dornbirn!5e0!3m2!1sfr!2sfr!4v1405433236369" width="350" height="200" frameborder="0" style="border:0"></iframe>',
                            '<div class="source">Source: Google Maps</div>',
                        '</div>',
                        '<div class="grid-col-6">',
                            '<div class="container grid">',
                                '<div class="grid-row">',
                                    '<div class="grid-col-3 field">Title:</div>',
                                    '<div class="grid-col-9">MASSIVE ART WebServices GmbH</div>',
                                '</div>',
                                '<div class="grid-row">',
                                    '<div class="grid-col-3 field">Street:</div>',
                                    '<div class="grid-col-9">Steineback</div>',

                                    '<div class="grid-col-3 field">Number:</div>',
                                    '<div class="grid-col-9">16</div>',

                                    '<div class="grid-col-3 field">Code</div>',
                                    '<div class="grid-col-9">6850</div>',

                                    '<div class="grid-col-3 field">Town</div>',
                                    '<div class="grid-col-9">Dornbirn</div>',

                                    '<div class="grid-col-3 field">Country</div>',
                                    '<div class="grid-col-9">Austria</div>',
                                '</div>',
                                '<div class="grid-row">',
                                    '<div class="grid-col-3 field">Coordinates:</div>',
                                    '<div class="grid-col-9">47.404936,9.75833,17</div>',
                                '</div>',
                            '</div>',
                        '</div>',
                    '</div>',
            ].join('')
        }

    return {

        initialize: function() {
            this.sandbox.logger.log('initialize', this);

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.createComponent();
        },

        createComponent: function () {
            this.render();
        },

        render: function () {
            this.sandbox.dom.html(this.$el, templates.skeleton);
            this.sandbox.dom.find('.location-content').append(templates.content);
        },

        renderHeader: function () {
            $this.$header = this.sandbox.dom.find('loc-header', this.$el);
        },

        renderButton: function() {
            this.$button = this.sandbox.dom.createElement('<a href="#"/>');
            this.sandbox.dom.addClass(this.$button, constants.buttonClass);
            this.sandbox.dom.append(this.$header, this.$button);
        },
    }
})

