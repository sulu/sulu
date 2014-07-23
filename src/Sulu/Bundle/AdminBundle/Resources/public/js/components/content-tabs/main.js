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
 * @class Content
 * @constructor
 *
 * @param {Object} [options] Configuration object
 * @param {String} [options.instanceName] The name of the instance
 * @param {Object} [options.contentOptions] options to pass to the tabs-content-component
 * @param {Array} [options.tabsData] array of tabs-items. Contains the tabs-content-component as a string
 */

define([], function() {

    'use strict';

    var defaults = {
            instanceName: 'content',
            contentOptions: {},
            tabsData: null
        },

        constants = {
            tabsComponentId: 'content-tabs-component'
        },

        templates = {
            skeleton: function() {
                return [
                    '<div id="content-tabs"></div>'
                ].join('');
            }
        },

        /**
         * trigger after initialization has finished
         *
         * @event sulu.content.[INSTANCE_NAME].initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * Creates the event names
         * @param postfix {string}
         * @returns {string}
         */
        createEventName = function(postfix) {
            return 'sulu.content-tabs.' + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
        };

    return {

        initialize: function() {

            // default
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            var template = this.sandbox.util.template(templates.skeleton.call(this));

            // skeleton
            this.html(template);

            // bind events (also initializes first component)
            this.bindCustomEvents();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {
            // load component on start
            this.sandbox.on('husky.tabs.header.initialized', this.startTabComponent.bind(this));

            // load component after click
            this.sandbox.on('husky.tabs.header.item.select', this.startTabComponent.bind(this));
        },

        /**
         * gets called when tabs either got initialized or when tab was clicked
         * @param item {Object} the Tabs object
         */
        startTabComponent: function(item) {
            var options;
            
            item = item || this.options.tabsData.items[0];

            if (!item.forceReload && item.action === this.action) {
                this.sandbox.logger.log('page already loaded; no reload required!');
                return false;
            }

            // save action
            this.action = item.action;
            
            // resets store to prevent duplicated models
            this.sandbox.mvc.Store.reset();

            // stop the current tab
            App.stop('#' + constants.tabsComponentId + ' *');
            App.stop('#' + constants.tabsComponentId);
            
            // start the new tab-component
            if (!!item.contentComponent) {
                this.sandbox.dom.append(this.$el, this.sandbox.dom.createElement('<div id="'+ constants.tabsComponentId +'"/>'));
                options = this.sandbox.util.extend(true, {}, this.options.contentOptions, {el: '#' + constants.tabsComponentId}, item.contentComponentOptions);
                // start component defined by
                this.sandbox.start([{
                    name: item.contentComponent,
                    options: options
                }]);
            }
        }
    };
});
