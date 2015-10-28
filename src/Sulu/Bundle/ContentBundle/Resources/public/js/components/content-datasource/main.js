/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    var defaults = {
            url: null,
            resultKey: null,
            selected: null,
            webspace: null,
            locale: null,
            selectCallback: function(item) {
            }
        },
        columnNavigationDefaults = {
            responsive: false,
            actionIcon: 'fa-check',
            showOptions: false,
            sortable: false,
            showStatus: false
        };

    return {

        /**
         * Initialize component
         */
        initialize: function() {
            this.sandbox.logger.log('initialize', this);

            // merge options with defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.selected = this.options.selected;

            // render
            this.render();

            // merge column-navigation options
            this.columnNavigationOptions = this.sandbox.util.extend(true, {}, columnNavigationDefaults,
                {
                    el: this.$columnNavigationElement,
                    instanceName: 'smart-content.' + this.options.instanceName,
                    url: this.prepareUrl(this.options.url),
                    resultKey: this.options.resultKey,
                    selected: this.options.selected,
                    responsive: false
                });

            // start child components and bind events
            this.startColumnNavigation(this.columnNavigationOptions).then(this.bindCustomEvents.bind(this));
        },

        /**
         * Prepare url for column-navigation
         *
         * @param {String} url
         *
         * @returns {String}
         */
        prepareUrl: function(url) {
            url = url.replace(
                '{id=dataSource&}',
                (!!this.selected ? 'id=' + this.selected + '&' : '')
            );
            url = url.replace('{webspace}', this.options.webspace);
            url = url.replace('{locale}', this.options.locale);

            return url;
        },

        /**
         * Start column-navigation with given options
         *
         * @param {{}} options
         *
         * @returns {{}} Deferred object of component start
         */
        startColumnNavigation: function(options) {
            return this.sandbox.start(
                [{
                    name: 'column-navigation@husky',
                    options: options
                }]
            );
        },

        /**
         * Render container for column-navigation
         */
        render: function() {
            this.$columnNavigationElement = this.sandbox.dom.createElement('<div/>');
            this.html(this.$columnNavigationElement);
        },

        /**
         * Bind events to call select callback
         */
        bindCustomEvents: function() {
            this.sandbox.on('husky.column-navigation.smart-content.' + this.options.instanceName + '.action', function(item) {
                this.selected = item.id;
                this.options.selectCallback(item.id, item.path);
            }.bind(this));
        }
    };
});
