/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class SmartContent
 * @constructor
 *
 * @params {Object} [options] Configuration object
 * @params {String} [options.dataSource] default value for the data-source
 * @params {Boolean} [options.includeSubFolders] if true sub folders are included right from the beginning
 * @params {Array} [options.categories] array of categories with id and name property
 * @params {Integer} [options.preSelectedCategory] array with id of the preselected category
 * @params {Array} [options.tags] array of tags which are inserted at the beginning
 * @params {String} [options.tagsAutoCompleteUrl] url to which the tags input is sent and can be autocompleted
 * @params {String} [options.tagsGetParameter] parameter name for auto-completing tags
 * @params {String} [options.preSelectedTagOperator] tag related default operator ('or' or 'and')
 * @params {Array} [options.sortBy] array of sort-possibilities with id and name property
 * @params {Integer} [options.preSelectedSortBy] array with id of the preselected sort-possibility
 * @params {String} [options.preSelectedSortMethod] Sort-method to begin with (asc or desc)
 * @params {Array} [options.presentAs] array of presentation-possibilities with id and name property
 * @params {Integer} [options.preSelectedPresentAs] id of the default presentation-mode
 * @params {String} [options.instanceName] name of the component instance
 * @params {String} [options.url] url for requesting the items
 * @params {String} [options.dataSourceParameter] parameter for the source id
 * @params {String} [options.includeSubFoldersParameter] parameter for the include-sub-folders-value
 * @params {String} [options.categoriesParameter] parameter for the category ids
 * @params {String} [options.categoryOperatorParameter] parameter for the category ids
 * @params {String} [options.tagsParameter] parameter for the tags
 * @params {String} [options.tagOperatorParameter] parameter for the tag operator
 * @params {String} [options.sortByParameter] parameter for the sort-possibility id
 * @params {String} [options.sortMethodParameter] parameter for the sort method
 * @params {String} [options.presentAsParameter] parameter for the presentation-possibility id
 * @params {String} [options.limitResultParameter] parameter for the limit-result-value
 * @params {String} [options.idKey] key for the id in the returning JSON-result
 * @params {String} [options.resultKey] key for the data in the returning JSON-embedded-result
 * @params {String} [options.tagsResultKey] key for the data in the returning JSON-embedded-result for the tags-component
 * @params {String} [options.titleKey] key for the title in the returning JSON-result
 * @params {String} [options.imageKey] key for the image in the returning JSON-result
 * @params {String} [options.pathKey] key for the full-qualified-title in the returning JSON-result
 * @params {Boolean} [options.subFoldersDisabled] if true sub-folders overlay-item will be disabled
 * @params {Boolean} [options.tagsDisabled] if true tags overlay-item will be disabled
 * @params {Boolean} [options.translations.externalConfigs] if true component waits for external config object
 * @params {Boolean} [options.has] activates or deactivates features (default all false)
 * @params {Boolean} [options.datasource] name and options of datasource component
 *
 * @params {Object} [options.translations] object that gets merged with the default translation-keys
 * @params {String} [options.translations.noContentFound] translation key
 * @params {String} [options.translations.noContentSelected] translation key
 * @params {String} [options.translations.visible] translation key
 * @params {String} [options.translations.of] translation key
 * @params {String} [options.translations.configureSmartContent] translation key
 * @params {String} [options.translations.dataSourceLabel] translation key
 * @params {String} [options.translations.categoryButton] translation key
 * @params {String} [options.translations.categoryLabel] translation key
 * @params {String} [options.translations.categories] translation key
 * @params {String} [options.translations.dataSourceButton] translation key
 * @params {String} [options.translations.includeSubFolders] translation key
 * @params {String} [options.translations.filterByTags] translation key
 * @params {String} [options.translations.useAnyTag] translation key
 * @params {String} [options.translations.useAllTags] translation key
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
 * @params {String} [options.translations.chooseDataSourceReset] translation key
 * @params {String} [options.translations.chooseDataSourceCancel] translation key
 * @params {String} [options.translations.chooseCategoriesSource] translation key
 * @params {String} [options.translations.chooseCategoriesOk] translation key
 * @params {String} [options.translations.chooseCategoriesCancel] translation key
 */
