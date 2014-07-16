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
             translations: {
                 'configureLocation': 'Configure Location',
             },
             instanceName: 'instance-one'
        },
        templates = {
            skeleton: [
                '<div class="location-content-container form-element">',
                '<div class="location-header"><a class="location-content-configure fa-gears" href="#"/></div>',
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
            ].join(''),
            overlay: [
                '<div class="location-overlay-content grid">',
                    '<form id="location-configuration-form">',
                        '<div class="grid-row">',
                            '<div class="grid-col-6">',
                            '<div class="form-group">',
                                '<label for="map_source">Map Source</label>',
                                '<select class="form-element" name="map_source" class="map-source">',
                                    '<option value="google">Google Maps</option>',
                                    '<option value="openstreetmaps">Open Street Maps</option>',
                                '</select>',
                                '</div>',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-12">',
                                '<label for="title">Title</label>',
                                '<input class="form-element" type="text" name="title"/ >',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-6">',
                                '<label for="street">Street</label>',
                                '<input class="form-element" type="text" class="street"/ >',
                            '</div>',
                            '<div class="form-group grid-col-6">',
                                '<label for="number">Number</label>',
                                '<input class="form-element" type="text" class="street"/ >',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-6">',
                                '<label for="code">Code</label>',
                                '<input class="form-element" type="text" class="code"/ >',
                            '</div>',
                            '<div class="form-group grid-col-6">',
                                '<label for="country">Country</label>',
                                '<select class="form-element" name="country" class="map-source">',
                                    '<option value="as">Austria</option>',
                                '</select>',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-12">',
                                '<label for="coordinates">Coordinates</label>',
                                '<input class="form-element" type="text" name="coordinates"/ >',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="grid-col-12">',
                                '<img src="/bundles/sululocation/js/test/map.png"/>',
                            '</div>',
                            '<div class="small-font grey-font">Move pointer to change location on map</div>',
                        '</div>',
                    '</div>',
                '</div>'
            ].join('')
        };

    return {
        options: {},
        $button: null,

        initialize: function() {
            this.sandbox.logger.log('initialize', this);

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.createComponent();
        },

        createComponent: function () {
            this.render();
            this.startOverlay();
        },

        render: function () {
            this.sandbox.dom.html(this.$el, templates.skeleton);
            this.sandbox.dom.find('.location-content').append(templates.content);
        },

        startOverlay: function () {
            var $element = this.sandbox.dom.createElement('<div></div>');
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: '.location-content-configure',
                        el: $element,
                        container: this.$el,
                        instanceName: 'location-content.' + this.options.instanceName,
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.configureLocation),
                                data: templates.overlay,
                                okCallback: function () {
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]);
        }
    }
})
