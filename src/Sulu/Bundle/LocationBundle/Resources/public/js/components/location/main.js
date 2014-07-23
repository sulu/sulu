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

        mapDefaults = {
            draggableMarker: false,
            positionUpdateCallback: null
        },

        dataDefaults = {
            title: '',
            street: '',
            number: '',
            code: '',
            country: '',
            long: 0,
            lat: 0,
            zoom: 0,
            mapProvider: 'leaflet'
        },

        constants = {
            contentContainerClass: 'location-content-container',
            contnetFieldContainerClass: 'location-content-fields-container',
            contentClass: 'location-content',
            configureButtonClass: 'location-content-configure',
            overlayClass: 'location-overlay-content',
            formId: 'location-content-overlay-form',
            mapElementId: 'location-map',
            mapElementClass: 'location-map',
            overlayMapElementId: 'location-overlay-map',
            overlayMapElementClass: 'location-overlay-map',
            locateAddressClass: 'location-locate-address-button',
            geolocatorSearchClass: 'geolocator-search',
            contentFieldContainerClass: 'location-content-field-container'
        },

        events = {
            RELOAD_DATA: 'sulu.location.reload_data'
        },

        templates = {
            skeleton: [
                '<div class="<%= constants.contentContainerClass %> white-box form-element">',
                    '<div class="header"><span class="fa-gears <%= constants.configureButtonClass %> icon right border"></span></div>',
                    '<div class="content <%= constants.contentClass %>">',
                        '<div class="grid-row">',
                            '<div class="grid-col-6 container">',
                                '<div id="<%= constants.mapElementId %>" class="content <%= constants.mapElementClass %>"></div>',
                            '</div>',
                            '<div class="grid-col-6 <%= constants.contentFieldContainerClass %>">',
                            '</div>',
                        '</div>',
                    '</div>',
                '</div>',
            ].join(''),
            contentFields: [
                '<div class="container grid">',
                    '<div class="grid-row">',
                        '<div class="grid-col-3 text"><%= translations.title %>:</div>',
                        '<div class="grid-col-9"><%= data.title %></div>',
                    '</div>',
                    '<div class="grid-row no-spacing">',
                        '<div class="grid-col-3 text"><%= translations.street %></div>',
                        '<div class="grid-col-9"><%= data.street %></div>',
                    '</div>',
                    '<div class="grid-row no-spacing">',

                        '<div class="grid-col-3 text"><%= translations.number %>:</div>',
                        '<div class="grid-col-9"><%= data.number %></div>',
                    '</div>',

                    '<div class="grid-row no-spacing">',
                        '<div class="grid-col-3 text"><%= translations.code %>:</div>',
                        '<div class="grid-col-9"><%= data.code %></div>',
                    '</div>',

                    '<div class="grid-row no-spacing">',
                        '<div class="grid-col-3 text"><%= translations.town %>:</div>',
                        '<div class="grid-col-9"><%= data.town %></div>',
                    '</div>',

                    '<div class="grid-row">',
                        '<div class="grid-col-3 text"><%= translations.country %>:</div>',
                        '<div class="grid-col-9"><%= data.country %></div>',
                    '</div>',
                    '<div class="grid-row">',
                        '<div class="grid-col-3 text"><%= translations.coordinates %>:</div>',
                        '<div class="grid-col-9"><%= data.long %>, <%= data.lat %>, <%= data.zoom %></div>',
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
                                        '<option <% if (key == data.country) { %>selected="selected" <% }; %>value="<%= key %>"><%= name %></option>',
                                    '<% }); %>',
                                '</select>',
                            '</div>',
                        '</div>',
                        '<h2 class="divider"><%= translations.map %></h2>',
                        '<div class="grid-row">',
                            '<div class="form-group grid-col-6">',
                                '<label for="map_provider">Map Provider</label>',
                                '<select class="form-element" name="map_provider" class="map-provider" data-mapper-property="mapProvider">',
                                    '<% _.each(mapProviders, function (provider, key) { %>',
                                        '<option <% if (key == data.mapProvider) { %>selected="selected" <% }; %>value="<%= key %>"><%= provider.title %></option>',
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
                        '<div class="grid-row coordinate-fields">',
                            '<div class="form-group grid-col-5">',
                                '<input class="form-element longitude" type="text" data-mapper-property="long" value="<%= data.long %>"/ >',
                            '</div>',
                            '<div class="form-group grid-col-5">',
                                '<input class="form-element latitude" type="text" data-mapper-property="lat" value="<%= data.lat %>"/ >',
                            '</div>',
                            '<div class="form-group grid-col-2">',
                                '<input class="form-element zoom" type="text" data-mapper-property="zoom" value="<%= data.zoom %>"/ >',
                            '</div>',
                        '</div>',
                        '<div class="grid-row">',
                            '<div class="grid-col-12">',
                                '<div id="<%= constants.overlayMapElementId %>" class="<%= constants.overlayMapElementClass %>"/>',
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
        $formContent: null,
        overlayContent: null,

        // object containing map domId => mapInstances
        mapInstances: {},

        data: {},
        formData: {},

        /**
         * Wrap the underscore _.template call and add some
         * default params
         */
        _template: function (name, params) {
            var tmpl = templates[name];
            var tmplParams = this.sandbox.util.extend(true, {}, {
                constants: constants,
                translations: this.options.translations
            }, params);

            return this.sandbox.util.template(tmpl, tmplParams);
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
            console.log(this.data);
        },

        getFormData: function () {
            var data = this.sandbox.form.getData('#' + constants.formId);
            return data;
        },

        initializeFormContent: function () {
            this.formData = this.data;
            this.$formContent = this.sandbox.dom.createElement(this._template('overlay', {
                data: this.formData,
                mapProviders: this.options.mapProviders,
                countries: this.options.countries
            }));
        },

        /**
         * Create the component when initialized
         */
        createComponent: function () {
            this.renderSkeleton();
            this.renderContentFields();
            this.renderMap(constants.mapElementId, this.data);
            this.startOverlay();
            this.bindEvents();
        },

        /**
         * Bind events to the component
         */
        bindEvents: function () {
            // Initialize form when overlay is opened
            this.sandbox.on('husky.overlay.location-content.' + this.options.instanceName + '.opened', this.createForm.bind(this));
            this.sandbox.on('husky.overlay.location-content.' + this.options.instanceName + '.initialized', function () {
                this.startFormComponents();
            }.bind(this));

            // update the location when user chooses a location from autoselect
            this.sandbox.on(
                'husky.auto-complete.' + this.options.instanceName + '.geolocator.search.select',
                this.updateLocationFromLocation.bind(this)
            );

            this.sandbox.on(events.RELOAD_DATA, function () {
                // reload the data from the DOM
                this.loadData();

                // reinitialize the form data
                this.formData = this.data;

                this.renderContentFields();
                this.renderMap(constants.mapElementId, this.data);
            }.bind(this));
        },

        // Update the location from the location object returned from
        // the webservice API
        updateLocationFromLocation: function (location) {
            this.updateCoordinates(location.longitude, location.latitude);
            this.renderMap(constants.overlayMapElementId, {
                'long': location.longitude,
                'lat': location.latitude,
                'zoom': this.formData.zoom
            });
        },

        updateLocation: function () {
            this.renderMap(constants.overlayMapElementId, {
                'long': this.formData.long,
                'lat': this.formData.lat,
                'zoom': this.formData.zoom
            });
        },

        updateCoordinates: function (long, lat, zoom) {
            var form = $('#' + constants.formId);
            if (long) {
                this.sandbox.dom.find('.longitude', form).val(long);
            }

            if (lat) {
                this.sandbox.dom.find('.latitude', form).val(lat);
            }

            if (zoom) {
                this.sandbox.dom.find('.zoom', form).val(zoom);
            }
        },

        /**
         * Render the "skeleton" container
         */
        renderSkeleton: function () {
            this.sandbox.dom.html(this.$el, this._template('skeleton', this.data));
        },

        /**
         * Render the (read only) content, i.e. not the overlay.
         */
        renderContentFields: function () {
            this.sandbox.dom.find('.' + constants.contentFieldContainerClass).empty().html(
                this._template('contentFields', {
                    data: this.data
                })
            );
        },

        /**
         * Render the map using the defined provider
         */
        renderMap: function (mapElementId, location, options) {
            var providerName = this.data.mapProvider;
            var mapProviderConfig = this.options.mapProviders[providerName];
            var resolvedOptions = this.sandbox.util.extend({}, mapDefaults, options);

            if (undefined === mapProviderConfig) {
                window.alert('Map provider "' + providerName + '" is not configured');
                return;
            }

            if (undefined === this.mapInstances[mapElementId]) {
                require(['map/' + providerName], function (Map) {
                    var map = new Map(mapElementId, mapProviderConfig, resolvedOptions);
                    map.show(location.long, location.lat, location.zoom);
                    this.mapInstances[mapElementId] = map;
                }.bind(this));
            } else {
                this.mapInstances[mapElementId].show(location.long, location.lat, location.zoom);
            }
        },

        startFormComponents: function () {
            this.sandbox.start([
                {
                    name: 'auto-complete@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.geolocatorSearchClass, this.$formContent),
                        instanceName: this.options.instanceName + '.geolocator.search',
                        getParameter: 'query',
                        suggestionImg: 'map-marker',
                        remoteUrl: this.options.geolocatorUrl + '?providerName=nominatim',
                        valueKey: 'name',
                        resultKey: 'locations'
                    }
                }
            ]);
        },

        /**
         * Initialize the form (why a separate method?)
         */
        createForm: function () {
            this.initializeFormContent();
            var element = this.sandbox.dom.find('.' + constants.geolocatorSearchClass);
            this.sandbox.form.create('#' + constants.formId);
            this.renderMap(constants.overlayMapElementId, this.data, {
                // allow the marker to be dragged
                draggableMarker: true,

                // update the coordinates when the marker is dragged
                positionUpdateCallback: function (long, lat) {
                    this.updateCoordinates(long, lat, null);
                }.bind(this),

                // update the zoom when the zoom is changed
                zoomChangeCallback: function (zoom) {
                    this.updateCoordinates(null, null, zoom);
                }.bind(this),
            });

            this.sandbox.dom.find('.coordinate-fields input').on('change', function () {
                var form = $('#' + constants.formId);
                this.formData.long = this.sandbox.dom.find('.longitude', form).val();
                this.formData.lat = this.sandbox.dom.find('.latitude', form).val();
                this.formData.zoom = this.sandbox.dom.find('.zoom', form).val();
                this.updateLocation();
            }.bind(this));
        },

        /**
         * Initialize the overlay
         */
        startOverlay: function () {
            var $element = this.sandbox.dom.createElement('<div></div>');
            this.initializeFormContent();
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
                                data: this.$formContent,
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
    };
});
