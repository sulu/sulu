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
            enabledIndexesUrl: '/admin/search/indexes',
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
            this.enabledIndexes = [];
            this.indexes = {};
            this.indexesStore = {};
            this.totals = {};
            this.state = {
                page: 1,
                pages: 1,
                loading: false,
                hasNextPage: true,
                index: 'all',
                query: ''
            };

            this.loadIndexes().then(function() {
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
         * @method addIndex
         * @param {String} indexConfiguration
         */
        addIndex: function(indexConfiguration) {
            var indexEntry = {
                'id': indexConfiguration.indexName,
                'name': this.sandbox.translate('search-overlay.index.' + indexConfiguration.indexName + '.title'),
            };

            // use the single translation value for the title if given by the api
            if (!!indexConfiguration.name) {
                indexEntry.name = indexConfiguration.name;
            }

            this.indexes[indexConfiguration.indexName] = indexEntry;
            this.enabledIndexes.push(indexEntry);
        },

        /**
         * @method startInfiniteScroll
         */
        startInfiniteScroll: function() {
            var $iscroll = this.$el.find('.iscroll');
            this.sandbox.infiniteScroll.initialize($iscroll, this.loadNextPage.bind(this), 50);
        },

        /**
         * @method loadIndexes
         */
        loadIndexes: function() {
            return this.sandbox.util.load(this.options.enabledIndexesUrl)
                .then(function(data) {
                    this.addIndex({indexName: 'all'});
                    data.forEach(this.addIndex.bind(this));
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
                    data: this.enabledIndexes,
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
                    indexes: this.indexes
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
                index = this.state.index;

            // if index is 'all' search for everything
            if (index && index !== 'all') {
                url += '&index=' + index;
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
                index,
                deepUrl;

            this.state.page = response.page;
            this.state.pages = response.pages;
            this.state.loading = false;
            this.state.hasNextPage = response.page < response.pages;
            this.totals = response.totals;

            data.forEach(function(entry) {
                var options;

                index = entry.document.index;
                deepUrl = this.getEntryDeepUrl(index, entry.document);
                entry.document.deepUrl = deepUrl;
                options = Config.get('sulusearch.' + index + '.options') || {};

                if (!preparedData[index]) {
                    preparedData[index] = {
                        index: index,
                        results: [entry.document],
                        options: this.sandbox.util.extend(true, {}, {
                            image: true
                        }, options)
                    };
                } else {
                    preparedData[index].results.push(entry.document);
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
                    if (!this.indexesStore[key]) {
                        this.indexesStore[key] = data[key];
                    } else {
                        this.indexesStore[key].results = this.indexesStore[key].results.concat(data[key].results);
                    }
                }.bind(this));
            }

            return this.indexesStore;
        },

        /**
         * @method dropDownInputActionHandler
         */
        dropDownInputActionHandler: function(data) {
            if (!!data.value) {
                this.state.query = data.value;
                this.state.index = data.selectedElement;
                this.state.page = 1;
                this.indexesStore = {};

                this.hideLogo();
                this.startLoader();
                this.updateResults();
                this.load().then(function(data) {
                    this.indexesStore = data;
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
         * @param {String} index
         * @param {Object} data
         */
        getEntryDeepUrl: function(index, data) {
            return this.sandbox.urlManager.getUrl(index, data);
        },

        /**
         * TODO should be injected in some way, so that it is extensible
         * @type {Object}
         */
        indexIconMapping: {
            'contact': 'fa-user',
            'snippet': 'fa-file',
            'account': 'fa-university'
        },

        defaultIcon: 'fa-file-o',

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
                indexes: this.indexes,
                totals: this.totals,
                indexIconMapping: this.indexIconMapping,
                defaultIcon: this.defaultIcon,
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

        updateTotals: function() {
            this.sandbox.emit(
                'sulu.search-totals.' + this.searchTotalsInstanceName + '.update',
                this.totals,
                this.state.index
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
