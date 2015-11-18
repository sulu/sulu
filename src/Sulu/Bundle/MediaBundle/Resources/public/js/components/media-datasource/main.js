/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var defaults = {
            rootUrl: null,
            selectedUrl: null,
            resultKey: null,
            selected: null,
            webspace: null,
            locale: null,
            selectCallback: function(item) {
            }
        },
        columnNavigationDefaults = {
            actionIcon: 'fa-check-circle',
            sortable: false,
            showStatus: false,
            responsive: false,
            showOptions: false
        },

        /**
         * namespace for events
         * @type {string}
         */
        eventNamespace = 'smart-content.datasource.';

    return {

        events: {
            names: {
                setSelected: {
                    postFix: 'set-selected',
                    type: 'on'
                }
            },
            namespace: eventNamespace
        },

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
                    instanceName: 'smart-content-' + this.options.instanceName,
                    resultKey: this.options.resultKey,
                    url: this.getUrl(),
                    selected: this.selected,
                    actionCallback: function(item) {
                        this.selected = item.id;
                        this.options.selectCallback(item.id, item.title);
                    }.bind(this)
                });

            // start child components and bind events
            this.startColumnNavigation(this.columnNavigationOptions).then(this.bindCustomEvents.bind(this));
        },

        /**
         * Set new selected and update column-navigation.
         *
         * @param {String} selected
         */
        setSelected: function(selected) {
            this.selected = selected;

            this.sandbox.emit(
                'husky.column-navigation.smart-content-' + this.options.instanceName + '.set-options',
                {selected: this.selected, url: this.getUrl()}
            );
        },

        /**
         * Returns url for column-navigation.
         *
         * @returns {String}
         */
        getUrl: function() {
            if (!!this.selected) {
                return this.prepareUrl(this.options.selectedUrl);
            }

            return this.prepareUrl(this.options.rootUrl);
        },

        /**
         * Prepare url for column-navigation
         *
         * @param {String} url
         *
         * @returns {String}
         */
        prepareUrl: function(url) {
            url = url.replace('{locale}', this.options.locale);
            url = url.replace('{datasource}', this.selected);

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
            // setter for selected
            this.events.setSelected(this.setSelected.bind(this));
        }
    };
});