define(['services/husky/util'], function(util) {

    'use strict';

    var defaults = {
            dataSource: '',
            subFoldersDisabled: false,
            categories: [],
            tags: [],
            tagsDisabled: false,
            tagsAutoCompleteUrl: '',
            tagsGetParameter: 'search',
            preSelectedTagOperator: 'or',
            preSelectedCategoryOperator: 'or',
            sortBy: [],
            preSelectedSortBy: null,
            preSelectedSortMethod: 'asc',
            presentAs: [],
            preSelectedPresentAs: null,
            instanceName: 'undefined',
            url: '',
            dataSourceParameter: 'dataSource',
            includeSubFolders: false,
            includeSubFoldersParameter: 'includeSubFolders',
            categoriesParameter: 'categories',
            categoryOperatorParameter: 'categoryOperator',
            paramsParameter: 'params',
            tagsParameter: 'tags',
            tagOperatorParameter: 'tagOperator',
            sortByParameter: 'sortBy',
            sortMethodParameter: 'sortMethod',
            presentAsParameter: 'presentAs',
            limitResultParameter: 'limitResult',
            limitResultDisabled: false,
            publishedStateKey: 'publishedState',
            publishedKey: 'published',
            idKey: 'id',
            resultKey: 'items',
            datasourceKey: 'datasource',
            tagsResultKey: 'tags',
            titleKey: 'title',
            imageKey: 'image',
            pathKey: 'path',
            localeKey: 'locale',
            webspaceKey: 'webspaceKey',
            translations: {},
            elementDataName: 'smart-content',
            externalConfigs: false,
            has: {},
            title: 'Smart-Content',
            datasource: null,
            categoryRoot: null,
            displayOptions: {},
            navigateEvent: 'sulu.router.navigate',
            deepLink: ''
        },

        displayOptionsDefaults = {
            tags: true,
            categories: true,
            sorting: true,
            limit: true,
            presentAs: true
        },

        sortMethods = {
            asc: 'Ascending',
            desc: 'Descanding'
        },

        operators = {
            or: 'or',
            and: 'and'
        },

        constants = {
            containerSelector: '.smart-content-container',
            headerSelector: '.header',
            contentSelector: '.content',
            sourceSelector: '.source',
            buttonIcon: 'fa-filter',
            includeSubSelector: '.includeSubCheck',
            tagListClass: 'tag-list',
            tagOperatorClass: 'tag-list-operator-dropdown',
            sortByDropdownClass: 'sort-by-dropdown',
            sortMethodDropdownClass: 'sort-method-dropdown',
            presentAsDropdownClass: 'present-as-dropdown',
            limitToSelector: '.limit-to',
            dataSourceSelector: '.data-source',
            contentListClass: 'items-list',
            loaderClass: 'loader',
            noContentClass: 'no-content',
            isLoadingClass: 'is-loading'
        },

        /** templates for component */
        templates = {
            skeleton: [
                '<div class="white-box smart-content-container form-element">',
                '<div class="header">',
                '    <span class="selected-counter">',
                '        <span class="num">0</span>',
                '        <span><%= selectedCounterStr %></span>',
                '    </span>',
                '    <span class="no-content-message"><%= noContentStr %></span>',
                '</div>',
                '<div class="content"></div>',
                '</div>'
            ].join(''),
            source: [
                '<span class="text">',
                '    <span class="source">',
                '        <span class="desc"><%= desc %></span>',
                '        <span class="val"><%= val %></span>',
                '    </span>',
                '</span>'
            ].join(''),
            contentItem: [
                '<li data-id="<%= dataId %>">',
                '    <span class="num"><%= num %></span>',
                '    <span class="icons"><%= icons %></span>',
                '<% if (!!image) { %>',
                '    <span class="image"><img src="<%= image %>"/></span>',
                '<% } %>',
                '    <span class="value"><%= value %></span>',
                '</li>'
            ].join(''),
            contentItemLink: [
                '<li data-id="<%= dataId %>">',
                '    <a href="#" data-id="<%= dataId %>" data-webspace="<%= webspace %>" data-locale="<%= locale %>" class="link">',
                '        <span class="num"><%= num %></span>',
                '        <span class="icons"><%= icons %></span>',
                '<% if (!!image) { %>',
                '        <span class="image"><img src="<%= image %>"/></span>',
                '<% } %>',
                '        <span class="value"><%= value %></span>',
                '    </a>',
                '</li>'
            ].join(''),
            categoryItem: [
                '<span><%=item.name%></span>'
            ].join(''),
            overlayContent: {
                main: [
                    '<div class="smart-overlay-content">',
                    '</div>'
                ].join(''),

                dataSource: [
                    '<div class="item-half left">',
                    '    <span class="desc"><%= dataSourceLabelStr %></span>',
                    '    <div class="btn action fit" id="select-data-source-action"><%= dataSourceButtonStr %></div>',
                    '    <div><span class="sublabel"><%= dataSourceLabelStr %>:</span> <span class="sublabel data-source"><%= dataSourceValStr %></span></div>',
                    '</div>'
                ].join(''),

                categories: [
                    '<div class="item">',
                    '    <div class="categories-loader"></div>',
                    '    <div class="categories" style="display: none;">',
                    '        <span class="desc"><%= categoriesLabelStr %></span>',
                    '        <div class="btn action fit select-categories-btn" id="select-categories-action"><%= categoriesButtonStr %></div>',
                    '        <div class="sublabel"><span><%= categoriesStr %> (<span class="amount-selected-categories"></span>):</span> <span class="selected-categories"></span></div>',
                    '    </div>',
                    '</div>'
                ].join(''),

                subFolders: [
                    '<div class="item-half">',
                    '    <div class="check<%= disabled %>">',
                    '        <label>',
                    '            <div class="custom-checkbox">',
                    '                <input type="checkbox" class="includeSubCheck form-element"<%= includeSubCheckedStr %>/>',
                    '                <span class="icon"></span>',
                    '            </div>',
                    '            <span class="description"><%= includeSubStr %></span>',
                    '        </label>',
                    '    </div>',
                    '</div>'
                ].join(''),

                tagList: [
                    '<div class="item-half left tags<%= disabled %>">',
                    '    <span class="desc"><%= filterByTagsStr %></span>',
                    '    <div class="' + constants.tagListClass + '"></div>',
                    '</div>'
                ].join(''),

                tagOperator: [
                    '<div class="item-half<%= disabled %>">',
                    '    <span class="desc">&nbsp;</span>',
                    '    <div class="' + constants.tagOperatorClass + '"></div>',
                    '</div>'
                ].join(''),

                sortBy: [
                    '<div class="item-half left">',
                    '    <span class="desc"><%= sortByStr %></span>',
                    '    <div class="' + constants.sortByDropdownClass + '"></div>',
                    '</div>'
                ].join(''),

                sortMethod: [
                    '<div class="item-half">',
                    '    <span class="desc">&nbsp;</span>',
                    '    <div class="' + constants.sortMethodDropdownClass + '"></div>',
                    '</div>'
                ].join(''),

                presentAs: [
                    '<div class="item-half left">',
                    '    <span class="desc"><%= presentAsStr %></span>',
                    '    <div class="' + constants.presentAsDropdownClass + '"></div>',
                    '</div>'
                ].join(''),

                limitResult: [
                    '<div class="item-half">',
                    '    <span class="desc"><%= limitResultToStr %></span>',
                    '    <input type="text" value="<%= limitResult %>" class="limit-to form-element"<%= disabled %>/>',
                    '</div>'
                ].join('')
            },

            icons: {
                draft: '<span class="draft-icon" title="<%= title %>"/>',
                published: [
                    '<span class="published-icon" title="<%= title %>"/>'
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

            //merge displayOptions with defaults
            this.options.displayOptions = this.sandbox.util.extend(
                true, {}, displayOptionsDefaults, this.options.displayOptions
            );

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
            this.startLoader();
            this.startOverlay();
            this.bindEvents();
            this.bindDomEvents();
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
            this.$loader = null;
            this.$button = null;
            this.items = [];
            this.URI = {
                data: {},
                str: this.options.url,
                hasChanged: false
            };

            this.initOverlayData();

            this.translations = {
                elementsSelected: 'public.elements-selected',
                noContentFound: 'smart-content.nocontent-found',
                noContentSelected: 'smart-content.nocontent-selected',
                visible: 'smart-content.visible',
                of: 'smart-content.of',
                configureSmartContent: 'smart-content.configure-smart-content',
                dataSourceLabel: 'smart-content.data-source.label',
                dataSourceButton: 'smart-content.data-source.button',
                categoryLabel: 'smart-content.categories.label',
                categoryButton: 'smart-content.categories.button',
                categories: 'smart-content.categories',
                includeSubFolders: 'smart-content.include-sub-folders',
                filterByTags: 'smart-content.filter-by-tags',
                useAnyTag: 'smart-content.use-any-tag',
                useAllTags: 'smart-content.use-all-tags',
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
                chooseDataSourceReset: 'smart-content.choose-data-source.reset',
                chooseDataSourceCancel: 'smart-content.choose-data-source.cancel',
                chooseCategories: 'smart-content.choose-categories',
                chooseCategoriesOk: 'smart-content.choose-categories.ok',
                chooseCategoriesCancel: 'smart-content.choose-categories.cancel',
                clearButton: 'smart-content.clear',
                applyButton: 'smart-content.apply',
                unpublished: 'public.unpublished',
                publishedWithDraft: 'public.published-with-draft'
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
                categories: this.options.categories || [],
                categoryOperator: this.options.preSelectedCategoryOperator || [],
                tags: this.options.tags || [],
                tagOperator: this.options.preSelectedTagOperator,
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
            this.sandbox.dom.html(this.$el, this.sandbox.util.template(templates.skeleton, {
                noContentStr: this.sandbox.translate(this.translations.noContentSelected),
                selectedCounterStr: this.sandbox.translate(this.translations.elementsSelected)
            }));
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
            var desc,
                $element = this.sandbox.dom.find(constants.dataSourceSelector, this.$overlayContent),
                fullQualifiedTitle = this.sandbox.translate(this.overlayData.fullQualifiedTitle);

            this.sandbox.dom.text($element, this.sandbox.util.cropMiddle(fullQualifiedTitle, 30, '...'));

            if (!!this.options.has.datasource &&
                typeof(this.overlayData.dataSource) !== 'undefined' &&
                this.overlayData.dataSource !== '' &&
                this.overlayData.title !== '' &&
                this.overlayData.title !== null
            ) {
                desc = this.sandbox.translate(this.translations.from);
                if (this.overlayData.includeSubFolders !== false) {
                    desc += ' (' + this.sandbox.translate(this.translations.subFoldersInclusive) + '):';
                } else {
                    desc += ': ';
                }
                this.sandbox.dom.append(this.$header, this.sandbox.util.template(templates.source)({
                    desc: desc,
                    val: this.sandbox.translate(this.overlayData.title)
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
         * Renders and appends the overlay open button
         */
        renderButton: function() {
            this.$button = this.sandbox.dom.createElement('<span class="icon left action"/>');
            this.sandbox.dom.prependClass(this.$button, constants.buttonIcon);
            this.sandbox.dom.prepend(this.$header, this.$button);
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
         * Renders the content
         */
        renderContent: function() {
            this.initContentContainer();

            if (this.items.length !== 0) {
                this.$container.removeClass(constants.noContentClass);

                var ul = this.sandbox.dom.createElement('<ul class="' + constants.contentListClass + '"/>');

                this.sandbox.util.foreach(this.items, function(item, index) {
                    var template = templates.contentItem;
                    if (this.options.deepLink !== '') {
                        template = templates.contentItemLink;
                    }

                    this.sandbox.dom.append(ul, _.template(template, {
                        dataId: item[this.options.idKey],
                        value: item[this.options.titleKey],
                        image: item[this.options.imageKey] || null,
                        webspace: this.options.webspace,
                        locale: this.options.locale,
                        num: (index + 1),
                        icons: this.getItemIcons(item)
                    }));
                }.bind(this));

                this.sandbox.dom.append(this.$content, ul);
            } else {
                this.$content.empty();
                this.$header.find('.no-content-message').html(this.sandbox.translate(this.translations.noContentFound));
                this.$container.addClass(constants.noContentClass);
            }
        },

        /**
         * Returns the icons of an item for given item data
         *
         * @param {Object} itemData The data of the item
         * @returns {string} the html string of icons
         */
        getItemIcons: function(itemData) {
            if (itemData[this.options.publishedStateKey] === undefined) {
                return '';
            }

            var icons = '',
                tooltip = this.sandbox.translate(this.translations.unpublished);
            if (!itemData[this.options.publishedStateKey] && !!itemData[this.options.publishedKey]) {
                tooltip = this.sandbox.translate(this.translations.publishedWithDraft);
                icons += _.template(templates.icons.published, {
                    title: tooltip
                });
            }
            if (!itemData[this.options.publishedStateKey]) {
                icons += _.template(templates.icons.draft, {
                    title: tooltip
                });
            }

            return icons;
        },

        /**
         * Renders the content at the beginning
         * (with no items and before any request)
         */
        renderStartContent: function() {
            this.initContentContainer();
            this.$container.addClass(constants.noContentClass);
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
         * Binds dom events
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.$el, 'click', function(e) {
                var id = this.sandbox.dom.data(e.currentTarget, 'id'),
                    webspace = this.sandbox.dom.data(e.currentTarget, 'webspace'),
                    locale = this.sandbox.dom.data(e.currentTarget, 'locale'),
                    route = this.options.deepLink;

                route = route.replace('{webspace}', webspace)
                    .replace('{locale}', locale)
                    .replace('{id}', id);

                this.sandbox.emit(this.options.navigateEvent, route);

                return false;
            }.bind(this), 'a.link');
        },

        /**
         * Starts the loader component
         */
        startLoader: function() {
            this.$loader = this.sandbox.dom.createElement('<div class="' + constants.loaderClass + '"/>');
            this.sandbox.dom.append(this.$header, this.$loader);

            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: this.$loader,
                        size: '20px',
                        color: '#999999'
                    }
                }
            ]);
        },

        /**
         * Starts the overlay component
         */
        startOverlay: function() {
            var hasDatasource = !!this.options.has.datasource,
                hasCategories = !!this.options.has.categories && !!this.options.displayOptions.categories;

            this.initOverlayContent();

            // init slide indexes for slide to
            this.mainSlide = 0;
            this.datasourceSlide = (!!hasDatasource) ? 1 : null;
            this.categoriesSlide = (!!hasDatasource) ? 2 : (!!hasCategories ? 1 : null);

            var $element = this.sandbox.dom.createElement('<div/>'),
                slides = [
                    {
                        title: this.sandbox.translate(
                            this.translations.configureSmartContent).replace('{title}',
                            this.options.title
                        ),
                        data: this.$overlayContent,
                        buttons: [
                            {
                                type: 'cancel',
                                text: 'public.cancel',
                                classes: 'gray black-text',
                                inactive: false,
                                align: 'left'
                            },
                            {
                                text: this.sandbox.translate(this.translations.clearButton),
                                inactive: false,
                                align: 'center',
                                classes: 'just-text',
                                callback: function() {
                                    this.clear();
                                }.bind(this)
                            },
                            {
                                type: 'ok',
                                text: this.sandbox.translate(this.translations.applyButton),
                                inactive: false,
                                align: 'right'
                            }
                        ],
                        okCallback: function() {
                            this.getOverlayData();
                        }.bind(this)
                    }
                ];

            if (!!hasDatasource) {
                slides.push({
                    title: this.sandbox.translate(this.translations.chooseDataSource),
                    data: '<div id="data-source-' + this.options.instanceName + '" class="data-source-content"/>',
                    cssClass: 'data-source-slide',
                    okInactive: true,
                    buttons: [
                        {
                            type: 'cancel',
                            inactive: false,
                            text: this.translations.chooseDataSourceCancel,
                            align: 'left'
                        },
                        {
                            inactive: false,
                            classes: 'just-text',
                            text: this.translations.chooseDataSourceReset,
                            align: 'center',
                            callback: function() {
                                var $element = this.sandbox.dom.find(constants.dataSourceSelector, this.$overlayContent);
                                this.overlayData.dataSource = null;
                                $element.text('');
                                $element.data('id', null);

                                this.sandbox.emit('smart-content.datasource.' + this.options.instanceName + '.set-selected', this.overlayData.dataSource);
                                this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-to', this.mainSlide);

                                return false;
                            }.bind(this)
                        }
                    ],
                    cancelCallback: function() {
                        this.sandbox.emit('smart-content.datasource.' + this.options.instanceName + '.set-selected', this.overlayData.dataSource);
                        this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-to', this.mainSlide);

                        return false;
                    }.bind(this)
                });
            }

            if (!!hasCategories) {
                slides.push({
                    title: this.sandbox.translate(this.translations.chooseCategories),
                    data: '<div id="categories-' + this.options.instanceName + '" class="categories-content"/>',
                    cssClass: 'categories-slide',
                    buttons: [
                        {
                            type: 'cancel',
                            inactive: false,
                            text: this.translations.chooseCategoriesCancel,
                            align: 'left'
                        },
                        {
                            type: 'ok',
                            inactive: false,
                            text: this.translations.chooseCategoriesOk,
                            align: 'right'
                        }
                    ],
                    cancelCallback: function() {
                        this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-to', this.mainSlide);
                        return false;
                    }.bind(this),
                    okCallback: function() {
                        this.sandbox.emit(
                            'smart-content.categories.' + this.options.instanceName + '.get-data',
                            this.selectCategories.bind(this)
                        );

                        this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-to', this.mainSlide);
                        return false;
                    }.bind(this)
                });
            }

            this.sandbox.dom.append(this.$el, $element);
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$button,
                        el: $element,
                        removeOnClose: false,
                        container: this.$el,
                        instanceName: 'smart-content.' + this.options.instanceName,
                        skin: 'wide',
                        slides: slides
                    }
                }
            ]);

            this.bindDatasourceEvents();
        },

        /**
         * datasource events
         */
        bindDatasourceEvents: function() {
            if (!!this.options.has.datasource) {
                // init datasource navigation after initialize of overlay
                this.sandbox.on(
                    'husky.overlay.smart-content.' + this.options.instanceName + '.initialized',
                    this.initDatasource.bind(this)
                );
            }
            if (!!this.options.has.categories && !!this.options.displayOptions.categories) {
                // init categories after initialize of overlay
                this.sandbox.on(
                    'husky.overlay.smart-content.' + this.options.instanceName + '.initialized',
                    this.initCategories.bind(this)
                );
            }

            // adopt height of datasource once
            this.sandbox.once('husky.overlay.smart-content.' + this.options.instanceName + '.opened', function() {
                // set height of smart-content datasource slide (missing margins)
                var height = this.sandbox.dom.outerHeight('.smart-content-overlay .slide-0 .overlay-content') + 24;
                this.sandbox.dom.css('.smart-content-overlay .slide-1 .overlay-content', 'height', height + 'px');
            }.bind(this));

            // slide to datasource by click on the action button
            this.sandbox.dom.on(this.$el, 'click', function() {
                this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-to', this.datasourceSlide);
            }.bind(this), '#select-data-source-action');

            // slide to categories by click on the action button
            this.sandbox.dom.on(this.$el, 'click', function() {
                this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-to', this.categoriesSlide);
            }.bind(this), '#select-categories-action');
        },

        /**
         * initialize datasource navigation
         */
        initDatasource: function() {
            var componentDefaults = {
                    el: '#data-source-' + this.options.instanceName,
                    selected: this.overlayData.dataSource,
                    webspace: this.options.webspace,
                    locale: this.options.locale,
                    instanceName: this.options.instanceName,
                    selectCallback: function(id, fullQualifiedTitle) {
                        fullQualifiedTitle = this.sandbox.translate(fullQualifiedTitle);

                        var $element = this.sandbox.dom.find(constants.dataSourceSelector, this.$overlayContent);
                        this.overlayData.dataSource = id;
                        this.sandbox.dom.text($element, this.sandbox.util.cropMiddle(fullQualifiedTitle, 30, '...'));
                        this.sandbox.dom.data($element, 'id', id);

                        this.sandbox.emit('smart-content.datasource.' + this.options.instanceName + '.set-selected', this.overlayData.dataSource);
                        this.sandbox.emit('husky.overlay.smart-content.' + this.options.instanceName + '.slide-to', this.mainSlide);
                    }.bind(this)
                },
                componentOptions = this.sandbox.util.extend(true, {}, componentDefaults, this.options.datasource.options);

            this.sandbox.start(
                [
                    {
                        name: this.options.datasource.name,
                        options: componentOptions
                    }
                ]
            );
        },

        /**
         * initialize categories slide
         */
        initCategories: function() {
            // wait for items and render them
            this.sandbox.once(
                'smart-content.categories.' + this.options.instanceName + '.initialized',
                this.handleCategoriesInitialized.bind(this)
            );

            this.sandbox.start(
                [
                    {
                        name: 'loader@husky',
                        options: {
                            el: this.sandbox.dom.find('.categories-loader', this.$overlayContent)
                        }
                    },
                    {
                        name: 'smart-content/categories@sulucontent',
                        options: {
                            el: '#categories-' + this.options.instanceName,
                            instanceName: this.options.instanceName,
                            preselectedOperator: this.overlayData.categoryOperator,
                            preselectedCategories: this.overlayData.categories,
                            root: this.options.categoryRoot
                        }
                    }
                ]
            );
        },

        /**
         * handles initialized event of categories subcomponent.
         * @param {Object} data
         */
        handleCategoriesInitialized: function(data) {
            this.selectCategories(data);

            this.sandbox.stop(this.sandbox.dom.find('.categories-loader', this.$overlayContent));
            this.sandbox.dom.find('.categories', this.$overlayContent).show();
        },

        /**
         * saves selected categories.
         * @param {Object} data
         */
        selectCategories: function(data) {
            this.overlayData.categories = data.ids;
            this.overlayData.categoryOperator = data.operator;
            this.renderCategories(data.items);
        },

        /**
         * render selected categories.
         */
        renderCategories: function(items) {
            var html = [],
                length = items.length > 3 ? 3 : items.length,
                i;

            for (i = 0; i < length; i++) {
                html.push(util.template(templates.categoryItem, {item: items[i]}));

                if (i < length - 1) {
                    html.push(', ');
                }
            }

            if (length < items.length) {
                html.push(' ...');
            }

            this.sandbox.dom.html(this.sandbox.dom.find('.selected-categories', this.$overlayContent), html.join(''));
            this.sandbox.dom.html(this.sandbox.dom.find('.amount-selected-categories', this.$overlayContent), items.length);
        },

        /**
         * Loads the overlay content based on a template
         */
        initOverlayContent: function() {
            this.$overlayContent = this.sandbox.dom.createElement(_.template(templates.overlayContent.main)());
            this.appendOverlayContent(this.$overlayContent, this.options);
        },

        appendOverlayContent: function($container, data) {
            if (!!this.options.has.datasource) {
                $container.append(_.template(templates.overlayContent.dataSource)({
                    dataSourceLabelStr: this.sandbox.translate(this.translations.dataSourceLabel),
                    dataSourceButtonStr: this.sandbox.translate(this.translations.dataSourceButton),
                    dataSourceValStr: ''
                }));
                $container.append(_.template(templates.overlayContent.subFolders)({
                    includeSubStr: this.sandbox.translate(this.translations.includeSubFolders),
                    includeSubCheckedStr: (data.includeSubFolders) ? ' checked' : '',
                    disabled: (this.overlayDisabled.subFolders) ? ' disabled' : ''
                }));
                $container.append('<div class="clear"></div>');
            }
            if (!!this.options.has.categories && !!this.options.displayOptions.categories) {
                $container.append(_.template(templates.overlayContent.categories)({
                    categoriesLabelStr: this.sandbox.translate(this.translations.categoryLabel),
                    categoriesStr: this.sandbox.translate(this.translations.categories),
                    categoriesButtonStr: this.sandbox.translate(this.translations.categoryButton)
                }));
            }
            if (!!this.options.has.tags && !!this.options.displayOptions.tags) {
                $container.append(_.template(templates.overlayContent.tagList)({
                    filterByTagsStr: this.sandbox.translate(this.translations.filterByTags),
                    disabled: (this.overlayDisabled.tags) ? ' disabled' : ''
                }));
                $container.append(_.template(templates.overlayContent.tagOperator)({
                    disabled: (this.overlayDisabled.tags) ? ' disabled' : ''
                }));
                $container.append('<div class="clear"></div>');
            }
            if (!!this.options.has.sorting && !!this.options.displayOptions.sorting) {
                $container.append(_.template(templates.overlayContent.sortBy)({
                    sortByStr: this.sandbox.translate(this.translations.sortBy)
                }));
                $container.append(_.template(templates.overlayContent.sortMethod)());

                $container.append('<div class="clear"></div>');
            }
            if (!!this.options.has.presentAs && !!this.options.displayOptions.presentAs && !!this.options.presentAs && this.options.presentAs.length > 0
            ) {
                $container.append(_.template(templates.overlayContent.presentAs)({
                    presentAsStr: this.sandbox.translate(this.translations.presentAs)
                }));
            }
            if (!!this.options.has.limit && !!this.options.displayOptions.limit) {
                $container.append(_.template(templates.overlayContent.limitResult)({
                    limitResultToStr: this.sandbox.translate(this.translations.limitResultTo),
                    limitResult: (data.limitResult > 0) ? data.limitResult : '',
                    disabled: (this.overlayDisabled.limitResult) ? ' disabled' : ''
                }));
            }
            $container.append('<div class="clear"></div>');
        },

        /**
         * Starts all husky-components used by the overlay
         */
        startOverlayComponents: function() {
            this.sandbox.start([
                {
                    name: 'auto-complete-list@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.tagListClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.tagListClass,
                        items: this.overlayData.tags,
                        remoteUrl: this.options.tagsAutoCompleteUrl,
                        autocomplete: (this.options.tagsAutoCompleteUrl !== ''),
                        getParameter: this.options.tagsGetParameter,
                        noNewTags: true,
                        itemsKey: this.options.tagsResultKey,
                        disabled: this.overlayDisabled.tags
                    }
                },
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.tagOperatorClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.tagOperatorClass,
                        value: 'name',
                        data: [
                            {id: operators.or, name: this.sandbox.translate(this.translations.useAnyTag)},
                            {id: operators.and, name: this.sandbox.translate(this.translations.useAllTags)}
                        ],
                        preSelectedElements: !!this.overlayData.tagOperator ?
                            [operators[this.overlayData.tagOperator]] : [],
                        disabled: this.overlayDisabled.tags
                    }
                },
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.sortByDropdownClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.sortByDropdownClass,
                        value: 'name',
                        data: this.options.sortBy,
                        preSelectedElements: !!this.overlayData.sortBy ? [this.overlayData.sortBy] : [],
                        disabled: this.overlayDisabled.sortBy,
                        defaultLabel: this.sandbox.translate('smart-content.no-sorting'),
                        deselectField: this.sandbox.translate('smart-content.no-sorting')
                    }
                },
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.sortMethodDropdownClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.sortMethodDropdownClass,
                        value: 'name',
                        data: [
                            {id: sortMethods.asc, name: this.sandbox.translate(this.translations.ascending)},
                            {id: sortMethods.desc, name: this.sandbox.translate(this.translations.descending)}
                        ],
                        preSelectedElements: !!this.overlayData.sortMethod ?
                            [sortMethods[this.overlayData.sortMethod]] : null,
                        disabled: this.overlayDisabled.sortBy
                    }
                },
                {
                    name: 'select@husky',
                    options: {
                        el: this.sandbox.dom.find('.' + constants.presentAsDropdownClass, this.$overlayContent),
                        instanceName: this.options.instanceName + constants.presentAsDropdownClass,
                        defaultLabel: this.sandbox.translate(this.translations.choosePresentAs),
                        value: 'name',
                        data: this.options.presentAs,
                        preSelectedElements: !!this.overlayData.presentAs ? [this.overlayData.presentAs] : [],
                        disabled: this.overlayDisabled.presentAs
                    }
                }
            ]);
        },

        /**
         * Generates the URI for the request
         */
        setURI: function() {
            var data = {};

            data[this.options.dataSourceParameter] = this.overlayData.dataSource;
            data[this.options.includeSubFoldersParameter] = this.overlayData.includeSubFolders;
            data[this.options.tagsParameter] = this.overlayData.tags;
            data[this.options.tagOperatorParameter] = this.overlayData.tagOperator;
            data[this.options.sortByParameter] = this.overlayData.sortBy;
            data[this.options.sortMethodParameter] = this.overlayData.sortMethod;
            data[this.options.presentAsParameter] = this.overlayData.presentAs;
            data[this.options.limitResultParameter] = this.overlayData.limitResult !== '' ?
                this.overlayData.limitResult : null;
            data[this.options.categoriesParameter] = this.overlayData.categories || [];
            data[this.options.categoryOperatorParameter] = this.overlayData.categoryOperator ||
                this.options.preSelectedCategoryOperator;
            data[this.options.paramsParameter] = JSON.stringify(this.options.property.params);

            // min source must be selected
            if (JSON.stringify(data) !== JSON.stringify(this.URI.data)) {
                var domData = this.sandbox.dom.data(this.$el, this.options.elementDataName);
                this.sandbox.emit(DATA_CHANGED.call(this), domData, this.$el);
                this.URI.data = this.sandbox.util.extend(true, {}, data);
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
                this.$find('.' + constants.contentListClass).empty();
                this.$container.addClass(constants.isLoadingClass);
                this.sandbox.util.ajax({
                    method: 'GET',
                    url: this.URI.str,
                    data: this.URI.data,

                    success: function(data) {
                        this.$container.removeClass(constants.isLoadingClass);
                        if (!!this.options.has.datasource && data[this.options.datasourceKey]) {
                            this.overlayData.title = data[this.options.datasourceKey][this.options.titleKey];
                            this.overlayData.fullQualifiedTitle = data[this.options.datasourceKey][this.options.pathKey];
                        } else {
                            this.overlayData.title = null;
                            this.overlayData.fullQualifiedTitle = '';
                        }
                        this.items = data._embedded[this.options.resultKey];
                        this.updateSelectedCounter(this.items.length);
                        this.sandbox.emit(DATA_RETRIEVED.call(this));
                    }.bind(this),

                    error: function(error) {
                        this.sandbox.logger.log(error);
                    }.bind(this)
                });
            }
        },

        /**
         * Writes a passed number into the select-counter dom element
         * @param num
         */
        updateSelectedCounter: function(num) {
            this.$header.find('.selected-counter .num').html(num);
        },

        /**
         * Gets the values of all user inputs of the overlay
         * event is emitted on which the associated component responses
         */
        getOverlayData: function() {
            var tagsDef, tagOperatorDef, sortByDef, sortMethodDef, presentAsDef, temp;
            tagsDef = tagOperatorDef = sortByDef = sortMethodDef = presentAsDef = this.sandbox.data.deferred();

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

            //tags
            this.sandbox.emit('husky.auto-complete-list.' + this.options.instanceName + constants.tagListClass + '.get-tags',
                function(tags) {
                    this.overlayData.tags = tags;
                    tagsDef.resolve();
                }.bind(this));

            //tag operators
            this.sandbox.emit('husky.select.' + this.options.instanceName + constants.tagOperatorClass + '.get-checked',
                function(tagOperator) {
                    this.overlayData.tagOperator = (tagOperator[0] === operators.and) ? operators.and : operators.or;
                    tagOperatorDef.resolve();
                }.bind(this));

            //sort by
            this.sandbox.emit('husky.select.' + this.options.instanceName + constants.sortByDropdownClass + '.get-checked',
                function(sortBy) {
                    this.overlayData.sortBy = sortBy;
                    sortByDef.resolve();
                }.bind(this));

            //sort method
            this.sandbox.emit('husky.select.' + this.options.instanceName + constants.sortMethodDropdownClass + '.get-checked',
                function(sortMethod) {
                    this.overlayData.sortMethod = (sortMethod[0] === sortMethods.asc) ? 'asc' : 'desc';
                    sortMethodDef.resolve();
                }.bind(this));

            //present as
            this.sandbox.emit('husky.select.' + this.options.instanceName + constants.presentAsDropdownClass + '.get-checked',
                function(presentAs) {
                    if (presentAs.length === 1) {
                        this.overlayData.presentAs = presentAs[0];
                    } else {
                        this.overlayData.presentAs = null;
                    }
                    presentAsDef.resolve();
                }.bind(this));

            this.sandbox.dom.when(tagsDef.promise(), tagOperatorDef.promise(), sortByDef.promise(), sortMethodDef.promise(), presentAsDef.promise()).then(function() {
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
        },

        /**
         * Resets content.
         */
        clear: function() {
            this.overlayData = {
                dataSource: '',
                includeSubFolders: false,
                limitResult: null,
                presentAs: null,
                sortBy: [],
                sortMethod: 'asc',
                categoryOperator: 'or',
                categories: [],
                tags: [],
                tagOperator: 'or'
            };

            this.$overlayContent.html('');
            this.appendOverlayContent(this.$overlayContent, this.overlayData);
            this.startOverlayComponents();
            this.handleCategoriesInitialized({ids: [], operator: 'or', items: []});
            this.sandbox.emit(
                'smart-content.datasource.' + this.options.instanceName + '.set-selected',
                this.overlayData.dataSource
            );
        }
    };
});
