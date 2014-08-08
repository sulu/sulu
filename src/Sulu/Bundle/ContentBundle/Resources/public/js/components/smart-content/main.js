/**
 * This file is part of Husky frontend development framework.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @module husky/components/smart-content
 */

/**
 * @class SmartContent
 * @constructor
 *
 * @params {Object} [options] Configuration object
 * @params {Integer} [options.visibleItems] maximum of items visible at the start and in the view-less state
 * @params {String} [options.dataSource] default value for the data-source
 * @params {Boolean} [options.includeSubFolders] if true sub folders are included right from the beginning
 * @params {Array} [options.categories] array of categories with id and name property
 * @params {Integer} [options.preSelectedCategory] array with id of the preselected category
 * @params {Array} [options.tags] array of tags which are inserted at the beginning
 * @params {String} [options.tagsAutoCompleteUrl] url to which the tags input is sent and can be autocompleted
 * @params {String} [options.tagsGetParameter] parameter name for auto-completing tags
 * @params {Array} [options.sortBy] array of sort-possibilities with id and name property
 * @params {Integer} [options.preSelectedSortBy] array with id of the preselected sort-possibility
 * @params {String} [options.preSelectedSortMethod] Sort-method to begin with (asc or desc)
 * @params {Array} [options.presentAs] array of presentation-possibilities with id and name property
 * @params {Integer} [options.preSelectedPresentAs] array with id presentation-possibility to begin with
 * @params {Integer} [options.limitResult] maximum number of items returned on the request
 * @params {String} [options.instanceName] name of the component instance
 * @params {String} [options.url] url for requesting the items
 * @params {String} [options.dataSourceParameter] parameter for the source id
 * @params {String} [options.includeSubFoldersParameter] parameter for the include-sub-folders-value
 * @params {String} [options.categoryParameter] parameter for the category id
 * @params {String} [options.tagsParameter] parameter for the tags
 * @params {String} [options.sortByParameter] parameter for the sort-possibility id
 * @params {String} [options.sortMethodParameter] parameter for the sort method
 * @params {String} [options.presentAsParameter] parameter for the presentation-possibility id
 * @params {String} [options.limitResultParameter] parameter for the limit-result-value
 * @params {String} [options.idKey] key for the id in the returning JSON-result
 * @params {String} [options.resultKey] key for the data in the returning JSON-embedded-result
 * @params {String} [options.tagsResultKey] key for the data in the returning JSON-embedded-result for the tags-component
 * @params {String} [options.columnNavigationResultKey] key for the data in the returning JSON-embedded-result for the column-navigation component
 * @params {String} [options.titleKey] key for the title in the returning JSON-result
 * @params {String} [options.pathKey] key for the path in the returning JSON-result
 * @params {Boolean} [options.subFoldersDisabled] if true sub-folders overlay-item will be disabled
 * @params {Boolean} [options.tagsDisabled] if true tags overlay-item will be disabled
 * @params {Boolean} [options.limitResultDisabled] if true limit-result overlay-item will be disabled
 * @params {Boolean} [options.translations.externalConfigs] if true component waits for external config object
 * @params {String} [options.columnNavigationUrl] url for column navigation
 * @params {Boolean} [options.hideDataSource] if true data-source selection hidden
 * @params {Boolean} [options.hideCategories] if true categories hidden
 * @params {Boolean} [options.hideTags] if true tags hidden
 * @params {Boolean} [options.hideSortBy] if true sort by hidden
 * @params {Boolean} [options.hidePresentAs] if true present as hidden
 * @params {Boolean} [options.hideLimit] if true limit hidden
 *
 * @params {Object} [options.translations] object that gets merged with the default translation-keys
 * @params {String} [options.translations.noContentFound] translation key
 * @params {String} [options.translations.noContentSelected] translation key
 * @params {String} [options.translations.visible] translation key
 * @params {String} [options.translations.of] translation key
 * @params {String} [options.translations.configureSmartContent] translation key
 * @params {String} [options.translations.dataSourceLabel] translation key
 * @params {String} [options.translations.dataSourceButton] translation key
 * @params {String} [options.translations.includeSubFolders] translation key
 * @params {String} [options.translations.filterByCategory] translation key
 * @params {String} [options.translations.filterByTags] translation key
 * @params {String} [options.translations.sortBy] translation key
 * @params {String} [options.translations.noSorting] translation key
 * @params {String} [options.translations.ascending] translation key
 * @params {String} [options.translations.descending] translation key
 * @params {String} [options.translations.presentAs] translation key
 * @params {String} [options.translations.limitResultTo] translation key
 * @params {String} [options.translations.noCategory] translation key
 * @params {String} [options.translations.choosePresentAs] translation key
 * @params {String} [options.translations.from] translation key
 * @params {String} [options.translations.subFoldersInclusive] translation key
 * @params {String} [options.translations.viewAll] translation key
 * @params {String} [options.translations.viewLess] translation key
 * @params {String} [options.translations.chooseDataSource] translation key
 * @params {String} [options.translations.chooseDataSourceOk] translation key
 * @params {String} [options.translations.chooseDataSourceCancel] translation key
 */
