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
    'config',
    'text!sulusearch/components/search-results/main.html',
    'text!sulusearch/components/search-results/search-results.html'
], function(Config, mainTemplate, searchResultsTemplate) {

    'use strict';

    var defaults = {
            instanceName: '',
            searchUrl: '/admin/search',
            enabledCategoriesUrl: '/admin/search/categories',
            pageLimit: 100,
            displayLogo: false
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
            this.mainTemplate = this.sandbox.util.template(mainTemplate);
            this.searchResultsTemplate = this.sandbox.util.template(searchResultsTemplate);
            this.enabledCategories = [];
            this.categories = {};
            this.categoriesStore = {};
            this.totals = {};
            this.state = {
                page: 1,
                pages: 1,
                loading: false,
                hasNextPage: true,
                category: 'all',
                query: ''
            };

            this.loadCategories().then(function() {
                this.render();
                this.startInfiniteScroll();
                this.bindEvents();
                this.bindDomEvents();
                this.displayLogo();
                this.sandbox.emit(INITIALIZED.call(this));
            }.bind(this));
        },

        /**
         * @method bindEvents
         */
        bindEvents: function() {
            this.sandbox.on('sulu.data-overlay.show', this.focusInput.bind(this));
            this.sandbox.on(
                'sulu.dropdown-input.' + this.dropDownInputInstance + '.action',
                this.dropDownInputActionHandler.bind(this)
            );
            this.sandbox.on(
                'sulu.dropdown-input.' + this.dropDownInputInstance + '.clear',
                this.dropDownInputClearHandler.bind(this)
            );
            this.sandbox.on(
                'sulu.dropdown-input.' + this.dropDownInputInstance + '.change',
                this.dropDownInputActionHandler.bind(this)
            );
        },

        /**
         * @method bindDomEvents
         */
        bindDomEvents: function() {
            this.$el.on('click', '.search-results-section li', this.openSearchEntry.bind(this));
        },

        /**
         * @method render
         */
        render: function() {
            var template = this.mainTemplate({
                translate: this.sandbox.translate
            });

            this.$el.html(template);
            this.createSearchInput();
            this.createSearchTotals();
        },

        /**
         * @method displayLog
         */
        displayLogo: function() {
            if (true === this.options.displayLogo) {
                var $logo = this.$el.find('.logo').first();
                $logo.removeClass('hidden');
                this.sandbox.util.delay(function() {
                    $logo.addClass('is-visible');
                }, 10);
            }
        },

        /**
         * @method displayLog
         */
        hideLogo: function() {
            var $logo = this.$el.find('.logo').first();
            $logo.addClass('hidden');
            $logo.removeClass('is-visible');
        },

        /**
         * @method focusInput
         */
        focusInput: function() {
            this.sandbox.emit('sulu.dropdown-input.' + this.dropDownInputInstance + '.focus');
        },

        /**
         * @method addCategory
         * @param {String} category
         */
        addCategory: function(category) {
            var categoryEntry = {
                'id': category,
                'name': this.sandbox.translate('search-overlay.category.' + category + '.title'),
                'placeholder': this.sandbox.translate('search-overlay.placeholder.' + category)
            };

            this.categories[category] = categoryEntry;
            this.enabledCategories.push(categoryEntry);
        },

        /**
         * @method startInfiniteScroll
         */
        startInfiniteScroll: function() {
            var $iscroll = this.$el.find('.iscroll');
            this.sandbox.infiniteScroll.initialize($iscroll, this.loadNextPage.bind(this), 50);
        },

        /**
         * @method loadCategories
         */
        loadCategories: function() {
            return this.sandbox.util.load(this.options.enabledCategoriesUrl)
                .then(function(data) {
                    this.addCategory('all');
                    data.forEach(this.addCategory.bind(this));
                }.bind(this));
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
                    preSelectedElement: 'all',
                    data: this.enabledCategories,
                    focused: true
                }
            }]);
        },

        createSearchTotals: function() {
            this.searchTotalsInstanceName = 'searchTotals';
            this.sandbox.start([{
                name: 'search-totals@sulusearch',
                options: {
                    el: this.$el.find('.search-totals'),
                    instanceName: this.searchTotalsInstanceName,
                    categories: this.categories
                }
            }]);
        },

        /**
         * Fetch the data from the server
         * @method load
         */
        load: function() {
            var params = {
                    q: this.state.query,
                    page: this.state.page,
                    limit: this.options.pageLimit
                },

                url = this.options.searchUrl + '/query?' + $.param(params),
                category = this.state.category;

            // if category is 'all' search for everything
            if (category && category !== 'all') {
                url += '&category=' + category;
            }

            return this.sandbox.util.load(url)
                .then(this.parse.bind(this));
        },

        /**
         * @method loadNextPage
         */
        loadNextPage: function() {
            var def = this.sandbox.data.deferred();

            if (!!this.state.hasNextPage && !this.state.loading) {
                this.startLoader();

                this.state.page++;

                return this.load()
                    .then(this.mergeResults.bind(this))
                    .then(this.updateResults.bind(this))
                    .then(this.updateTotals.bind(this));
            } else {
                def.resolve();
            }
            return def;
        },

        /**
         * @method parse
         * @param {Object} response
         */
        parse: function(response) {
            var data = response._embedded.result || [],
                preparedData = {},
                category,
                deepUrl;

            this.state.page = response.page;
            this.state.pages = response.pages;
            this.state.loading = false;
            this.state.hasNextPage = response.page < response.pages;
            this.totals = response.totals;

            data.forEach(function(entry) {
                var options;

                category = entry.document.category;
                deepUrl = this.getEntryDeepUrl(category, entry.document);
                entry.document.deepUrl = deepUrl;
                options = Config.get('sulusearch.' + category + '.options') || {};

                if (!preparedData[category]) {
                    preparedData[category] = {
                        category: category,
                        results: [entry.document],
                        options: this.sandbox.util.extend(true, {}, {
                            image: true
                        }, options)
                    };
                } else {
                    preparedData[category].results.push(entry.document);
                }

            }.bind(this));

            return preparedData;
        },

        /**
         * @method mergeResults
         * @param {Object} data
         */
        mergeResults: function(data) {
            if (data) {
                Object.keys(data).forEach(function(key) {
                    if (!this.categoriesStore[key]) {
                        this.categoriesStore[key] = data[key];
                    } else {
                        this.categoriesStore[key].results = this.categoriesStore[key].results.concat(data[key].results);
                    }
                }.bind(this));
            }

            return this.categoriesStore;
        },

        /**
         * @method dropDownInputActionHandler
         */
        dropDownInputActionHandler: function(data) {
            if (!!data.value) {
                this.state.query = data.value;
                this.state.category = data.selectedElement;
                this.state.page = 1;
                this.categoriesStore = {};

                this.hideLogo();
                this.startLoader();
                this.updateResults();
                this.load().then(function(data) {
                    this.categoriesStore = data;
                    this.updateResults(data);
                    this.updateTotals(data);
                }.bind(this));
            }
        },

        /**
         * @method dropDownInputClearHandler
         */
        dropDownInputClearHandler: function() {
            this.updateResults();

            // reset totals
            this.totals = {};
            this.updateTotals();
        },

        /**
         * @method getEntryDeepUrl
         * @param {String} category
         * @param {Object} data
         */
        getEntryDeepUrl: function(category, data) {
            var deepUrl = this.sandbox.urlManager.getUrl(category, data);

            return deepUrl;
        },

        /**
         * @type {Object}
         */
        categoryIconMapping: {
            'contact': 'fa-user',
            'page': 'fa-file-o',
            'snippet': 'fa-file',
            'account': 'fa-university'
        },

        /**
         * @method getTemplate
         * @param {Object} data
         */
        getTemplate: function(data) {
            var sections = null;

            if (data) {
                sections = [];

                Object.keys(data).forEach(function(key) {
                    sections.push(data[key]);
                }.bind(this));
            }

            return this.searchResultsTemplate({
                sections: sections,
                categories: this.categories,
                totals: this.totals,
                categoryIconMapping: this.categoryIconMapping,
                translate: this.sandbox.translate
            });
        },

        /**
         * @method updateResults
         */
        updateResults: function(data) {
            var template = this.getTemplate(data);

            this.stopLoader();
            this.$el.find('.search-results').html(template);
        },

        updateTotals: function(data) {
            this.sandbox.emit(
                'sulu.search-totals.' + this.searchTotalsInstanceName + '.update',
                this.totals,
                this.state.category
            );
        },

        /**
         * @method openSearchEntry
         */
        openSearchEntry: function(event) {
            var $element = $(event.currentTarget),
                url = $element.data('url');

            if (!!url) {
                this.sandbox.emit('sulu.router.navigate', url);
                this.sandbox.emit('sulu.data-overlay.hide');
            }
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
