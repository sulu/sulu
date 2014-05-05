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
 * @param {String} [options.overlay.triggerEl] id/class of trigger element(s)
 * @param {String} [options.overlay.title] title of the overlay
 * @param {Function} [options.overlay.okCallback]
 * @param {Function} [options.overlay.closeCallback]
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
                overlayContainer: null,
                triggerEl: null,
                title: ""
            },
            url: null,
            template: "",
            data: null
        },

        constants = {

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

            if(!!this.options.url) {
                this.options.data = this.loadData();
            }

            this.options.overlay.data = this.getConfigForOverlay(this.sandbox.util.template(this.options.template, this.options.data));

            this.startOverlayComponent(this.options.overlay);
            this.bindCustomEvents();
            this.bindDomEvents();
            this.sandbox.emit(INITIALZED.call(this));
        },

        /**
         * Returns configuration object for overlay
         */
        getConfigForOverlay: function(content){
            return {
              triggerEl: this.options.triggerEl,
                title: this.options.title,
                container: this.options.overlayContainer,
                data: content,
                closeCallback: this.options.closeCallback,
                okCallBack: this.options.okCallback
            };
        },

        /**
         * Bind custom related events
         */
        bindCustomEvents: function() {
            this.sandbox.on('husky.'+this.options.overlayInstanceName+'.undefined.closed', function(data){
                if(!!data) {
                    this.saveNewData(data);
                }
            }.bind(this));
        },

        /**
         * Loads data from URL
         * @returns {Object} data object
         */
        loadData: function(){
            return {};
        },

        /**
         * Saves data
         * @param data
         */
        saveNewData: function(data){

        },

        /**
         * Bind dom events
         */
        bindDomEvents: function() {

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
