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
 * @class Header
 * @constructor
 *
 * @param {Object} [options] Configuration object
 * @param {String} [options.instanceName] name of the instance
 * @param {Object} [options.tabsData] data to pass to the tabs component. For data-structure markup see husky
 * @param {Object} [options.tabsParentOptions] The options-object of the tabs-parent-component. this options get merged into each tabs-component-option
 * @param {Object} [options.tabsOption] an object of options which gets merged into each tabs-component option
 * @param {Object} [options.tabsComponentOptions] an object of options to pass to the husky-tab-component
 * @param {String|Object} [options.tabsContainer] Selector or dom object to insert the the tabs-content into
 * @param {Object} [options.toolbarOptions] options to pass to the toolbar-component
 * @param {Array} [options.toolbarButtons] Array of arguments to pass to the sulu.buttons.get function to recieve the toolbar-buttons
 * @param {Boolean|Object} [options.toolbarLanguageChanger] If true a default-language changer will be displayed. Can be an object to build a custom language changer
 * @param {Function} [options.toolbarLanguageChanger.callback] callback to pass the clicked language-item to
 * @param {String} [options.toolbarLanguageChanger.preselected] id of the language selected at the beginning
 * @param {Boolean} [options.toolbarDisabled] if true the toolbar-component won't be initialized
 * @param {Boolean} [options.noBack] if true the back icon won't be displayed
 * @param {String} [options.scrollContainerSelector] determines the box which gets observed for hiding the tabs on scroll
 * @param {String} [options.scrollDelta] this much pixels must be scrolled before the tabs get hidden or shown
 */

