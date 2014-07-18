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
                 configureLocation: 'Configure Location',
                 title: 'Title',
                 street: 'Street',
                 number: 'Number',
                 code: 'Code',
                 town: 'Town',
                 country: 'Country',
                 coordinates: 'Coordinates'
             },
             instanceName: null,
             mapProviders: {},
             mapProvider: null,
             countries: {}
        },

        constants = {
            contentContainerClass: 'location-content-container',
            contentClass: 'location-content',
            configureButtonClass: 'location-content-configure',
            overlayClass: 'location-overlay-content',
            formId: 'location-content-overlay-form',
            mapElementId: 'location-map',
            mapElementClass: 'location-map',
        },

        dataDefaults = {
            title: '',
            street: '',
            number: '',
            code: '',
            country: '',
            coordinates: ''
        },

        templates = {
            skeleton: [
                '<div class="<%= constants.contentContainerClass %> form-element">',
                    '<div class="location-header"><a href="#" class="<%= constants.configureButtonClass %>"><span class="fa-gears icon large"></span></a></div>',
                    '<div class="<%= constants.contentClass %>"></div>',
                '</div>',
            ].join(''),
            content: [
                    '<div class="grid-row">',
                        '<div class="grid-col-6 container">',
                            '<div id="<%= constants.mapElementId %>" class="<%= constants.mapElementClass %>"><img src="/bundles/sululocation/js/test/map.png"/></div>',
                            '<div class="provider">Provider: <%= data.map_provider %></div>',
                        '</div>',
                        '<div class="grid-col-6">',
                            '<div class="container grid">',
                                '<div class="grid-row">',
                                    '<div class="grid-col-3 field"><%= translations.title %>:</div>',
                                    '<div class="grid-col-9"><%= data.title %></div>',
                                '</div>',
                                '<div class="grid-row no-spacing">',
                                    '<div class="grid-col-3 field"><%= translations.street %></div>',
                                    '<div class="grid-col-9"><%= data.street %></div>',
                                '</div>',
                                '<div class="grid-row no-spacing">',

                                    '<div class="grid-col-3 field"><%= translations.number %>:</div>',
                                    '<div class="grid-col-9"><%= data.number %></div>',
                                '</div>',

                                '<div class="grid-row no-spacing">',
                                    '<div class="grid-col-3 field"><%= translations.code %>:</div>',
                                    '<div class="grid-col-9"><%= data.code %></div>',
                                '</div>',

                                '<div class="grid-row no-spacing">',
                                    '<div class="grid-col-3 field"><%= translations.town %>:</div>',
                                    '<div class="grid-col-9"><%= data.town %></div>',
                                '</div>',

                                '<div class="grid-row">',
                                    '<div class="grid-col-3 field"><%= translations.country %>:</div>',
                                    '<div class="grid-col-9"><%= data.country %></div>',
                                '</div>',
                                '<div class="grid-row">',
                                    '<div class="grid-col-3 field"><%= translations.coordinates %>:</div>',
                                    '<div class="grid-col-9"><%= data.coordinates %></div>',
                                '</div>',
                            '</div>',
                        '</div>',
                    '</div>',
            ].join(''),
            overlay: [
                '<div class="<%= constants.overlayClass %> grid">',
                    '<form id="<%= constants.formId %>">',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-6">',
                                '<label for="map_provider">Map Provider</label>',
                                '<select class="form-element" name="map_provider" class="map-provider" data-mapper-property="map_provider">',
                                    '<% _.each(mapProviders, function ($v, $i) { %>',
                                        '<option value="<%= $i %>"><%= $v.title %></option>',
                                    '<% }); %>',
                                '</select>',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-12">',
                                '<label for="title"><%= translations.title %></label>',
                                '<input class="form-element" type="text" data-mapper-property="title" value="<%= data.title %>"/ >',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-6">',
                                '<label for="street"><%= translations.street %></label>',
                                '<input class="form-element" type="text" data-mapper-property="street" value="<%= data.street %>"/ >',
                            '</div>',
                            '<div class="form-group grid-col-6">',
                                '<label for="number"><%= translations.number %></label>',
                                '<input class="form-element" type="text" data-mapper-property="number" value="<%= data.number %>"/ >',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-6">',
                                '<label for="code"><%= translations.code %></label>',
                                '<input class="form-element" type="text" data-mapper-property="code" value="<%= data.code %>"/ >',
                            '</div>',
                            '<div class="form-group grid-col-6">',
                                '<label for="town"><%= translations.town %></label>',
                                '<input class="form-element" type="text" data-mapper-property="town" value="<%= data.town %>"/ >',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-6">',
                                '<label for="country"><%= translations.country %></label>',
                                '<select class="form-element" name="country" data-mapper-property="country">',
                                    '<% _.each(countries, function (name, key) { %>',
                                        '<option value="<%= key %>"><%= name %></option>',
                                    '<% }); %>',
                                '</select>',
                            '</div>',
                            '<div class="form-group grid-col-6">',
                                '<label for="coordinates"><%= translations.coordinates %></label>',
                                '<input class="form-element" type="text" data-mapper-property="coordinates" value="<%= data.coordinates %>"/ >',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="grid-col-12">',
                                '<img src="/bundles/sululocation/js/test/map.png"/>',
                            '</div>',
                            '<div class="small-font grey-font">Move pointer to change location on map</div>',
                        '</div>',
                    '</form>',
                '</div>'
            ].join('')
        };


    return {
        options: {},
        $button: null,

        data: {},
        formData: {},

        /**
         * Wrap the underscore _.template call and add some
         * default params
         */
        _template: function (name, params) {
            var tmpl = templates[name];
            var params = this.sandbox.util.extend(true, {}, {
                constants: constants,
                translations: this.options.translations
            }, params);

            return _.template(tmpl, params);
        },

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.data = this.sandbox.util.extend(true, {}, dataDefaults, this.sandbox.dom.data(this.$el, 'location'));
            this.createComponent();
        },

        createComponent: function () {
            this.render();
            this.renderContent();
            this.renderMap();
            this.bindEvents();
            this.startOverlay();
        },

        renderMap: function () {
            this.sandbox.dom.find('#' + constants.mapElementId).empty();
            require(['map/leaflet'], function (map) {
                map.show(constants.mapElementId, 47.366915, 9.77052, 11);
            });
        },

        bindEvents: function () {
            this.sandbox.on('husky.overlay.location-content.location.opened', this.createForm.bind(this));
        },

        render: function () {
            this.sandbox.dom.html(this.$el, this._template('skeleton', {}));
        },

        renderContent: function () {
            this.sandbox.dom.find('.' + constants.contentClass).empty().html(
                this._template('content', {
                    data: this.data
                })
            );
        },

        createForm: function () {
            this.sandbox.form.create('#' + constants.formId);
        },

        startOverlay: function () {
            this.formData = this.data;

            var $element = this.sandbox.dom.createElement('<div></div>');
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: '.' + constants.configureButtonClass,
                        el: $element,
                        container: this.$el,
                        instanceName: 'location-content.' + this.options.instanceName,
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.configureLocation),
                                data: this._template('overlay', {
                                    data: this.formData,
                                    mapProviders: this.options.mapProviders,
                                    countries: this.options.countries
                                }),
                                okCallback: function () {
                                    // @todo: Validation
                                    this.data = this.sandbox.form.getData('#' + constants.formId);
                                    this.sandbox.dom.data(this.$el, 'location', this.data);
                                    this.sandbox.emit('sulu.content.changed');
                                    this.renderContent();
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]);
        }
    }
})
