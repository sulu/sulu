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
        searchUrl: '/admin/search',
        enabledCategoriesUrl: '/admin/search/categories'
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
            this.dropDownEntries = [];
            this.enabledCategories = [];
            this.categoriesStore = {};
            this.totals = {};
            this.state = {
                page: 1,
                pageCount: 1,
                loading: false,
                hasNextPage: true,
                category: 'all',
                query: ''
            };

            this.setCategories();
            this.loadCategories().then(function() {
                this.render();
                this.startInfiniteScroll();
                this.bindEvents();
                this.bindDOMEvents();
                this.sandbox.emit(INITIALIZED.call(this));
            }.bind(this));
        },

        /**
         * @method bindEvents
         */
        bindEvents: function() {
            this.sandbox.on('sulu.dropdown-input.' + this.dropDownInputInstance + '.action', this.dropDownInputActionHandler.bind(this));
            this.sandbox.on('sulu.dropdown-input.' + this.dropDownInputInstance + '.clear', this.dropDownInputClearHandler.bind(this));
            this.sandbox.on('sulu.dropdown-input.' + this.dropDownInputInstance + '.change', this.dropDownInputActionHandler.bind(this));
        },

        /**
         * @method bindDOMEvents
         */
        bindDOMEvents: function() {
            this.$el.on('click', '.search-results-section li', this.openSearchEntry.bind(this));
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
         * @method startInfiniteScroll
         */
        startInfiniteScroll: function() {
            var $iscroll = this.$el.find('.iscroll');
            this.sandbox.infiniteScroll($iscroll, this.loadNextPage.bind(this), 50);
        },

        /**
         * Set categories in a method because of the 'this' context
         * @method setCategories
         */
        setCategories: function() {
            this.categories = {
                'all': {
                    'id': 'all',
                    'name': this.sandbox.translate('search-overlay.category.everything.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.everything')
                },

                'media': {
                    'id': 'media',
                    'name': this.sandbox.translate('search-overlay.category.media.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.media')
                },

                'contact': {
                    'id': 'contact',
                    'name': this.sandbox.translate('search-overlay.category.contacts.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.contacts')
                },

                'account': {
                    'id': 'account',
                    'name': this.sandbox.translate('search-overlay.category.accounts.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.accounts')
                },

                'page': {
                    'id': 'page',
                    'name': this.sandbox.translate('search-overlay.category.pages.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.pages')
                },

                'snippet': {
                    'id': 'snippet',
                    'name': this.sandbox.translate('search-overlay.category.snippet.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.snippet')
                }
            };
        },

        /**
         * @method loadCategories
         */
        loadCategories: function() {
            return this.sandbox.util.load(this.options.enabledCategoriesUrl)
                .then(function(data) {
                    var enabledCategory;
                    this.enabledCategories.push(this.categories['all']);

                    data.forEach(function(category) {
                        enabledCategory = this.categories[category];
                        this.enabledCategories.push(enabledCategory);
                    }.bind(this));
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
                    data: this.enabledCategories
                }
            }]);
        },

        /**
         * Fetch the data from the server
         * @method load
         */
        load: function() {
            var url = this.options.searchUrl + '/query?q=' + this.state.query + '&page=' + this.state.page,
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
                    .then(this.updateResults.bind(this));
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
            var data = response.result || [],
                preparedData = {},
                category,
                deepUrl;

            this.state.page = response.page;
            this.state.pageCount = response.pageCount;
            this.state.loading = false;
            this.state.hasNextPage = response.page < response.page_count;
            this.totals = response.totals;

            data.forEach(function(entry) {
                category = entry.document.category;
                deepUrl = this.getEntryDeepUrl(category, entry.document);
                entry.document.deepUrl = deepUrl;

                if (!preparedData[category]) {
                    preparedData[category] = {
                        category: category,
                        results: [entry.document]
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

                this.startLoader();
                this.updateResults();
                this.load().then(function(data) {
                    this.categoriesStore = data;
                    this.updateResults(data);
                }.bind(this));
            }
        },

        /**
         * @method dropDownInputClearHandler
         */
        dropDownInputClearHandler: function() {
            this.updateResults();
        },

        /**
         * @method getEntryDeepUrl
         * @param {String} category
         * @param {Object} data
         */
        getEntryDeepUrl: function(category, data) {
            var handler = this.urlTemplateMapping[category],
                deepUrl = null;

            if (handler) {
                deepUrl = handler.call(this, data);
            }

            return deepUrl;
        },

        /**
         * @type {Object}
         */
        urlTemplateMapping: {
            page: function(data) {
                if (data.url === '/') {
                    // startpage
                    return this.sandbox.urlManager.getUrl('startpage', {
                        id: data.id,
                        webspace: 'sulu_io'
                    });
                } else {
                    return this.sandbox.urlManager.getUrl('contentDetail', {
                        id: data.id,
                        webspace: 'sulu_io'
                    });
                }
            },

            contact: function(data) {
                return this.sandbox.urlManager.getUrl('contactDetail', data);
            },

            account: function(data) {
                return this.sandbox.urlManager.getUrl('accountDetail', data);
            },

            media: function(data) {
                return this.sandbox.urlManager.getUrl('mediaDetail', {
                    id: data.id,
                    collectionId: data.properties.collection_id
                });
            },

            snippet: function(data) {
                return this.sandbox.urlManager.getUrl('snippetDetail', data);
            }
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
            var sections = [];

            if (data) {
                Object.keys(data).forEach(function(key) {
                    sections.push(data[key]);
                }.bind(this));
            }

            return this.searchResultsTpl({
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
            var tpl = this.getTemplate(data);

            this.stopLoader();
            this.$el.find('.search-results').html(tpl);
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