define([], function() {

    'use strict';

    var defaults = {
            instanceName: '',
            tabsData: null,
            tabsParentOptions: {},
            tabsOption: {},
            tabsComponentOptions: {},
            toolbarOptions: {},
            tabsContainer: null,
            toolbarLanguageChanger: false,
            toolbarButtons: [],
            toolbarDisabled: false,
            noBack: false,
            scrollContainerSelector: '.content-column > .wrapper .page',
            scrollDelta: 50 //px
        },

        constants = {
            componentClass: 'sulu-header',
            hasTabsClass: 'has-tabs',
            hasLabelClass: 'has-label',
            backClass: 'back',
            backIcon: 'chevron-left',
            toolbarClass: 'toolbar',
            tabsRowClass: 'tabs-row',
            tabsClass: 'tabs',
            tabsLabelContainer: 'tabs-label',
            tabsSelector: '.tabs-container',
            toolbarSelector: '.toolbar-container',
            rightSelector: '.right-container',
            languageChangerTitleSelector: '.language-changer .title',
            hideTabsClass: 'tabs-hidden',
            tabsContentClass: 'tabs-content',
            contentTitleClass: 'sulu-title',
            breadcrumbContainerClass: 'sulu-breadcrumb',
            toolbarDefaults: {
                groups: [
                    {id: 'left', align: 'left'}
                ]
            },
            languageChangerDefaults: {
                instanceName: 'header-language',
                alignment: 'right',
                valueName: 'title'
            }
        },

        templates = {
            toolbarRow: [
                '<div class="blue-row"></div>',
                '<div class="toolbar-row">',
                '   <div class="left-container ' + constants.backClass + '">',
                '       <span class="fa-' + constants.backIcon + '"></span>',
                '   </div>',
                '   <div class="toolbar-container">',
                '       <div class="' + constants.toolbarClass + '"></div>',
                '   </div>',
                '   <div class="right-container">',
                '   </div>',
                '</div>'
            ].join(''),
            tabsRow: [
                '<div class="', constants.tabsRowClass, '">',
                '    <div class="', constants.tabsClass, '"></div>',
                '</div>'
            ].join(''),
            languageChanger: [
                '<div class="language-changer">',
                '   <span class="title"><%= title %></span>',
                '   <span class="dropdown-toggle"></span>',
                '</div>'
            ].join(''),
            titleElement: [
                '<div class="' + constants.contentTitleClass + '">',
                '   <div class="content-title <% if(!!underline){ %>underlined<% } %>">',
                '       <h2><%= title %></h2>',
                '   </div>',
                '</div>'
            ].join('')
        },

        createEventName = function(postfix) {
            return 'sulu.header.' + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
        },

        /**
         * trigger after initialization has finished
         *
         * @event sulu.header.[INSTANCE_NAME].initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * emitted when the back-icon gets clicked
         *
         * @event sulu.header.[INSTANCE_NAME].back
         */
        BACK = function() {
            return createEventName.call(this, 'back');
        },

        /**
         * Emitted if the language changer got changed
         *
         * @event sulu.header.[INSTANCE_NAME].language-changed
         * @param {string} the language which got changed to
         */
        LANGUAGE_CHANGED = function() {
            return createEventName.call(this, 'language-changed');
        },

        /**
         * Emitted when a breadcrumb item got clicked
         *
         * @event sulu.header.[INSTANCE_NAME].breadcrumb-clicked
         * @param {Object} The data of the breadcrumb item
         */
        BREADCRUMB_CLICKED = function() {
            return createEventName.call(this, 'breadcrumb-clicked');
        },

        /**
         * changes the html value of the language changer
         *
         * @event sulu.header.[INSTANCE_NAME].change-language
         * @param {string} the language to change to
         */
        CHANGE_LANGUAGE = function() {
            return createEventName.call(this, 'change-language');
        },

        /**
         * emited if switched to a tab with no specified component
         *
         * @event sulu.header.[INSTANCE_NAME].tab-changed
         * @param {Object} the tab item switched to
         */
        TAB_CHANGED = function() {
            return createEventName.call(this, 'tab-changed');
        },

        /**
         * listens on and renders a title
         *
         * @event sulu.header.[INSTANCE_NAME].set-title
         * @param {String} the title to render
         */
        SET_TITLE = function() {
            return createEventName.call(this, 'set-title');
        },

        /**
         * listens on and update for example the tabs
         *
         * @event sulu.header.[INSTANCE_NAME].set-title
         * @param {Object} data save data
         */
        SAVED = function() {
            return createEventName.call(this, 'saved');
        },

        /**
         * listens on and initializes a blank toolbar with given options
         *
         * @deprecated This event is deprecated. Try to set the toolbar when starting the header
         * @event sulu.header.[INSTANCE_NAME].set-toolbar
         * @param {object} the toolbar options
         */
        SET_TOOLBAR = function() {
            return createEventName.call(this, 'set-toolbar');
        },

    /*********************************************
     *   Abstract events
     ********************************************/

        /**
         * listens on activates tabs
         *
         * @event sulu.header.[INSTANCE_NAME].tabs.activate
         */
        TABS_ACTIVATE = function() {
            return createEventName.call(this, 'tabs.activate');
        },

        /**
         * listens on deactivates tabs
         *
         * @event sulu.header.[INSTANCE_NAME].tabs.activate
         */
        TABS_DEACTIVATE = function() {
            return createEventName.call(this, 'tabs.deactivate');
        },

        /**
         * listens on showing tab labels
         *
         * @event sulu.header.[INSTANCE_NAME].tabs.label.show
         */
        TABS_LABEL_SHOW = function() {
            return createEventName.call(this, 'tabs.label.show');
        },

        /**
         * listens on hiding tab labels
         *
         * @event sulu.header.[INSTANCE_NAME].tabs.label.hide
         */
        TABS_LABEL_HIDE = function() {
            return createEventName.call(this, 'tabs.label.hide');
        },

        /**
         * listens on and sets a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.button.set
         * @param {string} id The id of the button
         * @param {object} object with a icon and title
         */
        TOOLBAR_BUTTON_SET = function() {
            return createEventName.call(this, 'toolbar.button.set');
        },

        /**
         * listens on and sets an item in loading state
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.loading
         * @param {string} id The id of the item
         */
        TOOLBAR_ITEM_LOADING = function() {
            return createEventName.call(this, 'toolbar.item.loading');
        },

        /**
         * listens on and changes the item of a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.change
         * @param {string} button The id of the button
         * @param {string} item the id or the index of the dropdown-item
         */
        TOOLBAR_ITEM_CHANGE = function() {
            return createEventName.call(this, 'toolbar.item.change');
        },

        /**
         * listens on and marks a subitem
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.mark
         * @param {string} item The id of the subitem
         */
        TOOLBAR_ITEM_MARK = function() {
            return createEventName.call(this, 'toolbar.item.mark');
        },

        /**
         * listens on and shows a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.show
         * @param {string} button The id of the button
         */
        TOOLBAR_ITEM_SHOW = function() {
            return createEventName.call(this, 'toolbar.item.show');
        },

        /**
         * listens on and hides a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.hide
         * @param {string} button The id of the button
         */
        TOOLBAR_ITEM_HIDE = function() {
            return createEventName.call(this, 'toolbar.item.hide');
        },

        /**
         * listens on and enables a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.enable
         * @param {string} button The id of the button
         * @param {Boolean} true to highlight the button on change
         */
        TOOLBAR_ITEM_ENABLE = function() {
            return createEventName.call(this, 'toolbar.item.enable');
        },

        /**
         * listens on and disables a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.enable
         * @param {string} button The id of the button
         * @param {Boolean} true to highlight the button on change
         */
        TOOLBAR_ITEM_DISABLE = function() {
            return createEventName.call(this, 'toolbar.item.disable');
        },

        /**
         * listens on and shows back icon
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.items.set
         * @param id {string|number} id of the parent item
         * @param items {array} array of items to set
         */
        TOOLBAR_ITEMS_SET = function() {
            return createEventName.call(this, 'toolbar.items.set');
        };

    return {
        /**
         * Initializes the component
         */
        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            // set default callback when no callback is provided

            // store the instance-name of the toolbar
            this.toolbarInstanceName = 'header' + this.options.instanceName;
            this.oldScrollPosition = 0;
            this.tabsAction = null;

            this.bindCustomEvents();
            this.render();
            this.bindDomEvents();

            var toolbarDef, tabsDef;
            toolbarDef = this.startToolbar();
            this.startLanguageChanger();
            tabsDef = this.startTabs();

            this.sandbox.data.when(toolbarDef, tabsDef).then(function() {
                this.sandbox.emit(INITIALIZED.call(this));
                this.oldScrollPosition = this.sandbox.dom.scrollTop(this.options.scrollContainerSelector);
            }.bind(this));
        },

        /**
         * Destroys the component
         */
        destroy: function() {
            this.removeTitle();
        },

        /**
         * Renders the component
         */
        render: function() {
            // add component-class
            this.sandbox.dom.addClass(this.$el, constants.componentClass);

            this.sandbox.dom.append(this.$el, this.sandbox.util.template(templates.toolbarRow)());
            this.sandbox.dom.append(this.$el, this.sandbox.util.template(templates.tabsRow)());

            // render title if there are no tabs (else they are rendered in tabChangeHandler)
            if (!this.options.tabsData) {
                this.renderTitle();
            }

            // hide back if configured
            if (this.options.noBack === true) {
                this.sandbox.dom.hide(this.$find('.' + constants.backClass));
            } else {
                this.sandbox.dom.show(this.$find('.' + constants.backClass));
            }
        },

        /**
         * Renders the title into the main-view or (if tabs exist) injects the title into the current tab
         */
        renderTitle: function() {
            var title = (typeof this.options.title === 'function') ? this.options.title() : this.options.title,
                $element;

            this.removeTitle();
            if (!!title) {
                $element = this.sandbox.dom.createElement(this.sandbox.util.template(templates.titleElement, {
                    title: this.sandbox.util.escapeHtml(this.sandbox.translate(title)),
                    underline: this.options.underline
                }));
                $('.page').prepend($element);
                this.renderBreadcrumb($element.children().first());
            }
        },

        /**
         * Renders the breadcrumb into a given container. If no breadcrumb
         * got passed, this method does nothing.
         *
         * @param {Object} $container The container to render the breadcrumb in
         */
        renderBreadcrumb: function($container) {
            var breadcrumbs = this.options.breadcrumb, $element;
            breadcrumbs = (typeof breadcrumbs === 'function') ? breadcrumbs() : breadcrumbs;

            if (!breadcrumbs) {
                return;
            }

            $element = $('<div class="' + constants.breadcrumbContainerClass + '"/>');
            $container.append($element);

            this.sandbox.start([{
                name: 'breadcrumbs@suluadmin',
                options: {
                    el: $element,
                    instanceName:'header',
                    breadcrumbs: breadcrumbs
                }
            }]);
        },

        /**
         * Sets a new title
         * @param {String} title the new title to set
         */
        setTitle: function(title) {
            this.options.title = title;
            this.renderTitle();
        },

        /**
         * Removes the title element from the dom
         */
        removeTitle: function() {
            $('.page').find('.' + constants.contentTitleClass).remove();
        },

        /**
         * Handles the start of the Tabs
         */
        startTabs: function() {
            var def = this.sandbox.data.deferred();

            if (!this.options.tabsData) {
                def.resolve();
            } else if (this.options.tabsData.length > 0) {
                this.startTabsComponent(def);
            } else {
                def.resolve();
            }

            return def;
        },

        /**
         * Starts the tabs component
         * @param {deferred} def
         */
        startTabsComponent: function(def) {
            if (!!this.options.tabsData) {
                var $container = this.sandbox.dom.createElement('<div/>'),
                    options = {
                        el: $container,
                        data: this.options.tabsData,
                        instanceName: 'header' + this.options.instanceName,
                        forceReload: false,
                        forceSelect: true,
                        fragment: this.sandbox.mvc.history.fragment
                    };

                // wait for initialized
                this.sandbox.once('husky.tabs.header.initialized', function(selectedItem, visibleTabs) {
                    if (visibleTabs > 1) {
                        this.sandbox.dom.addClass(this.$el, constants.hasTabsClass);
                    }

                    def.resolve();
                }.bind(this));

                this.sandbox.dom.html(this.$find('.' + constants.tabsClass), $container);

                options = this.sandbox.util.extend(true, {}, options, this.options.tabsComponentOptions);
                this.sandbox.start([
                    {
                        name: 'tabs@husky',
                        options: options
                    }
                ]);
            }
        },

        /**
         * Handles the starting of the toolbar
         */
        startToolbar: function() {
            var def = this.sandbox.data.deferred();

            if (this.options.toolbarDisabled !== true) {
                var options = this.options.toolbarOptions;
                options = this.sandbox.util.extend(true, {}, constants.toolbarDefaults, options, {
                    buttons: this.sandbox.sulu.buttons.get.call(this, this.options.toolbarButtons)
                });
                // start toolbar component with built options
                this.startToolbarComponent(options, def);
            } else {
                def.resolve();
            }

            return def;
        },

        /**
         * Renders and starts the language-changer dropdown
         */
        startLanguageChanger: function() {
            if (!!this.options.toolbarLanguageChanger) {
                var $element = this.sandbox.dom.createElement(this.sandbox.util.template(templates.languageChanger)({
                            title: this.options.toolbarLanguageChanger.preSelected
                                || this.sandbox.sulu.getDefaultContentLocale()
                    })),
                    options = constants.languageChangerDefaults;
                this.sandbox.dom.show(this.$find(constants.rightSelector));
                this.sandbox.dom.append(this.$find(constants.rightSelector), $element);
                options.el = $element;
                options.data = this.options.toolbarLanguageChanger.data || this.getDefaultLanguages();

                this.sandbox.start([{
                    name: 'dropdown@husky',
                    options: options
                }]);
            } else {
                this.sandbox.dom.hide(this.$find(constants.rightSelector));
            }
        },

        /**
         * Returns an array of objects with id and title property containing the system locales
         * @returns {Array}
         */
        getDefaultLanguages: function() {
            var items = [], i, length;
            for (i = -1, length = this.sandbox.sulu.locales.length; ++i < length;) {
                items.push({
                    id: this.sandbox.sulu.locales[i],
                    title: this.sandbox.sulu.locales[i]
                });
            }
            return items;
        },

        /**
         * Starts the husky-component
         * @param {object} options The options to pass to the toolbar component
         * @param {deferred} def
         */
        startToolbarComponent: function(options, def) {
            var $container = this.sandbox.dom.createElement('<div />'),
            // global default values
                componentOptions = {
                    el: $container,
                    skin: 'big',
                    instanceName: this.toolbarInstanceName,
                    responsive: true
                };

            // wait for initialized
            if (!!def) {
                this.sandbox.once('husky.toolbar.' + this.toolbarInstanceName + '.initialized', function() {
                    def.resolve();
                }.bind(this));
            }

            this.sandbox.dom.html(this.$find('.' + constants.toolbarClass), $container);

            // merge default tabs-options with passed ones
            componentOptions = this.sandbox.util.extend(true, {}, componentOptions, options);

            this.sandbox.start([
                {
                    name: 'toolbar@husky',
                    options: componentOptions
                }
            ]);
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {
            this.sandbox.on('husky.dropdown.header-language.item.click', this.languageChanged.bind(this));

            this.sandbox.on('husky.tabs.header.initialized', this.tabChangedHandler.bind(this));
            this.sandbox.on('husky.tabs.header.item.select', this.tabChangedHandler.bind(this));

            this.sandbox.on('sulu.breadcrumbs.header.breadcrumb-clicked', function(breadcrumbData) {
                this.sandbox.emit(BREADCRUMB_CLICKED.call(this), breadcrumbData);
            }.bind(this));

            this.sandbox.on(SET_TOOLBAR.call(this), this.setToolbar.bind(this));
            this.sandbox.on(SET_TITLE.call(this), this.setTitle.bind(this));
            this.sandbox.on(SAVED.call(this), this.saved.bind(this));
            this.sandbox.on(CHANGE_LANGUAGE.call(this), this.setLanguageChanger.bind(this));
            this.bindAbstractToolbarEvents();
            this.bindAbstractTabsEvents();
        },

        saved: function(data) {
            // wait for initialized
            this.sandbox.once('husky.tabs.header.updated', function(visibleTabs) {
                if (visibleTabs > 1) {
                    this.sandbox.dom.addClass(this.$el, constants.hasTabsClass);
                } else {
                    this.sandbox.dom.removeClass(this.$el, constants.hasTabsClass);
                }
            }.bind(this));

            this.sandbox.emit('husky.tabs.header.update', data);
        },

        /**
         * Stops the current toolbar and starts a new one
         * @deprecated
         * @param toolbar
         */
        setToolbar: function(toolbar) {
            if (typeof toolbar.languageChanger !== 'undefined') {
                this.options.toolbarLanguageChanger = toolbar.languageChanger;
            }
            this.options.toolbarDisabled = false;
            this.options.toolbarOptions = toolbar.options || this.options.toolbarOptions;
            this.options.toolbarButtons = toolbar.buttons || this.options.toolbarButtons;
            this.sandbox.stop(this.$find('.' + constants.toolbarClass + ' *'));
            this.sandbox.stop(this.$find(constants.rightSelector + ' *'));
            this.startToolbar();
            this.startLanguageChanger();
        },

        /**
         * Renders and starts a tab-content component if specified. if not emits an event
         * @param tabItem {Object} the Tabs object
         */
        tabChangedHandler: function(tabItem) {
            if (!!tabItem.component) {
                var options;
                if (!tabItem.forceReload && tabItem.action === this.tabsAction) {
                    return false; // no reload required
                }
                this.tabsAction = tabItem.action;
                // resets store to prevent duplicated models
                if (!!tabItem.resetStore) {
                    this.sandbox.mvc.Store.reset();
                }
                this.stopTabContent();

                var $container = this.sandbox.dom.createElement('<div class="' + constants.tabsContentClass + '"/>');
                this.sandbox.dom.append(this.options.tabsContainer, $container);

                options = this.sandbox.util.extend(true, {},
                    this.options.tabsParentOption,
                    this.options.tabsOption,
                    {el: $container},
                    tabItem.componentOptions);

                this.sandbox.start([{
                    name: tabItem.component,
                    options: options
                }]).then(function(tabComponent) {
                    // render title if view title is set and tab-option noTitle title is false or not set
                    if (!!tabComponent.tabOptions && !!tabComponent.tabOptions.noTitle) {
                        this.removeTitle();
                    } else {
                        this.renderTitle();
                    }
                }.bind(this));
            } else {
                // if no component is set render title
                this.renderTitle();

                this.sandbox.emit(TAB_CHANGED.call(this), tabItem);
            }
        },

        /**
         * Stops the tab-content-component
         */
        stopTabContent: function() {
            App.stop('.' + constants.tabsContentClass + ' *');
            App.stop('.' + constants.tabsContentClass);
        },

        /**
         * Handles the change of the language-changer
         * @param item
         */
        languageChanged: function(item) {
            this.setLanguageChanger(item.title);
            this.sandbox.emit(LANGUAGE_CHANGED.call(this), item);
        },

        /**
         * Changes the html of the language changer to a given string
         * @param {String} newLanguage the string to show in the language changer
         */
        setLanguageChanger: function(newLanguage) {
            this.sandbox.dom.html(this.$find(constants.languageChangerTitleSelector), newLanguage);
        },

        /**
         * Abstracts husky-toolbar events
         */
        bindAbstractToolbarEvents: function() {
            this.sandbox.on(TOOLBAR_ITEMS_SET.call(this), function(id, items) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.items.set', id, items);
            }.bind(this));

            this.sandbox.on(TOOLBAR_BUTTON_SET.call(this), function(id, object) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.button.set', id, object);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_LOADING.call(this), function(id) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.loading', id);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_CHANGE.call(this), function(id, name) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.change', id, name);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_SHOW.call(this), function(id, name) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.show', id, name);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_HIDE.call(this), function(id, name) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.hide', id, name);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_ENABLE.call(this), function(id, highlight) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.enable', id, highlight);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_DISABLE.call(this), function(id, highlight) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.disable', id, highlight);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_MARK.call(this), function(id) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.mark', id);
            }.bind(this));
        },

        /**
         * Abstracts husky-tabs events
         */
        bindAbstractTabsEvents: function() {
            this.sandbox.on(TABS_ACTIVATE.call(this), function() {
                this.sandbox.emit('husky.tabs.header.deactivate');
            }.bind(this));

            this.sandbox.on(TABS_DEACTIVATE.call(this), function() {
                this.sandbox.emit('husky.tabs.header.activate');
            }.bind(this));

            this.sandbox.on(TABS_LABEL_SHOW.call(this), function(description, buttons) {
                this.showTabsLabel(description, buttons);
            }.bind(this));

            this.sandbox.on(TABS_LABEL_HIDE.call(this), function() {
                this.hideTabsLabel();
            }.bind(this));
        },

        /**
         * Bind Dom-events
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.$el, 'click', function() {
                this.sandbox.emit(BACK.call(this));
            }.bind(this), '.' + constants.backClass);

            if (!!this.options.tabsData) {
                this.sandbox.dom.on(this.options.scrollContainerSelector, 'scroll', this.scrollHandler.bind(this));
            }
        },

        /**
         * Handles the scroll event to hide or show the tabs
         */
        scrollHandler: function() {
            var scrollTop = this.sandbox.dom.scrollTop(this.options.scrollContainerSelector);
            if (scrollTop <= this.oldScrollPosition - this.options.scrollDelta || scrollTop < this.options.scrollDelta) {
                this.showTabs();
                this.oldScrollPosition = scrollTop;
            } else if (scrollTop >= this.oldScrollPosition + this.options.scrollDelta) {
                this.hideTabs();
                this.oldScrollPosition = scrollTop;
            }
        },

        /**
         * Hides the tabs
         */
        hideTabs: function() {
            this.sandbox.dom.addClass(this.$el, constants.hideTabsClass);
        },

        /**
         * Shows the tabs
         */
        showTabs: function() {
            this.sandbox.dom.removeClass(this.$el, constants.hideTabsClass);
        },

        /**
         * Shows a tabs label with the given text.
         *
         * @param {String} description
         * @param {Array} buttons
         */
        showTabsLabel: function(description, buttons) {
            var $tabsLabelContainer = $('<div class="' + constants.tabsLabelContainer + '"/>');

            this.$find('.' + constants.tabsRowClass).append($tabsLabelContainer);

            this.sandbox.emit(
                'sulu.labels.label.show',
                {
                    instanceName: 'header',
                    el: $tabsLabelContainer,
                    type: 'WARNING',
                    description: description,
                    title: '',
                    autoVanish: false,
                    hasClose: false,
                    additionalLabelClasses: 'small',
                    buttons: buttons || []
                }
            );

            this.sandbox.dom.addClass(this.$el, constants.hasLabelClass);
        },

        /**
         * Hides all the tabs labels.
         */
        hideTabsLabel: function() {
            this.sandbox.stop('.' + constants.tabsLabelContainer);
            this.$el.removeClass(constants.hasLabelClass);
        }
    };
});
