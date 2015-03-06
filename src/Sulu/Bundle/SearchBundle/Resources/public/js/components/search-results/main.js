/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class SearchResults
 * @constructor
 */
define([
    'text!sulusearch/components/search-results/main.html',
    'text!sulusearch/components/search-results/search-results.html'
    ], function(mainTpl, searchResultsTpl) {

    'use strict';

    var defaults = {
        instanceName: '',
        searchUrl: '/admin/search'
    },

    createEventName = function(postfix) {
        return 'sulu.search-results.' + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
    },

    /**
     * trigger after initialization has finished
     * @event sulu.search-results.[INSTANCE_NAME].initialized
     */
    INITIALIZED = function() {
        return createEventName.call(this, 'initialized');
    };

    return {
        /**
         * @method initialize
         */
        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.mainTpl = this.sandbox.util.template(mainTpl);
            this.searchResultsTpl = this.sandbox.util.template(searchResultsTpl);

            this.render();
            this.bindEvents();
            this.bindDOMEvents();
            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * @method bindEvents
         */
        bindEvents: function() {
            this.sandbox.on('sulu.dropdown-input.' + this.dropDownInputInstance + '.action', this.dropDownInputActionHandler.bind(this));
            this.sandbox.on('sulu.dropdown-input.' + this.dropDownInputInstance + '.clear', this.dropDownInputClearHandler.bind(this));
        },

        /**
         * @method bindDOMEvents
         */
        bindDOMEvents: function() {
        },

        /**
         * @method render
         */
        render: function() {
            var tpl = this.mainTpl();
            
            this.$el.html(tpl);
            this.createSearchInput();
        },

        /**
         * @method createSearchInput
         */
        createSearchInput: function() {
            this.dropDownInputInstance = 'searchResults';

            this.sandbox.start([{
                name: 'dropdown-input@sulusearch',
                options: {
                    el: this.$el.find('.search-results-bar'),
                    instanceName: this.dropDownInputInstance,
                    preSelectedElement: 0,
                    data: [
                        {
                            'id': 0,
                            'name': 'Everything'
                        },
                        {
                            'id': 1,
                            'name': 'Assets'
                        },
                        {
                            'id': 2,
                            'name': 'Contacts'
                        }
                    ]
                }
            }]);
        },

        /**
         * @type {Object}
         */
        categoryMapping: {
            1: 'assets',
            2: 'contacts'
        },

        /**
         * Fetch the data from the server
         * @method load
         * @param {String} query
         * @param {String} category
         */
        load: function(query, category) {
            var url = this.options.searchUrl + '?q=' + query;

            // if category is 0 search for everything
            if (category) {
                url += '&index[0]=' + this.categoryMapping[category];
            }

            return this.sandbox.util.load(url)
                .then(this.parse.bind(this));
        },

        /**
         * @method parse
         * @param {Object} response
         */
        parse: function(response) {
            return response;
        },

        /**
         * @method dropDownInputActionHandler
         */
        dropDownInputActionHandler: function(data) {
            this.startLoader();
            this.updateResults();
            this.load(data.value, data.selectedElement)
                .then(function(data) {
                    data = this.prepareData(data);
                    this.updateResults(data);
                }.bind(this));
        },

        /**
         * @method dropDownInputClearHandler
         */
        dropDownInputClearHandler: function() {
            this.updateResults();
        },

        /**
         * @method prepareData
         * @param {Array} data
         */
        prepareData: function(data) {
            data = data || [];
            var preparedData = [],
                categoriesStore = {},
                category;

            data.forEach(function(entry) {
                category = entry.document.category;

                if (!categoriesStore[category]) {
                    categoriesStore[category] = {
                        category: category,
                        results: [entry.document]
                    };

                    preparedData.push(categoriesStore[category]);
                } else {
                    categoriesStore[category].results.push(entry.document);
                }
            }.bind(this));

            return preparedData;
        },

        /**
         * @method updateResults
         */
        updateResults: function(data) {
            var tpl = this.searchResultsTpl({
                sections: data || []
            });

            this.stopLoader();
            this.$el.find('.search-results').html(tpl);
        },

        /**
         * Starts a loader for the sidebar
         * @method startLoader
         */
        startLoader: function() {
            var $container = this.sandbox.dom.createElement('<div class="search-results-loader"/>');
            this.sandbox.dom.append(this.$el.find('.search-results-loader-container'), $container);
            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: $container,
                        size: '100px',
                        color: '#ccc'
                    }
                }
            ]);
        },

        /**
         * @method stopLoader
         */
        stopLoader: function() {
            this.sandbox.stop('.search-results-loader');
        }
    };
});
