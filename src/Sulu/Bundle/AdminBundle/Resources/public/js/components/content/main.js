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
        tabsData: null
    };

    return {
        view: true,

        initialize: function() {

            // default
            this.sandbox.util.extend(true, {}, defaults, this.options);

            // skeleton
            this.sandbox.dom.html(this.options.el, '<div id="edit-toolbar"></div><h1>' + this.options.heading + '</h1><div id="content-tabs" /><div id="content-tabs-content" />');

            // bind events (also initializes first component)
            this.bindCustomEvents();

            // initialize tabs
            this.sandbox.start([
                {
                    name: 'tabs@husky',
                    options: {
                        el: '#content-tabs',
                        data: this.options.tabsData,
                        instanceName: 'content'
                    }
                }
            ]);
        },

        bindCustomEvents: function() {
            // load component on start
            this.sandbox.on('husky.tabs.content.initialized', this.startComponent.bind(this));
            // load component after click
            this.sandbox.on('husky.tabs.content.item.select', this.startComponent.bind(this));
        },

        startComponent: function(item) {

            if (item.action === this.action) {
                this.sandbox.logger.log("page already loaded; no reload required!");
                return;
            }
            // resets store to prevent duplicated models
            this.sandbox.mvc.Store.reset();

            if (!!item && !!item.contentComponent) {
                var options = this.sandbox.util.extend(true, {}, {el: '#content-tabs-content'}, item.contentComponentOptions, this.options.contentOptions);
                this.sandbox.start([
                    {name: item.contentComponent, options: options}
                ]);
            }
            this.action = item.action;
        }
    };
});
