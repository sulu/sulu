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
                 locateAddress: 'Locate Address',
                 title: 'Title',
                 street: 'Street',
                 number: 'Number',
                 code: 'Code',
                 town: 'Town',
                 country: 'Country',
                 coordinates: 'Coordinates (Long / Lat / Zoom)',
                 map: 'Map',
                 search: 'Search'
             },
             instanceName: null,
             mapProviders: {},
             countries: {},
             geolocationUrl: ''
        },

        dataDefaults = {
            title: '',
            street: '',
            number: '',
            code: '',
            country: '',
            location: {
                long: '',
                lat: '',
                zoom: ''
            },
            mapProvider: 'leaflet'
        },

        constants = {
            contentContainerClass: 'location-content-container',
            contentClass: 'location-content',
            configureButtonClass: 'location-content-configure',
            overlayClass: 'location-overlay-content',
            formId: 'location-content-overlay-form',
            mapElementId: 'location-map',
            mapElementClass: 'location-map',
            locateAddressClass: 'location-locate-address-button',
            geolocatorSearchClass: 'geolocator-search'
        },

        events = {
            RELOAD_DATA: 'sulu.location.reload_data'
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
                                    '<div class="grid-col-9"><%= data.location.long %>, <%= data.location.lat %>, <%= data.location.zoom %></div>',
                                '</div>',
                            '</div>',
                        '</div>',
                    '</div>',
            ].join(''),
            overlay: [
                '<div class="<%= constants.overlayClass %> grid">',
                    '<form id="<%= constants.formId %>">',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-12">',
                                '<label for="title"><%= translations.title %></label>',
                                '<input class="form-element" type="text" placeholder="<%= translations.title %>" data-mapper-property="title" value="<%= data.title %>"/ >',
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
                        '</div>',
                        '<h2 class="divider"><%= translations.map %></h2>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-6">',
                                '<label for="map_provider">Map Provider</label>',
                                '<select class="form-element" name="map_provider" class="map-provider" data-mapper-property="map-provider">',
                                    '<% _.each(mapProviders, function ($v, $i) { %>',
                                        '<option value="<%= $i %>"><%= $v.title %></option>',
                                    '<% }); %>',
                                '</select>',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-12">',
                                '<label for="title"><%= translations.search %></label>',
                                '<div class="<%= constants.geolocatorSearchClass %>" type="text" placeholder="<% translations.search %>" ></div>',
                            '</div>',
                        '</div>',
                        '<div class="grid-row no-spacing">',
                            '<div class="form-group grid-col-12">',
                                '<label><%= translations.coordinates %></label>',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-5">',
                                '<input class="form-element" type="text" data-mapper-property="location.long" value="<%= data.location.long %>"/ >',
                            '</div>',
                            '<div class="form-group grid-col-5">',
                                '<input class="form-element" type="text" data-mapper-property="location.lat" value="<%= data.location.lat %>"/ >',
                            '</div>',
                            '<div class="form-group grid-col-2">',
                                '<input class="form-element" type="text" data-mapper-property="location.zoom" value="<%= data.location.zoom %>"/ >',
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
        overlayContent: null,

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
            this.loadData();
            this.createComponent();
        },

        /**
         * Load the data from the DOM element
         */
        loadData: function () {
            this.data = this.sandbox.util.extend(true, {}, dataDefaults, this.sandbox.dom.data(this.$el, 'location'));
        },

        getFormData: function () {
            var data = this.sandbox.form.getData('#' + constants.formId);
            return data;
        },

        /**
         * Create the component when initialized
         */
        createComponent: function () {
            this.renderSkeleton();
            this.renderContent();
            this.renderMap();
            this.startOverlay();
            this.bindEvents();
        },

        /**
         * Bind events to the component
         */
        bindEvents: function () {
            this.sandbox.on('husky.overlay.location-content.' + this.options.instanceName + '.opened', this.createForm.bind(this));
            this.sandbox.on(events.RELOAD_DATA, function () {
                this.loadData();
                this.formData = this.data;

                this.renderContent();
                this.renderMap();
            }.bind(this));
        },

        /**
         * Render the "skeleton" container
         */
        renderSkeleton: function () {
            this.sandbox.dom.html(this.$el, this._template('skeleton', {}));
        },

        /**
         * Render the (read only) content, i.e. not the overlay.
         */
        renderContent: function () {
            this.sandbox.dom.find('.' + constants.contentClass).empty().html(
                this._template('content', {
                    data: this.data
                })
            );
        },

        /**
         * Render the map using the defined provider
         */
        renderMap: function () {
            this.sandbox.dom.find('#' + constants.mapElementId).empty();
            var loc = this.data.location;
            var providerName = this.data.mapProvider;
            var mapProviderConfig = this.options.mapProviders[providerName];

            if (undefined == mapProviderConfig) {
                alert('Map provider "' + providerName + '" is not configured');
                return;
            }

            require(['map/' + providerName], function (Map) {
                var map = new Map(mapProviderConfig);
                map.show(constants.mapElementId, loc.long, loc.lat, loc.zoom);
            });
        },

        /**
         * Initialize the form (why a separate method?)
         */
        createForm: function () {
            var element = this.sandbox.dom.find('.' + constants.geolocatorSearchClass);
            console.log('helkl');
            console.log(element);
            this.sandbox.form.create('#' + constants.formId);
            this.sandbox.start([
                {
                    name: 'auto-complete@husky',
                    options: {
                        el: element,
                        instanceName: this.options.instanceName + '.geolocator.search',
                        getParameter: 'query',
                        suggestionImg: 'map-marker',
                        remoteUrl: this.options.geolocatorUrl + '?providerName=nominatim',
                        valueKey: 'displayTitle',
                        resultKey: 'locations'
                    }
                }
            ]);
        },

        /**
         * Initialize the overlay
         */
        startOverlay: function () {
            this.formData = this.data;

            var $element = this.sandbox.dom.createElement('<div></div>');
            this.overlayContent = $element;
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
                                    countries: {}
                                }),
                                okCallback: function () {
                                    // @todo: Validation
                                    this.data = this.getFormData();
                                    this.sandbox.dom.data(this.$el, 'location', this.data);
                                    this.sandbox.emit('sulu.content.changed');
                                    this.sandbox.emit(events.RELOAD_DATA);
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]);
        }
    }
})
