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
            instanceName: 'content',
            changeStateCallback: null,
            parentChangeStateCallback: null,
            tabsData: null
        },

        constants = {
            componentClass: 'sulu-header',
            infoClass: 'info',
            headlineClass: 'headline',
            backClass: 'back',
            toolbarClass: 'toolbar',
            tabsClass: 'tabs'
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
                        'position': 1,
                        disabled: true,
                        callback: function() {
                            this.sandbox.emit('sulu.edit-toolbar.save');
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
                                title: this.sandbox.translate('sulu.edit-toolbar.delete'),
                                callback: function() {
                                    this.sandbox.emit('sulu.edit-toolbar.delete');
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
                        '<span class="'+ constants.backClass +'"></span>',
                        '<h1 class="bright"><%= headline %></h1>',
                    '</div>',
                    '<div class="'+ constants.toolbarClass +'"></div>',
                '</div>'
            ].join('')
        },


        changeStateCallbacks = {
            default: function(saved, type, highlight) {
                if (!!saved) {
                    this.sandbox.emit('husky.toolbar.item.disable', 'save-button', !!highlight);
                } else {
                    this.sandbox.emit('husky.toolbar.item.enable', 'save-button', false);
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
            return 'sulu.header.' + this.options.instanceName + '.' + postfix;
        },

        /**
         * trigger after initialization has finished
         *
         * @event sulu.header.[INSTANCE_NAME].initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        };

    return {
        view: true,

        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.render();

            this.startToolbar();
            this.startTabs();

            // bind events
            this.bindCustomEvents();

            this.sandbox.emit(INITIALIZED.call(this));
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
                this.sandbox.dom.append(this.$el, '<div class="'+ constants.tabsClass +'"></div>');
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
                        instanceName: this.options.instanceName,
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
                        pageFunction: this.options.pageFunction,
                        data: this.options.toolbarTemplate,
                        skin: 'blueish'
                    }
                }
            ]);
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {

            var instanceName = (this.options.instanceName && this.options.instanceName !== '') ? this.options.instanceName + '.' : '';

            // changes the saved state of the toolbar
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'state.change', function(type, saved, highlight){
                this.changeToolbarState(type, saved, highlight);
            }.bind(this));

            // change the title
            this.sandbox.on('sulu.content.set-title', this.setTitle.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'items.set', function(id, items) {
                this.sandbox.emit('husky.toolbar.items.set', id, items);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'button.set', function(id, object) {
                this.sandbox.emit('husky.toolbar.button.set', id, object);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'item.loading', function(id) {
                this.sandbox.emit('husky.toolbar.item.loading', id);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'item.change', function(id, name) {
                this.sandbox.emit('husky.toolbar.item.change', id, name);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'item.show', function(id, name) {
                this.sandbox.emit('husky.toolbar.item.show', id, name);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'item.enable', function(id, highlight) {
                this.sandbox.emit('husky.toolbar.item.enable', id, highlight);
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
