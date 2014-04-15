/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

define([], function() {

    'use strict';

    var defaults = {
            heading: '',
            toolbarTemplate: 'default',
            toolbarParentTemplate: null,
            instanceName: '',
            changeStateCallback: null,
            parentChangeStateCallback: null,
            tabsData: null
        },

        constants = {
            componentClass: 'sulu-header',
            infoClass: 'info',
            headlineClass: 'headline',
            backClass: 'back',
            backIcon: 'chevron-left',
            toolbarClass: 'toolbar',
            tabsClass: 'tabs',
            innerSelector: '.inner',
            tabsSelector: '.tabs-container'
        },

        toolbarTemplates = {
            default: function() {
                return[
                    {
                        id: 'save-button',
                        icon: 'floppy',
                        disabledIcon: 'floppy-saved',
                        iconSize: 'large',
                        class: 'highlight',
                        position: 1,
                        group: 'left',
                        disabled: true,
                        callback: function() {
                            this.sandbox.emit('sulu.header.toolbar.save');
                        }.bind(this)
                    },
                    {
                        icon: 'cogwheel',
                        iconSize: 'large',
                        group: 'left',
                        id: 'options-button',
                        position: 30,
                        items: [
                            {
                                title: this.sandbox.translate('toolbar.delete'),
                                callback: function() {
                                    this.sandbox.emit('sulu.header.toolbar.delete');
                                }.bind(this)
                            }
                        ]
                    }
                ];
            }
        },


        templates = {
            skeleton: [
                '<div class="inner">',
                    '<div class="'+ constants.infoClass +'"></div>',
                    '<div class="'+ constants.headlineClass +'">',
                        '<span class="icon-'+ constants.backIcon +' '+ constants.backClass +'"></span>',
                        '<h1 class="bright"><%= headline %></h1>',
                    '</div>',
                    '<div class="'+ constants.toolbarClass +'"></div>',
                '</div>'
            ].join(''),

            breadcrumbItem: [
                '<li>',
                    '<a data-sulu-navigate="true" href="<%= link %>"><%= title %></a>',
                '</li>'
            ].join('\n')
        },


        changeStateCallbacks = {
            default: function(saved, type, highlight) {
                if (!!saved) {
                    this.sandbox.emit('husky.toolbar.header.item.disable', 'save-button', !!highlight);
                } else {
                    this.sandbox.emit('husky.toolbar.header.item.enable', 'save-button', false);
                }
            }
        },

        getToolbarTemplate = function(template) {
            var templateObj = template;
            if (typeof template === 'string') {
                try {
                    templateObj = JSON.parse(template);
                } catch (e) {
                    if (!!toolbarTemplates[template]) {
                        templateObj = toolbarTemplates[template].call(this);
                    } else {
                        this.sandbox.logger.log('no template found!');
                    }
                }
            } else if (typeof templateObj === 'function') {
                templateObj = template();
            }
            return templateObj;
        },

        getChangeToolbarStateCallback = function(template) {
            if (!!changeStateCallbacks[template]) {
                return changeStateCallbacks[template];
            } else {
                this.sandbox.logger.log('no template found!');
            }
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
         * listens on and sets the breadcrumb
         *
         * @event sulu.header.[INSTANCE_NAME].set-breadcrumb
         * @param {array} breadcrumb Array of breadcrumb-objects with a title and link attribute
         */
        SET_BREADCRUMB = function() {
            return createEventName.call(this, 'set-breadcrumb');
        },

        /**
         * listens on and sets the title
         *
         * @event sulu.header.[INSTANCE_NAME].set-title
         * @param {string} title to set
         */
        SET_TITLE = function() {
            return createEventName.call(this, 'set-title');
        },

        /**
         * listens on and hides back icon
         *
         * @event sulu.header.[INSTANCE_NAME].show-back
         * @param {boolean} animate If true back icon gets hidden with an animation
         */
        HIDE_BACK = function() {
            return createEventName.call(this, 'hide-back');
        },

        /**
         * listens on and shows back icon
         *
         * @event sulu.header.[INSTANCE_NAME].show-back
         */
        SHOW_BACK = function() {
            return createEventName.call(this, 'show-back');
        },

        /**
         * listens on and changes the state of the toolbar
         *
         * @event sulu.header.[INSTANCE_NAME].show-back
         * @param {string} type 'add' or 'edit'
         * @param {boolean} saved If false toolbar gets set in dirty state
         * @param {boolean} highlight True to change with highlight effect
         */
        TOOLBAR_STATE_CHANGE = function() {
            return createEventName.call(this, 'toolbar.state.change');
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
         * listens on and shows a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.show
         * @param {string} button The id of the button
         */
        TOOLBAR_ITEM_SHOW = function() {
            return createEventName.call(this, 'toolbar.item.show');
        },

        /**
         * listens on and enables a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.enable
         * @param {string} button The id of the button
         */
        TOOLBAR_ITEM_ENABLE = function() {
            return createEventName.call(this, 'toolbar.item.enable');
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
        view: true,

        /**
         * Initializes the component
         */
        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.$inner = null;
            this.$tabs = null;

            this.render();
            this.setPosition();

            this.startToolbar();
            this.startTabs();

            // bind events
            this.bindCustomEvents();
            this.bindDomEvents();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Handles the placing of the component at the beginning
         */
        setPosition: function() {
            // wait for tabs to initialize if there are tabs
            if (this.options.tabsData !== null) {
                this.sandbox.on('husky.tabs.header'+ this.options.instanceName +'.initialized', function() {
                    this.sandbox.emit('sulu.app.content.get-dimensions', this.setDimensions.bind(this));
                }.bind(this));
            } else {
                this.sandbox.emit('sulu.app.content.get-dimensions', this.setDimensions.bind(this));
            }
        },

        /**
         * Applies an object with dimensions to the component element
         * @param dimensions
         */
        setDimensions: function(dimensions) {
            this.sandbox.dom.css(this.$inner, {
                'margin-left': dimensions.left + 'px'
            });
            this.sandbox.dom.width(this.$inner, dimensions.width);

            if (this.$tabs !== null) {
                this.sandbox.dom.css(this.sandbox.dom.find(constants.tabsSelector, this.$tabs), {
                   'padding-left': dimensions.left + 'px'
                });
            }
        },

        /**
         * Renders the component
         */
        render: function() {
            // add component-class
            this.sandbox.dom.addClass(this.$el, constants.componentClass);

            this.html(this.sandbox.util.template(templates.skeleton)({
                headline: this.options.heading
            }));

            this.$inner = this.$find(constants.innerSelector);
        },

        /**
         * Builds the template of items for the Toolbar
         */
        buildToolbarTemplate: function(template, parentTemplate) {
            this.options.toolbarTemplate = getToolbarTemplate.call(this, template);
            if (!this.options.changeStateCallback || typeof this.options.changeStateCallback !== 'function') {
                this.options.changeStateCallback = getChangeToolbarStateCallback.call(this, template);
            }

            //if a parentTemplate is set merge it with the current template
            if (this.options.toolbarParentTemplate !== null) {

                this.options.toolbarParentTemplate = getToolbarTemplate.call(this, parentTemplate);
                if (!this.options.parentChangeStateCallback || typeof this.options.parentChangeStateCallback !== 'function') {
                    this.options.parentChangeStateCallback = getChangeToolbarStateCallback.call(this, parentTemplate);
                }

                this.options.toolbarTemplate = this.options.toolbarTemplate.concat(this.options.toolbarParentTemplate);
            }
        },

        /**
         * Handles the start of the Tabs
         */
        startTabs: function() {
            if (this.options.tabsData !== null) {
                this.$tabs = this.sandbox.dom.createElement('<div class="'+ constants.tabsClass +'"></div>');
                this.sandbox.dom.append(this.$el, this.$tabs);
                this.startTabsComponent();
            }
        },

        /**
         * Starts the tabs component
         */
        startTabsComponent: function() {
            this.sandbox.stop(this.$find('.' + constants.tabsClass));
            var $container = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.html(this.$find('.' + constants.tabsClass), $container);

            this.sandbox.start([
                {
                    name: 'tabs@husky',
                    options: {
                        el: $container,
                        data: this.options.tabsData,
                        instanceName: 'header' + this.options.instanceName,
                        forceReload: false,
                        forceSelect: true
                    }
                }
            ]);
        },

        /**
         * Handles the starting of the toolbar
         */
        startToolbar: function() {
            this.buildToolbarTemplate(this.options.toolbarTemplate, this.options.toolbarParentTemplate);
            this.startToolbarComponent();
        },

        /**
         * Starts the husky-component
         */
        startToolbarComponent: function() {
            var $container = this.sandbox.dom.createElement('<div />');

            this.sandbox.stop(this.$find('.' + constants.toolbarClass));
            this.sandbox.dom.html(this.$find('.' + constants.toolbarClass), $container);

            this.sandbox.start([
                {
                    name: 'toolbar@husky',
                    options: {
                        el: $container,
                        groups: [
                            {id: 'left', align: 'left'},
                            {id: 'right', align: 'right'}
                        ],
                        data: this.options.toolbarTemplate,
                        skin: 'blueish',
                        instanceName: 'header' + this.options.instanceName
                    }
                }
            ]);
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {
            // change dimensions if content-dimensions change
            this.sandbox.on('sulu.app.content.dimensions-changed', this.setDimensions.bind(this));

            // changes the saved state of the toolbar
            this.sandbox.on(TOOLBAR_STATE_CHANGE.call(this), this.changeToolbarState.bind(this));

            // show back icon
            this.sandbox.on(SHOW_BACK.call(this), this.showBack.bind(this));

            // hide back icon
            this.sandbox.on(HIDE_BACK.call(this), this.hideBack.bind(this));

            // set breadcrumb
            this.sandbox.on(SET_BREADCRUMB.call(this), this.setBreadcrumb.bind(this));

            // change the title
            this.sandbox.on(SET_TITLE.call(this), this.setTitle.bind(this));

            this.bindAbstractToolbarEvents();
            this.bindAbstractTabsEvents();
        },

        /**
         * Abstracts husky-toolbar events
         */
        bindAbstractToolbarEvents: function() {
            this.sandbox.on(TOOLBAR_ITEMS_SET.call(this), function(id, items) {
                this.sandbox.emit('husky.toolbar.header'+ this.options.instanceName +'.items.set', id, items);
            }.bind(this));

            this.sandbox.on(TOOLBAR_BUTTON_SET.call(this), function(id, object) {
                this.sandbox.emit('husky.toolbar.header'+ this.options.instanceName +'.button.set', id, object);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_LOADING.call(this), function(id) {
                this.sandbox.emit('husky.toolbar.header'+ this.options.instanceName +'.item.loading', id);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_CHANGE.call(this), function(id, name) {
                this.sandbox.emit('husky.toolbar.header'+ this.options.instanceName +'.item.change', id, name);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_SHOW.call(this), function(id, name) {
                this.sandbox.emit('husky.toolbar.header'+ this.options.instanceName +'.item.show', id, name);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_ENABLE.call(this), function(id, highlight) {
                this.sandbox.emit('husky.toolbar.header'+ this.options.instanceName +'.item.enable', id, highlight);
            }.bind(this));
        },

        /**
         * Abstracts husky-tabs events
         */
        bindAbstractTabsEvents: function() {
            this.sandbox.on(TABS_ACTIVATE.call(this), function() {
                this.sandbox.emit('husky.tabs.header'+ this.options.instanceName +'.deactivate');
            }.bind(this));

            this.sandbox.on(TABS_DEACTIVATE.call(this), function() {
                this.sandbox.emit('husky.tabs.header'+ this.options.instanceName +'.activate');
            }.bind(this));
        },

        /**
         * Shows the back icon
         */
        showBack: function() {
            this.sandbox.dom.css(this.$find('.' + constants.backClass), {
                'opacity': '1'
            });
            this.sandbox.dom.show(this.$find('.' + constants.backClass));
        },

        /**
         * Hides the back icon
         * @param animate {boolean} if true hidden with an animation
         */
        hideBack: function(animate) {
            if (animate === true) {
                this.sandbox.dom.css(this.$find('.' + constants.backClass), {
                    'opacity': '0'
                });
            } else {
                this.sandbox.dom.hide(this.$find('.' + constants.backClass));
            }
        },

        /**
         * Displays an array of objects as a breadcrumb
         * @param crumbs {array} crumbs Array of objects with a title and a link attribute
         */
        setBreadcrumb: function(crumbs) {
            if (!!crumbs && !!crumbs.length) {
                var $breadcrumb = this.sandbox.dom.createElement('<ul class="breadcrumb"/>');

                this.sandbox.util.foreach(crumbs, function(crumb) {
                    if (!!crumb.title) {
                        this.sandbox.dom.append($breadcrumb, this.sandbox.util.template(templates.breadcrumbItem)({
                            title: crumb.title,
                            link: (!!crumb.link) ? crumb.link : '#'
                        }));
                    }
                }.bind(this));

                this.sandbox.dom.html(this.$find('.' + constants.infoClass), $breadcrumb);
            }
        },

        /**
         * Bind Dom-events
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.$find('.' + constants.backClass), 'click', function() {
                this.sandbox.emit(BACK.call(this));
            }.bind(this));
        },

        /**
         * Calles the change states callbacks and passes it the arguments
         * //todo: make it cleaner!
         * @param type {string} "edit" or "add"
         * @param saved {boolean} false if the toolbar should represent a dirty-state
         * @param highlight {boolean} true to change with a highlight effect
         */
        changeToolbarState: function(type, saved, highlight) {
            if (typeof this.options.changeStateCallback === 'function') {
                this.options.changeStateCallback.call(this, saved, type, highlight);
            }
            if (typeof this.options.parentChangeStateCallback === 'function') {
                this.options.parentChangeStateCallback.call(this, saved, type, highlight);
            }
        },

        /**
         * Changes the title of the header
         * @param title {string} the new title
         */
        setTitle: function(title) {
            this.sandbox.dom.html(this.$find('h1'), title);
        }
    };
});
