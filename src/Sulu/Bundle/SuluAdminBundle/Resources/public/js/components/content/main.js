/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 *
 * options:
 *  - heading - string
 *
 *
 */

define([], function() {

    'use strict';

    var defaults = {
            heading: '',
            headingAddition: '',
            tabsData: null,
            instanceName: 'content',
            template: 'default',
            parentTemplate: null
        },

        templates = {
            skeleton: function() {
                return [
                    '<div id="sulu-header-container"></div>',
                    '   <div id="content-tabs" />',
                    '</div>'
                ].join('');
            }
        };

    return {
        view: true,

        initialize: function() {

            // default
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // skeleton
            this.html(templates.skeleton.call(this));

            // bind events (also initializes first component)
            this.bindCustomEvents();

            // initialize header
            this.initializeHeader();
        },

        /**
         * Starts the sulu-header component
         */
        initializeHeader: function() {
            this.sandbox.start([{
                name: 'header@suluadmin',
                options: {
                    el: '#sulu-header-container',
                    toolbarTemplate: this.options.template,
                    toolbarParentTemplate: this.options.parentTemplate,
                    heading: this.options.heading,
                    tabsData: this.options.tabsData
                }
            }
            ]);
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {
            var instanceName = (this.options.instanceName && this.options.instanceName !== '') ? this.options.instanceName + '.' : '';
            // load component on start
            this.sandbox.on('husky.tabs.header.initialized', this.startTabComponent.bind(this));

            // load component after click
            this.sandbox.on('husky.tabs.header.item.select', this.startTabComponent.bind(this));

            // if the header has initialized move the content down the height of the header
            this.sandbox.on('sulu.header.initialized', this.setTopSpacing.bind(this));
        },

        /**
         * gets called when tabs either got initialized or when tab was clicked
         * @param item
         */
        startTabComponent: function(item) {

            if (!item) {
                item = this.options.tabsData.items[0];
            }

            if (!item.forceReload && item.action === this.action) {
                this.sandbox.logger.log('page already loaded; no reload required!');
                return;
            }

            // resets store to prevent duplicated models
            this.sandbox.mvc.Store.reset();

            this.sandbox.stop('#content-tabs-component');

            this.sandbox.dom.append(this.$el, '<div id="content-tabs-component"></div>');

            if (!!item && !!item.contentComponent) {
                var options = this.sandbox.util.extend(true, {}, this.options.contentOptions, {el: '#content-tabs-component', reset: true }, item.contentComponentOptions);
                // start component defined by
                this.sandbox.start([
                    {name: item.contentComponent, options: options}
                ]);
            }

            if (!!item) {
                this.action = item.action;
            }
        },

        /**
         * Sets the top spacing equal to the height of the header
         */
        setTopSpacing: function() {
            this.sandbox.dom.css(this.$el, {
                'padding-top': this.sandbox.dom.outerHeight(this.$find('#sulu-header-container')) + 'px'
            });
        }
    };
});
