/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class Labels
 * @constructor
 *
 * @param {String} [options.overlay] object which will accept same arguments as husky overlay
 * @param {String} [options.overlay.instanceName] instancename
 * @param {String} [options.overlay.container] selector for overlay container
 * @param {String} [options.overlay.triggerEl] id/class of trigger element(s)
 * @param {String} [options.overlay.title] title of the overlay
 *
 * @param {String} [options.url] url to fetch data from
 * @param {Array} [options.data] data to display in the template
 * @param {String} [options.template] underscore template
 */

define([], function() {

    'use strict';

    var defaults = {

            overlay: {
                instanceName: 'overlay',
                container: null,
                triggerEl: null,
                title: ''
            },

            url: null,

            template:   '<div class="grid-row" data-id="<%= id%>">'+
                        '   <div class="grid-col-8 pull-left"><input class="form-element" type="text" value="<%= value&>"/></div>'+
                        '   <div class="grid-col-2 pull-right"><div class="removeRow btn gray-dark fit only-icon pull-right"><div class="icon-circle-minus"></div></div></div>'+
                        '</div>',

            templateRow:   ['<div class="content-inner">',
                            '   <% _.each(, function(id, value) { %>',
                            '       <div class="grid-row" data-id="<%= id%>">',
                            '           <div class="grid-col-8 pull-left"><input class="form-element" type="text" value="<%= value&>"/></div>',
                            '           <div class="grid-col-2 pull-right"><div class="delete btn gray-dark fit only-icon pull-right"><div class="icon-circle-minus"></div></div></div>',
                            '       </div>',
                            ' <% }); %>',
                            '</div>'].join(''),
            data: null
        },

        constants = {
            overlayContentSelector: '.overlay-content',
            templateRemoveSelector: '.removeRow',
            templateAddSelector: '.addRow'
        },

        eventNamespace = 'sulu.types.',

        /**
         * error label event
         *
         * @event sulu.labels.error.show
         */
            INITIALZED = function() {
            return createEventName.call(this, 'initialzed');
        },

        createEventName = function(postFix) {
            return eventNamespace + postFix;
        };

    return {

        view: true,

        /**
         * Waits for the App-Component to start,
         * then continues with the initialization
         */
        initialize: function() {

            // TODO update husky
            // TODO loader?
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            if (!!this.options.url) {
                this.options.data = this.loadData();
            }

            this.options.overlay.data = this.sandbox.util.template(this.options.template, this.options.data);
            this.startOverlayComponent(this.options.overlay);

            // TODO timing issue?
            this.$overlay = this.sandbox.dom.find(this.options.overlay.container);
            this.$overlayContent = this.sandbox.dom.find(constants.overlayContentSelector);

            this.bindCustomEvents();
            this.bindDomEvents();
            this.sandbox.emit(INITIALZED.call(this));
        },

        /**
         * Loads data from URL
         * @returns {Object} data object
         */
        loadData: function() {

            this.sandbox.util.load(this.options.url)
                .then(function(response){
                    return response;
                    // TODO loaded event

                }.bind(this)).fail(function(status,error){
                    this.sandbox.logger.error(status,error);
                    return null;
                }.bind(this));
        },

        /**
         * Saves data
         * @param data
         */
        saveNewData: function(domData,method) {

            var data = this.parseDataFromDom(domData);

            this.sandbox.util.save(this.options.url,method, data)
                .then(function(response){
                    return response;
                    // TODO saved event

                }.bind(this)).fail(function(status,error){
                    this.sandbox.logger.error(status,error);
                    return null;
                }.bind(this));
        },

        /**
         * Extracts data from dom structure
         */
        parseDataFromDom: function(domData){
            // TODO
            return {};
        },

        /**
         * Removes data item with specific id
         * @param id
         */
        removeData: function(id) {
//            this.sandbox.util.save(this.options.url,'DELETE', data)
//                .then(function(response){
//                    return response;
//                    // TODO removed event
//
//                }.bind(this)).fail(function(status,error){
//                    this.sandbox.logger.error(status,error);
//                    return null;
//                }.bind(this));
        },

        /**
         * Bind dom events
         */
        bindDomEvents: function() {

            // bind click on remove icon
            this.sandbox.dom.on(constants.templateRemoveSelector, 'click', function(event) {
                var $row = this.sandbox.dom.parent(this.sandbox.dom.parent(event.currentTarget)),
                    id = this.sandbox.dom.data('id',$row);
                this.removeData(id);
            }.bind(this), this.$overlay);

            // bind click on add icon
            this.sandbox.dom.on(constants.templateAddSelector, 'click', function() {
                this.sandbox.dom.append(this.options.templateRow, this.$overlayContent);
            }.bind(this), this.$overlay);

        },

        /**
         * Bind custom related events
         */
        bindCustomEvents: function() {
            this.sandbox.on('husky.' + this.options.overlayInstanceName + '.closed', function(data) {
                if (!!data) {
                    this.saveNewData(data);
                }

                // TODO event for finish?
            }.bind(this));
        },

        /**
         * Starts the husky component
         * @param configs
         */
        startOverlayComponent: function(configs) {
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: configs
                }
            ]);
        }
    };
});
