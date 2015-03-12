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
            this.dropDownEntries = [];

            this.loadCategories();
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
         * @method loadCategories
         * @param {Array} data
         */
        loadCategories: function(data) {
            data = data || [];

            var categoryData;

            data.forEach(function(category) {
                categoryData = this.categoryMapping()[category];
                this.dropDownEntries.push(categoryData);
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
                    preSelectedElement: 0,
                    data: [
                        {
                            'id': 0,
                            'name': this.sandbox.translate('search-overlay.category.everything.title'),
                            'placeholder': this.sandbox.translate('search-overlay.placeholder.everything')
                        },
                        {
                            'id': 1,
                            'name': this.sandbox.translate('search-overlay.category.media.title'),
                            'placeholder': this.sandbox.translate('search-overlay.placeholder.media')
                        },
                        {
                            'id': 2,
                            'name': this.sandbox.translate('search-overlay.category.contacts.title'),
                            'placeholder': this.sandbox.translate('search-overlay.placeholder.contacts')
                        }
                    ]
                }
            }]);
        },

        /**
         * @method categoryMapping
         */
        categoryMapping: function() {
            return {
                'media': {
                    'id': 1,
                    'name': this.sandbox.translate('search-overlay.category.media.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.media')
                },

                'contact': {
                    'id': 2,
                    'name': this.sandbox.translate('search-overlay.category.contacts.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.contacts')
                },

                'account': {
                    'id': 3,
                    'name': this.sandbox.translate('search-overlay.category.accounts.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.accounts')
                },

                'page': {
                    'id': 4,
                    'name': this.sandbox.translate('search-overlay.category.pages.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.pages')
                },

                'snippet': {
                    'id': 5,
                    'name': this.sandbox.translate('search-overlay.category.snippets.title'),
                    'placeholder': this.sandbox.translate('search-overlay.placeholder.snippets')
                }
            };
        },

        /**
         * Fetch the data from the server
         * @method load
         * @param {String} query
         * @param {String} category
         */
        load: function(query, category) {
            var url = this.options.searchUrl + '/query?q=' + query;

            // if category is 0 search for everything
            if (category && category !== '0') {
                url += '&category=' + category;
            }

            return this.sandbox.util.load(url)
                .then(this.parse.bind(this));
        },

        /**
         * @method parse
         * @param {Object} response
         */
        parse: function(response) {
            var data = response || [],
                preparedData = [],
                categoriesStore = {},
                category, 
                deepUrl;

            data.forEach(function(entry) {
                category = entry.document.category;
                deepUrl = this.getEntryDeepUrl(category, entry);

                if (!categoriesStore[category]) {
                    categoriesStore[category] = {
                        category: category,
                        results: [entry.document]
                    };

                    preparedData.push(categoriesStore[category]);
                } else {
                    categoriesStore[category].results.push(entry.document);
                }

                entry.document.deepUrl = deepUrl;
            }.bind(this));

            return preparedData;
        },

        /**
         * @method dropDownInputActionHandler
         */
        dropDownInputActionHandler: function(data) {
            if (!!data.value) {
                this.startLoader();
                this.updateResults();
                this.load(data.value, data.selectedElement)
                    .then(this.updateResults.bind(this));
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
                return this.sandbox.urlManager.getUrl('contentDetail', data);
            }
        },

        /**
         * @type {Object}
         */
        categoryTranslationMapping: {
            'contact': 'search-overlay.category.contacts.title',
            'page': 'search-overlay.category.pages.title'
        },

        /**
         * @type {Object}
         */
        categoryIconMapping: {
            'contact': 'fa-user',
            'page': 'fa-file-o'
        },

        /**
         * @method updateResults
         */
        updateResults: function(data) {
            var tpl = this.searchResultsTpl({
                sections: data || [],
                categoryTranslationMapping: this.categoryTranslationMapping,
                categoryIconMapping: this.categoryIconMapping,
                translate: this.sandbox.translate
            });

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
