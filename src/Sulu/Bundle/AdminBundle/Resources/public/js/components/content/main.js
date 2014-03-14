/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 *
 * options:
 *  - heading - string
 *
 *
 */

define([], function() {

    'use strict';

    var defaults = {
            heading: '',
            headingAddition: '',
            tabsData: null,
            instanceName: 'content',
            template: 'default',
            parentTemplate: null
        },

        templates = {
            skeleton: function() {
                return [
                    '<div id="edit-toolbar-container">',
                    '   <div id="page-functions"></div>',
                    '   <div id="edit-toolbar"></div>',
                    '</div>',
                    '<div class="content-tabs-content">',
                    '   <div class="headlines">',
                    '       <h1>' + this.options.heading + '</h1>',
                    '   </div>',
                    '   <div id="content-tabs" />',
                    '</div>'
                ].join('');
            }
        },

        initializeTabs = function() {
            if (this.options.tabsData && this.options.tabsData.items <= 1) {
                // TODO: do not show tabs if just one item available
            }

            // initialize tabs
            this.sandbox.start([
                {
                    name: 'tabs@husky',
                    options: {
                        el: '#content-tabs',
                        data: this.options.tabsData,
                        instanceName: this.options.instanceName,
                        forceReload: false,
                        forceSelect: true
                    }
                }
            ]);
        },

        initializeToolbar = function() {

            this.sandbox.start([
                {
                    name: 'page-functions@husky',
                    options: {
                        el: '#page-functions',
                        data: {
                            icon: 'chevron-left'
                        }
                    }
                },
                {
                    name: 'edit-toolbar@suluadmin',
                    options: {
                        el: '#edit-toolbar',
                        instanceName: this.options.instanceName,
                        forceReload: false,
                        template: this.options.template,
                        parentTemplate: this.options.parentTemplate
                    }
                }
            ]);
        },

        setTitle = function(title) {
            this.sandbox.dom.html(this.sandbox.dom.find('h1', this.$headlines), title);
        },

        setTitleAddition = function(title) {
            this.sandbox.dom.html(this.sandbox.dom.find('h6', this.$headlines), title);
        },

        prependHeadingAddition = function() {
            if (typeof this.options.headingAddition !== 'undefined' && this.options.headingAddition !== null) {
                this.sandbox.dom.addClass(this.$headlines,'compoundedHeadlines');
                this.sandbox.dom.prepend(this.$headlines,'<h6>'+this.options.headingAddition+'</div>');
            }
        };

    return {
        view: true,

        initialize: function() {

            // default
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // skeleton
            this.html(templates.skeleton.call(this));

            // headlines
            this.$headlines = this.$find('.headlines');
            prependHeadingAddition.call(this);

            // bind events (also initializes first component)
            this.bindCustomEvents();

            // initialize toolbar
            initializeToolbar.call(this);

            // initialize tabs
            initializeTabs.call(this);
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {
            var instanceName = (this.options.instanceName && this.options.instanceName !== '') ? this.options.instanceName + '.' : '';
            // load component on start
            this.sandbox.on('husky.tabs.' + instanceName + 'initialized', this.startTabComponent.bind(this));
            // load component after click
            this.sandbox.on('husky.tabs.' + instanceName + 'item.select', this.startTabComponent.bind(this));

            // back clicked
            this.sandbox.on('husky.page-functions.clicked', function() {
                this.sandbox.emit('sulu.edit-toolbar.back');
            }.bind(this));

            this.sandbox.on('sulu.content.set-title', setTitle.bind(this));

            this.sandbox.on('sulu.content.set-title-addition', setTitleAddition.bind(this));

        },

        /**
         * gets called when tabs either got initialized or when tab was clicked
         * @param item
         */
        startTabComponent: function(item) {

            if (!item) {
                item = this.options.tabsData.items[0];
            }

            if (!item.forceReload && item.action === this.action) {
                this.sandbox.logger.log('page already loaded; no reload required!');
                return;
            }

            // resets store to prevent duplicated models
            this.sandbox.mvc.Store.reset();

            this.sandbox.dom.html('#content-tabs-component', '');
            this.sandbox.dom.remove('#content-tabs-component');

            this.sandbox.dom.append(this.$el, '<div id="content-tabs-component"><span class="is-loading"/></div>');

            if (!!item && !!item.contentComponent) {
                var options = this.sandbox.util.extend(true, {}, this.options.contentOptions, {el: '#content-tabs-component', reset: true }, item.contentComponentOptions);
                // start component defined by
                this.sandbox.start([
                    {name: item.contentComponent, options: options}
                ]);
            }

            if (!!item) {
                this.action = item.action;
            }
        }
    };
});
