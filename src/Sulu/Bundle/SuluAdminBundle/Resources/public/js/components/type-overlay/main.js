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

            template: '<div class="grid-row type-row" data-id="<%= data.id %>">' +
                '   <div class="grid-col-8 pull-left"><input class="form-element" type="text" value="<%= data.value %>"/></div>' +
                '   <div class="grid-col-2 pull-right"><div class="remove-row btn gray-dark fit only-icon pull-right"><div class="icon-circle-minus"></div></div></div>' +
                '</div>',

            templateRow: ['<div class="content-inner">',
                '   <% _.each(data, function(id, value) { %>',
                '       <div class="grid-row type-row" data-id="<%= data.id%>">',
                '           <div class="grid-col-8 pull-left"><input class="form-element" type="text" value="<%= data.value %>"/></div>',
                '           <div class="grid-col-2 pull-right"><div class="remove-row btn gray-dark fit only-icon pull-right"><div class="icon-circle-minus"></div></div></div>',
                '       </div>',
                ' <% }); %>',
                '<div class="grid-row"><div id="addRow" class="addButton"></div></div>',
                '</div>'].join(''),
            data: null
        },

        constants = {
            overlayContentSelector: '.overlay-content',
            templateRemoveSelector: '.remove-row',
            templateAddSelector: '#addRow',
            typeRowSelector: '.type-row'
        },

        eventNamespace = 'sulu.types.',

        /**
         * Initialized event
         * @event sulu.types.initialzed
         */
            INITIALZED = function() {
            return createEventName.call(this, 'initialzed');
        },

        /**
         * Loaded event
         * @event sulu.types.loaded
         */
            LOADED = function() {
            return createEventName.call(this, 'loaded');
        },

        /**
         * Saved event
         * @event sulu.types.saved
         */
            SAVED = function() {
            return createEventName.call(this, 'saved');
        },

        /**
         * Removed event
         * @event sulu.types.removed
         */
            REMOVED = function() {
            return createEventName.call(this, 'removed');
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

            if (!!this.options.url && !this.options.data) {
                this.options.data = this.loadData();
            }

            this.options.overlay.data = this.sandbox.util.template(this.options.template, {data:this.options.data});
            this.startOverlayComponent(this.options.overlay);

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
                .then(function(response) {
                    this.sandbox.emit(LOADED);
                    return response;
                }.bind(this)).fail(function(status, error) {
                    this.sandbox.logger.error(status, error);
                    return null;
                }.bind(this));
        },

        /**
         * Saves data
         * @param data
         */
        saveNewData: function(domData, method) {

            // TODO new and edited
            // TODO delete old

            var data = this.parseDataFromDom(domData);

            this.sandbox.util.save(this.options.url, method, data)
                .then(function(response) {
                    this.sandbox.emit(SAVED, response);
                    return response;
                }.bind(this)).fail(function(status, error) {
                    this.sandbox.logger.error(status, error);
                    return null;
                }.bind(this));
        },

        /**
         * Extracts data from dom structure
         */
        parseDataFromDom: function(domData) {
            var $rows = this.sandbox.dom.find(constants.typeRowSelector, domData),
                data = [],
                id, value;

            this.sandbox.dom.each($rows, function(index, $el) {
                id = this.sandbox.dom.data($el, 'id');
                value = this.sandbox.dom.val(this.sandbox.dom.find($el, 'input'));
                data.push({id: id, value: value});
            }.bind(this));

            return {};
        },

        /**
         * Removes data item with specific id
         * @param id
         */
        removeData: function(id) {

            // TODO set data-delted + faded class

            this.sandbox.util.save(this.options.url, 'DELETE', id)
                .then(function(response) {
                    this.sandbox.emit(REMOVED, id);
                    return response;
                }.bind(this)).fail(function(status, error) {
                    this.sandbox.logger.error(status, error);
                    return null;
                }.bind(this));
        },

        /**
         * Bind dom events
         */
        bindDomEvents: function() {

            // bind click on remove icon
            this.sandbox.dom.on(constants.templateRemoveSelector, 'click', function(event) {

                var $row = this.sandbox.dom.parent(this.sandbox.dom.parent(event.currentTarget)),
                    id = this.sandbox.dom.data($row, 'id');
                if (!!id) {

                    // TODO remove on ok
                    // on click just fade and mark
                    this.removeData(id);
                }
                this.sandbox.dom.remove($row);

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
            this.sandbox.on('husky.' + this.options.overlay.instanceName + '.closed', function(data) {
                if (!!data) {
                    this.saveNewData(data);
                }
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