define([], function() {

    'use strict';

    var defaults = {
            visibleItems: 5,
            dataSource: '',
            subFoldersDisabled: false,
            categories: [],
            preSelectedCategory: null,
            tags: [],
            tagsDisabled: false,
            tagsAutoCompleteUrl: '',
            tagsGetParameter: 'search',
            sortBy: [],
            preSelectedSortBy: null,
            preSelectedSortMethod: 'asc',
            presentAs: [],
            preSelectedPresentAs: null,
            limitResult: null,
            instanceName: 'undefined',
            url: '',
            dataSourceParameter: 'dataSource',
            includeSubFolders: false,
            includeSubFoldersParameter: 'includeSubFolders',
            categoryParameter: 'category',
            tagsParameter: 'tags',
            sortByParameter: 'sortBy',
            sortMethodParameter: 'sortMethod',
            presentAsParameter: 'presentAs',
            limitResultParameter: 'limitResult',
            limitResultDisabled: false,
            idKey: 'id',
            resultKey: 'items',
            tagsResultKey: 'tags',
            columnNavigationResultKey: 'nodes',
            titleKey: 'title',
            pathKey: 'path',
            translations: {},
            elementDataName: 'smart-content',
            externalConfigs: false,
            columnNavigationUrl: '',
            hideDataSource: false,
            hideCategories: false,
            hideTags: false,
            hideSortBy: false,
            hidePresentAs: false,
            hideLimit: false
        },

        sortMethods = {
            asc: 'Ascending',
            desc: 'Descanding'
        },

        constants = {
            containerSelector: '.smart-content-container',
            headerSelector: '.header',
            contentSelector: '.content',
            sourceSelector: '.source',
            footerClass: 'footer',
            viewTogglerClass: 'view-toggler',
            buttonIcon: 'fa-filter',
            includeSubSelector: '.includeSubCheck',
            categoryDDClass: 'category-dropdown',
            tagListClass: 'tag-list',
            sortByDDClass: 'sort-by-dropdown',
            sortMethodDDClass: 'sort-method-dropdown',
            presentAsDDClass: 'present-as-dropdown',
            limitToSelector: '.limit-to',
            dataSourceSelector: '.data-source',
            contentListClass: 'items-list',
            loaderClass: 'loader'
        },

        /** templates for component */
        templates = {
            skeleton: [
                '<div class="white-box smart-content-container form-element">',
                '<div class="header"></div>',
                '<div class="content"></div>',
                '</div>'
            ].join(''),
            source: [
                '<span class="text">',
                '<span class="source">',
                '<span class="desc"><%= desc %></span>',
                '<span class="val"><%= val %></span>',
                '</span>',
                '</span>'
            ].join(''),
            noContent: [
                '<div class="no-content">',
                '<span class="fa-coffee icon"></span>',
                '<div class="text"><%= noContentStr %></div>',
                '</div>'
            ].join(''),
            contentItem: [
                '<li data-id="<%= dataId %>">',
                '<span class="num"><%= num %></span>',
                '<span class="value"><%= value %></span>',
                '</li>'
            ].join(''),
            overlayContent: {
                main: [
                    '<div class="smart-overlay-content">',
                    '</div>'
                ].join(''),

                dataSource: [
                    '<div class="item-half left">',
                    '<span class="desc"><%= dataSourceLabelStr %></span>',
                    '<div class="btn action fit" id="select-data-source-action"><%= dataSourceButtonStr %></div>',
                    '<div><span class="sublabel"><%= dataSourceLabelStr %>:</span> <span class="sublabel data-source"><%= dataSourceValStr %></span></div>',
                    '</div>'
                ].join(''),

                subFolders: [
                    '<div class="item-half">',
                    '<div class="check<%= disabled %>">',
                    '<label>',
                    '<div class="custom-checkbox">',
                    '<input type="checkbox" class="includeSubCheck form-element"<%= includeSubCheckedStr %>/>',
                    '<span class="icon"></span>',
                    '</div>',
                    '<span class="description"><%= includeSubStr %></span>',
                    '</label>',
                    '</div>',
                    '</div>'
                ].join(''),

                categories: [
                    '<div class="item full">',
                    '<span class="desc"><%= filterByCatStr %></span>',
                        '<div class="' + constants.categoryDDClass + '"></div>',
                    '</div>'
                ].join(''),

                tagList: [
                    '<div class="item full tags<%= disabled %>">',
                    '<span class="desc"><%= filterByTagsStr %></span>',
                        '<div class="' + constants.tagListClass + '"></div>',
                    '</div>'
                ].join(''),

                sortBy: [
                    '<div class="item-half left">',
                    '<span class="desc"><%= sortByStr %></span>',
                        '<div class="' + constants.sortByDDClass + '"></div>',
                    '</div>'
                ].join(''),

                sortMethod: [
                    '<div class="item-half">',
                        '<div class="' + constants.sortMethodDDClass + ' sortMethod"></div>',
                    '</div>'
                ].join(''),

                presentAs: [
                    '<div class="item-half left">',
                    '<span class="desc"><%= presentAsStr %></span>',
                        '<div class="' + constants.presentAsDDClass + '"></div>',
                    '</div>'
                ].join(''),

                limitResult: [
                    '<div class="item-half">',
                    '<span class="desc"><%= limitResultToStr %></span>',
                    '<input type="text" value="<%= limitResult %>" class="limit-to form-element"<%= disabled %>/>',
                    '</div>'
                ].join('')
            }
        },

        /**
         * namespace for events
         * @type {string}
         */
        eventNamespace = 'husky.smart-content.',

        /**
         * raised after initialization process
         * @event husky.smart-content.initialize
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialize');
        },

        /**
         * raised when all overlay components returned their value
         * @event husky.smart-content.input-retrieved
         */
        INPUT_RETRIEVED = function() {
            return createEventName.call(this, 'input-retrieved');
        },

        /**
         * raised before data is requested with AJAX
         * @event husky.smart-content.data-request
         */
        DATA_REQUEST = function() {
            return createEventName.call(this, 'data-request');
        },

        /**
         * raised when data has returned from the ajax request
         * @event husky.smart-content.data-retrieved
         */
        DATA_RETRIEVED = function() {
            return createEventName.call(this, 'data-retrieved');
        },

        /**
         * raised when the overlay data has been changed
         * @event husky.smart-content.data-changed
         */
        DATA_CHANGED = function() {
            return createEventName.call(this, 'data-changed');
        },

        /**
         * takes an config-object and merges it with this.options, before the initialization of the component
         * (options.externalConfigs has to be true)
         * @event husky.smart-content.external-configs
         */
        EXTERNAL_CONFIGS = function() {
            return createEventName.call(this, 'external-configs');
        },

        /**
         * takes an config-object and merges it with this.options. Moreover destroys overlay, so
         * it uses the new configs
         * @event husky.smart-content.set-configs
         */
        SET_CONFIGS = function() {
            return createEventName.call(this, 'set-configs');
        },

        /** returns normalized event names */
        createEventName = function(postFix) {
            return eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {

        /**
         * Initialize component
         */
        initialize: function() {
            this.sandbox.logger.log('initialize', this);

            //merge options with defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            //if externalConfigs is true wait for configs to to get in otherwise start the component right ahead
            if (this.options.externalConfigs === true) {
                this.sandbox.on(EXTERNAL_CONFIGS.call(this), function(configs) {

                    //merge the passed components with the current ones
                    this.options = this.sandbox.util.extend(true, {}, this.options, configs);

                    this.createComponent();
                }.bind(this));
            } else {
                this.createComponent();
            }
        },

        /**
         * Creates the component
         */
        createComponent: function() {
            this.setVariables();
            this.render();
            this.renderStartContent();
            this.startOverlay();
            this.bindEvents();
            this.setURI();
            this.loadContent();

            this.setElementData(this.overlayData);

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Sets the objects properties default values
         */
        setVariables: function() {
            this.$container = null;
            this.$header = null;
            this.$content = null;
            this.$footer = null;
            this.$button = null;
            this.itemsVisible = this.options.visibleItems;
            this.items = [];
            this.URI = {
                str: '',
                hasChanged: false
            };

            this.initOverlayData();

            this.translations = {
                noContentFound: 'smart-content.nocontent-found',
                noContentSelected: 'smart-content.nocontent-selected',
                visible: 'smart-content.visible',
                of: 'smart-content.of',
                configureSmartContent: 'smart-content.configure-smart-content',
                dataSourceLabel: 'smart-content.data-source.label',
                dataSourceButton: 'smart-content.data-source.button',
                includeSubFolders: 'smart-content.include-sub-folders',
                filterByCategory: 'smart-content.filter-by-category',
                filterByTags: 'smart-content.filter-by-tags',
                sortBy: 'smart-content.sort-by',
                noSorting: 'smart-content.no-sorting',
                ascending: 'smart-content.ascending',
                descending: 'smart-content.descending',
                presentAs: 'smart-content.present-as',
                limitResultTo: 'smart-content.limit-result-to',
                noCategory: 'smart-content.no-category',
                choosePresentAs: 'smart-content.choose-present-as',
                from: 'smart-content.from',
                subFoldersInclusive: 'smart-content.sub-folders-inclusive',
                viewAll: 'smart-content.view-all',
                viewLess: 'smart-content.view-less',
                chooseDataSource: 'smart-content.choose-data-source',
                chooseDataSourceOk: 'smart-content.choose-data-source.ok',
                chooseDataSourceCancel: 'smart-content.choose-data-source.cancel'
            };

            this.translations = this.sandbox.util.extend(true, {}, this.translations, this.options.translations);
        },

        /**
         * Sets the starting values of properties related to the overlay
         */
        initOverlayData: function() {
            this.$overlayContent = null;
            this.overlayData = {
                dataSource: this.options.dataSource,
                includeSubFolders: this.options.includeSubFolders,
                category: this.options.preSelectedCategory,
                tags: this.options.tags,
                sortBy: this.options.preSelectedSortBy,
                sortMethod: this.options.preSelectedSortMethod,
                presentAs: this.options.preSelectedPresentAs,
                limitResult: this.options.limitResult
            };

            this.overlayDisabled = {
                categories: (this.options.categories.length === 0),
                sortBy: (this.options.sortBy.length === 0),
                presentAs: (this.options.presentAs.length === 0),
                subFolders: (this.options.subFoldersDisabled),
                tags: this.options.tagsDisabled,
                limitResult: this.options.limitResultDisabled
            };
        },

        /**
         * Renders the main container and the header
         */
        render: function() {
            this.renderContainer();
            this.renderHeader();
        },

        /**
         * Inserts the skeleton-template and finds the main-container
         */
        renderContainer: function() {
            this.sandbox.dom.html(this.$el, templates.skeleton);
            this.$container = this.sandbox.dom.find(constants.containerSelector, this.$el);
        },

        /**
         * Finds the header-container and renders the config-button
         */
        renderHeader: function() {
            this.$header = this.sandbox.dom.find(constants.headerSelector, this.$el);
            if (!!this.$header.length) {
                this.renderButton();
            } else {
                this.sandbox.logger.log('Error: no Header-container found!');
            }
        },

        /**
         * Renders the source text and inserts it to the header
         */
        insertSource: function() {
            var desc, $element = this.sandbox.dom.find(constants.dataSourceSelector, this.$overlayContent);
            this.sandbox.dom.text($element, this.sandbox.util.cropMiddle(this.overlayData.path, 30, '...'));

            if (typeof(this.overlayData.dataSource) !== 'undefined') {
                desc = this.sandbox.translate(this.translations.from);
                if (this.overlayData.includeSubFolders !== false) {
                    desc += ' (' + this.sandbox.translate(this.translations.subFoldersInclusive) + '):';
                } else {
                    desc += ': ';
                }
                this.sandbox.dom.append(this.$header, this.sandbox.util.template(templates.source)({
                    desc: desc,
                    val: this.overlayData.title
                }));
            }
        },

        /**
         * Removes the source element from the header
         */
        removeSource: function() {
            this.sandbox.dom.remove(this.sandbox.dom.find(constants.sourceSelector, this.$header));
        },

        /**
         * Renders and appends the toggle-button
         */
        renderButton: function() {
            this.$button = this.sandbox.dom.createElement('<span class="icon right border"/>');
            this.sandbox.dom.prependClass(this.$button, constants.buttonIcon);
            this.sandbox.dom.append(this.$header, this.$button);
        },

        /**
         * initializes the content container
         */
        initContentContainer: function() {
            //if not already exists render content-container
            if (this.$content === null) {
                this.$content = this.sandbox.dom.find(constants.contentSelector, this.$el);
            }
        },

        /**
         * Renders the content decides whether the footer is rendered or not
         */
        renderContent: function() {
            this.initContentContainer();

            if (this.items.length !== 0) {

                var ul, i = -1, length = this.items.length;
                ul = this.sandbox.dom.createElement('<ul class="' + constants.contentListClass + '"/>');

                //loop stops if no more items are left or if number of rendered items matches itemsVisible
                for (; ++i < length && i < this.itemsVisible;) {
                    this.sandbox.dom.append(ul, _.template(templates.contentItem)({
                        dataId: this.items[i][this.options.idKey],
                        value: this.items[i][this.options.titleKey],
                        num: (i + 1)
                    }));
                }

                this.sandbox.dom.html(this.$content, ul);
                this.renderFooter();
            } else {
                //render no-content-template and detach the footer
                this.sandbox.dom.html(this.$content, _.template(templates.noContent)({
                    noContentStr: this.sandbox.translate(this.translations.noContentSelected)
                }));
                this.detachFooter();
            }
        },

        /**
         * Renders the content at the beginning
         * (with no items and before any request)
         */
        renderStartContent: function() {
            this.initContentContainer();

            this.sandbox.dom.html(this.$content, _.template(templates.noContent)({
                noContentStr: this.sandbox.translate(this.translations.noContentSelected)
            }));
        },

        /**
         * Renders the footer and calls a method to bind the events for itself
         */
        renderFooter: function() {
            this.itemsVisible = (this.items.length < this.itemsVisible) ? this.items.length : this.itemsVisible;

            if (this.$footer === null) {
                this.$footer = this.sandbox.dom.createElement('<div/>');
                this.sandbox.dom.addClass(this.$footer, constants.footerClass);
            }

            this.sandbox.dom.html(this.$footer, [
                '<span>',
                    '<strong>' + this.itemsVisible + ' </strong>', this.sandbox.translate(this.translations.of) , ' ',
                    '<strong>' + this.items.length + ' </strong>', this.sandbox.translate(this.translations.visible),
                '</span>'
            ].join(''));

            this.appendViewToggler();
            this.sandbox.dom.append(this.$container, this.$footer);
            this.bindFooterEvents();
        },

        /**
         * Appends the view-toggler to the footer
         */
        appendViewToggler: function() {
            if (this.itemsVisible < this.items.length) {
                this.sandbox.dom.append(
                    this.$footer,
                        '<span class="' + constants.viewTogglerClass + '">(' + this.sandbox.translate(this.translations.viewAll) + ')</span>'
                );
            } else if (this.items.length > this.options.visibleItems) {
                this.sandbox.dom.append(
                    this.$footer,
                        '<span class="' + constants.viewTogglerClass + '">(' + this.sandbox.translate(this.translations.viewLess) + ')</span>'
                );
            }
        },

        /**
         * Removes the footer
         */
        detachFooter: function() {
            if (this.$footer !== null) {
                this.sandbox.dom.remove(this.$footer);
            }
        },

        /**
         * Binds general events
         */
        bindEvents: function() {
            this.sandbox.on(DATA_RETRIEVED.call(this), function() {
                this.renderContent();
                this.removeSource();
                this.insertSource();
            }.bind(this));

            this.sandbox.on(INPUT_RETRIEVED.call(this), function() {
                this.setURI();
                this.loadContent();
            }.bind(this));

            this.sandbox.on('husky.overlay.smart-content.' + this.options.instanceName + '.initialized', function() {
                this.startOverlayComponents();
            }.bind(this));

            this.sandbox.on(SET_CONFIGS.call(this), function(configs) {
                //merge this.options with passed configs
                this.options = this.sandbox.util.extend(false, {}, this.options, configs);

                //reload the overlay
                this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.remove');
                this.initOverlayData();
                this.startOverlay();

                //reload the items
                this.setURI();
                this.loadContent();
            }.bind(this));
        },

        /**
         * Binds footer events
         */
        bindFooterEvents: function() {
            this.sandbox.dom.on(
                this.sandbox.dom.find('.' + constants.viewTogglerClass, this.$footer),
                'click',
                function() {
                    this.toggleView();
                }.bind(this)
            );
        },

        /**
         * Starts the loader component
         */
        startLoader: function() {
            this.detachFooter();

            var loaderContainer = this.sandbox.dom.createElement('<div class="' + constants.loaderClass + '"/>');
            this.sandbox.dom.html(this.$content, loaderContainer);

            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: loaderContainer,
                        size: '100px',
                        color: '#e4e4e4'
                    }
                }
            ]);
        },

        /**
         * Changes the itemsVisible property and calls the render content method
         * (more or less items are visible)
         */
        toggleView: function() {
            if (this.itemsVisible < this.items.length) {
                this.itemsVisible = this.items.length;
            } else {
                this.itemsVisible = this.options.visibleItems;
            }
            this.renderContent();
        },

        /**
         * Starts the overlay component
         */
        startOverlay: function() {
            this.initOverlayContent();

            var $element = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$button,
                        el: $element,
                        container: this.$el,
                        instanceName: 'smart-content.' + this.options.instanceName,
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(this.translations.configureSmartContent),
                                data: this.$overlayContent,
                                okCallback: function() {
                                    this.itemsVisible = this.options.visibleItems;
                                    this.getOverlayData();
                                }.bind(this)
                            },
                            {
                                title: this.sandbox.translate(this.translations.chooseDataSource),
                                data: '<div id="column-navigation-' + this.options.instanceName + '"/>',
                                cssClass: 'column-navigation-slide',
                                okInactive: true,
                                okDefaultText: this.sandbox.translate(this.translations.chooseDataSourceOk),
                                cancelDefaultText: this.sandbox.translate(this.translations.chooseDataSourceCancel),
                                buttons: [
                                    {
                                        type: 'cancel',
                                        inactive: false,
                                        align: 'center'
                                    }
                                ],
                                closeCallback: function() {
                                    this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-left');
                                    return false;
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]);

            this.bindColumnNavigationEvents();
        },

        /**
         * column navigation events
         */
        bindColumnNavigationEvents: function() {
            // init column navigation after initialize of overlay
            this.sandbox.on('husky.overlay.smart-content.' + this.options.instanceName + '.initialized', this.initColumnNavigation.bind(this));

            // adopt height of column navigation once
            this.sandbox.once('husky.overlay.smart-content.' + this.options.instanceName + '.opened', function() {
                // set height of smart-content column navigation slide (missing margins)
                var height = this.sandbox.dom.outerHeight('.smart-content-overlay .slide-0 .overlay-content') + 24;
                this.sandbox.dom.css('.smart-content-overlay .slide-1 .overlay-content', 'height', height + 'px');
            }.bind(this));

            // activate button OK when a page is selected
            this.sandbox.on('husky.column-navigation.smart-content'+ this.options.instanceName +'.edit', function(item) {
                this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-left');

                var $element = this.sandbox.dom.find(constants.dataSourceSelector, this.$overlayContent);
                this.overlayData.dataSource = item.id;
                this.sandbox.dom.text($element, this.sandbox.util.cropMiddle(item.path, 30, '...'));
                this.sandbox.dom.data($element, 'id', item.id);
            }.bind(this));

            // slide to column navigation by click on the action button
            this.sandbox.dom.on(this.$el, 'click', function() {
                this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-right');
            }.bind(this), '#select-data-source-action');
        },

        /**
         * initialize column navigation
         */
        initColumnNavigation: function() {
            var url = this.options.columnNavigationUrl.replace('{/dataSource}', (!!this.overlayData.dataSource ? '/' + this.overlayData.dataSource : ''));

            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: {
                            el: '#column-navigation-' + this.options.instanceName + '',
                            url: url,
                            selected: this.overlayData.dataSource,
                            instanceName: 'smart-content' + this.options.instanceName,
                            responsive: false,
                            editIcon: 'fa-check',
                            showEdit: false,
                            showStatus: false,
                            resultKey: this.options.columnNavigationResultKey
                        }
                    }
                ]
            );
        },

        /**
         * Loads the overlay content based on a template
         */
        initOverlayContent: function() {

            this.$overlayContent = this.sandbox.dom.createElement(_.template(templates.overlayContent.main)());

            if (!this.options.hideDataSource) {
                this.$overlayContent.append(_.template(templates.overlayContent.dataSource)({
                    dataSourceLabelStr: this.sandbox.translate(this.translations.dataSourceLabel),
                    dataSourceButtonStr: this.sandbox.translate(this.translations.dataSourceButton),
                    dataSourceValStr: this.options.dataSource.path
                }));
                this.$overlayContent.append(_.template(templates.overlayContent.subFolders)({
                    includeSubStr: this.sandbox.translate(this.translations.includeSubFolders),
                    includeSubCheckedStr: (this.options.includeSubFolders) ? ' checked' : '',
                    disabled: (this.overlayDisabled.subFolders) ? ' disabled' : ''
                }));
                this.$overlayContent.append('<div class="clear"></div>');
            }
            if (!this.options.hideCategories) {
                this.$overlayContent.append(_.template(templates.overlayContent.categories)({
                    filterByCatStr: this.sandbox.translate(this.translations.filterByCategory)
                }));
            }
            if (!this.options.hideTags) {
                this.$overlayContent.append(_.template(templates.overlayContent.tagList)({
                    filterByTagsStr: this.sandbox.translate(this.translations.filterByTags),
                    disabled: (this.overlayDisabled.tags) ? ' disabled' : ''
                }));
            }
            if (!this.options.hideSortBy) {
                this.$overlayContent.append(_.template(templates.overlayContent.sortBy)({
                    sortByStr: this.sandbox.translate(this.translations.sortBy)
                }));
                this.$overlayContent.append(_.template(templates.overlayContent.sortMethod)({
                    filterByTagsStr: this.sandbox.translate(this.translations.filterByTags)
                }));
            }
            this.$overlayContent.append('<div class="clear"></div>');

            if (!this.options.hidePresentAs) {
                this.$overlayContent.append(_.template(templates.overlayContent.presentAs)({
                    presentAsStr: this.sandbox.translate(this.translations.presentAs)
                }));
            }
            if (!this.options.hideLimit) {
                this.$overlayContent.append(_.template(templates.overlayContent.limitResult)({
                    limitResultToStr: this.sandbox.translate(this.translations.limitResultTo),
                    limitResult: (this.options.limitResult > 0) ? this.options.limitResult : '',
                    disabled: (this.overlayDisabled.limitResult) ? ' disabled' : ''
                }));
            }
            this.$overlayContent.append('<div class="clear"></div>');
        },

        /**
         * Starts all husky-components used by the overlay
         */
        startOverlayComponents: function() {
            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.categoryDDClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.categoryDDClass,
                        defaultLabel: this.sandbox.translate(this.translations.noCategory),
                        value: 'name',
                        data: this.options.categories,
                        preSelectedElements: [this.options.preSelectedCategory],
                        disabled: this.overlayDisabled.categories
                    }
                },
                {
                    name: 'auto-complete-list@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.tagListClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.tagListClass,
                        items: this.options.tags,
                        remoteUrl: this.options.tagsAutoCompleteUrl,
                        autocomplete: (this.options.tagsAutoCompleteUrl !== ''),
                        getParameter: this.options.tagsGetParameter,
                        noNewTags: true,
                        itemsKey: this.options.tagsResultKey
                    }
                },
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.sortByDDClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.sortByDDClass,
//                        deselectLabel: 'No sorting',
                        value: 'name',
                        data: this.options.sortBy,
                        preSelectedElements: [this.options.preSelectedSortBy],
                        disabled: this.overlayDisabled.sortBy
                    }
                },
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.sortMethodDDClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.sortMethodDDClass,
                        defaultLabel: 'Select sort method',
                        value: 'name',
                        data: [
                            {id: sortMethods.asc, name: this.sandbox.translate(this.translations.ascending)},
                            {id: sortMethods.desc, name: this.sandbox.translate(this.translations.descending)}
                        ],
                        preSelectedElements: [sortMethods[this.options.preSelectedSortMethod]],
                        disabled: this.overlayDisabled.sortBy
                    }
                },
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.presentAsDDClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.presentAsDDClass,
                        defaultLabel: this.sandbox.translate(this.translations.choosePresentAs),
                        value: 'name',
                        data: this.options.presentAs,
                        preSelectedElements: [this.options.preSelectedPresentAs],
                        disabled: this.overlayDisabled.presentAs
                    }
                }
            ]);
        },

        /**
         * Generates the URI for the request
         */
        setURI: function() {
            var delimiter = (this.options.url.indexOf('?') === -1) ? '?' : '&',
                newURI = [
                    this.options.url,
                    delimiter, this.options.dataSourceParameter, '=', this.overlayData.dataSource,
                    '&', this.options.includeSubFoldersParameter, '=', this.overlayData.includeSubFolders,
                    '&', this.options.categoryParameter, '=', this.overlayData.category,
                    '&', this.options.tagsParameter, '=', this.overlayData.tags,
                    '&', this.options.sortByParameter, '=', this.overlayData.sortBy,
                    '&', this.options.sortMethodParameter, '=', this.overlayData.sortMethod,
                    '&', this.options.presentAsParameter, '=', this.overlayData.presentAs,
                    '&', this.options.limitResultParameter, '=', this.overlayData.limitResult
                ].join('');
            // min source must be selected
            if (this.overlayData.dataSource.length > 0 && newURI !== this.URI.str) {
                this.sandbox.emit(DATA_CHANGED.call(this), this.sandbox.dom.data(this.$el, 'smart-content'), this.$el);
                this.URI.str = newURI;
                this.URI.hasChanged = true;
            } else {
                this.URI.hasChanged = false;
            }
        },

        /**
         * Requests the data for the content
         */
        loadContent: function() {
            //only request if URI has changed
            if (this.URI.hasChanged === true) {
                this.sandbox.emit(DATA_REQUEST.call(this));
                this.startLoader();
                this.sandbox.util.ajax({
                    url: this.URI.str,

                    success: function(data) {
                        this.overlayData.title = data[this.options.titleKey];
                        this.overlayData.path = data[this.options.pathKey];
                        this.items = data._embedded[this.options.resultKey];
                        this.sandbox.emit(DATA_RETRIEVED.call(this));
                    }.bind(this),

                    error: function(error) {
                        this.sandbox.logger.log(error);
                    }.bind(this)
                });
            }
        },

        /**
         * Gets the values of all user inputs of the overlay
         * event is emited on which the associeted component responses
         */
        getOverlayData: function() {
            var categoryDef, tagsDef, sortByDef, sortMethodDef, presentAsDef, temp;
            categoryDef = tagsDef = sortByDef = sortMethodDef = presentAsDef = this.sandbox.data.deferred();

            //include sub folders
            this.overlayData.includeSubFolders = this.sandbox.dom.prop(
                this.sandbox.dom.find(constants.includeSubSelector, this.$overlayContent),
                'checked');

            //limit result
            this.overlayData.limitResult = this.sandbox.dom.val(
                this.sandbox.dom.find(constants.limitToSelector, this.$overlayContent)
            );

            //data-source
            temp = this.sandbox.dom.data(this.sandbox.dom.find(constants.dataSourceSelector, this.$overlayContent), 'id');
            if (temp !== undefined) {
                this.overlayData.dataSource = temp;
            }

            //category
            this.sandbox.emit('husky.select.' + this.options.instanceName + constants.categoryDDClass + '.get-checked',
                function(category) {
                    this.overlayData.category = category;
                    categoryDef.resolve();
                }.bind(this));

            //tags
            this.sandbox.emit('husky.auto-complete-list.' + this.options.instanceName + constants.tagListClass + '.get-tags',
                function(tags) {
                    this.overlayData.tags = tags;
                    tagsDef.resolve();
                }.bind(this));

            //sort by
            this.sandbox.emit('husky.select.' + this.options.instanceName + constants.sortByDDClass + '.get-checked',
                function(sortBy) {
                    this.overlayData.sortBy = sortBy;
                    sortByDef.resolve();
                }.bind(this));

            //sort method
            this.sandbox.emit('husky.select.' + this.options.instanceName + constants.sortMethodDDClass + '.get-checked',
                function(sortMethod) {
                    this.overlayData.sortMethod = (sortMethod[0] === sortMethods.asc) ? 'asc' : 'desc';
                    sortMethodDef.resolve();
                }.bind(this));

            //present as
            this.sandbox.emit('husky.select.' + this.options.instanceName + constants.presentAsDDClass + '.get-checked',
                function(presentAs) {
                    this.overlayData.presentAs = presentAs;
                    presentAsDef.resolve();
                }.bind(this));

            this.sandbox.dom.when(categoryDef.promise(), tagsDef.promise(), sortByDef.promise(), sortMethodDef.promise(), presentAsDef.promise()).then(function() {
                this.setElementData(this.overlayData);
                this.sandbox.emit(INPUT_RETRIEVED.call(this));
            }.bind(this));
        },

        /**
         * Binds the tags to the element
         * @param newData {object} new data
         */
        setElementData: function(newData) {
            var data = this.sandbox.util.extend(true, {}, newData);
            this.sandbox.dom.data(this.$el, this.options.elementDataName, data);
        }
    };
});
