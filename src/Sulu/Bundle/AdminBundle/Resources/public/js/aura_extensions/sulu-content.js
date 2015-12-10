/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var defaults = {
        layout: {
            navigation: {
                collapsed: false,
                hidden: false
            },
            content: {
                width: 'fixed',
                leftSpace: true,
                rightSpace: true,
                topSpace: true
            },
            sidebar: false
        }
    };

    /**
     * Parse content tabs and convert into the correct format.
     *
     * @param {Object|String} contentNavigation The content navigation JSON-String returned from the server.
     * @param {String} id ID of the current element (used for url generation).
     * @return {Object} the parsed tabs-navigation items.
     */
    var parseContentTabs = function(contentNavigation, id) {
            var navigation, hasNew, hasEdit,
                url = this.sandbox.mvc.history.fragment;

            try {
                // try parse
                navigation = JSON.parse(contentNavigation);
            } catch (e) {
                // string already parsed
                navigation = contentNavigation;
            }

            var items = [];
            // check action
            this.sandbox.util.foreach(navigation, function(content) {
                // check DisplayMode (new or edit) and show menu item or don't
                hasNew = content.display.indexOf('new') >= 0;
                hasEdit = content.display.indexOf('edit') >= 0;

                if ((!id && hasNew) || (id && hasEdit)) {
                    content.action = parseActionUrl(content.action, url, id);

                    if (content.action === url) {
                        content.selected = true;
                    }

                    items.push(content);
                }
            }.bind(this));

            return items;
        },

        /**
         * Parse content tab action url.
         *
         * @param {String} actionString
         * @param {String} url
         * @param {String|Number} id
         *
         * @return {String}
         */
        parseActionUrl = function(actionString, url, id) {
            // if first char is '/' return action url and remove leading '/'
            if (actionString.substr(0, 1) === '/') {
                return actionString.substr(1, actionString.length);
            }

            if (!!id) {
                var regex = new RegExp("\\w*:" + id),
                    result = regex.exec(url),
                    strSearch = result[0];

                url = url.substr(0, url.indexOf(strSearch) + strSearch.length);
            }

            return url + '/' + actionString;
        },

        /**
         * Handles layout marked components
         *
         * @param {Object|Boolean|Function} layout The layout object or true for default values.
         *        If a function, gets called and takes the return value to work with.
         * @param {Boolean} [layout.extendExisting] Iff true, the existing layout stays as it is and only the spcified
         *        properties cause effects
         * @param {Object} [layout.navigation] The object which holds the layout configuration for the navigation.
         * @param {Boolean} [layout.navigation.collapsed] If true navigation is collapsed.
         * @param {Boolean} [layout.navigation.hidden] If true navigation gets hidden.
         * @param {Object} [layout.content] The object which holds the layout configuration for the content.
         * @param {Boolean} [layout.content.shrinkable] If true an icon for shrinking the content-column
         *        will be displayed.
         * @param {String} [layout.content.width] The width-type, 'fixed' or 'max', of the content-column.
         * @param {Boolean} [layout.content.leftSpace] If false content has no spacing on the left.
         * @param {Boolean} [layout.content.rightSpace] If false content has no spacing on the right.
         * @param {Boolean} [layout.content.topSpace] If false content has no spacing on top.
         * @param {Object|Boolean} [layout.sidebar] The object which holds the layout configuration for the sidebar.
         *        If false no sidebar will be displayed.
         * @param {String} [layout.sidebar.width] The width-type, 'fixed' or 'max', of the sidebar-column.
         *
         * @example
         *
         *      layout: {
         *          navigation: {
         *              collapsed: true
         *          },
         *          content: {
         *              width: 'fixed',
         *              topSpace: false,
         *              leftSpace: false,
         *              rightSpace: true
         *          },
         *          sidebar: {
         *              width: 'max',
         *              darkBorder: true,
         *              url: '/admin/widget-groups/my-widget-group'
         *          }
         *      }
         */
        handleLayoutMarker = function(layout) {
            if (typeof layout === 'function') {
                layout = layout.call(this);
            }
            if (!layout.extendExisting) {
                layout = this.sandbox.util.extend(true, {}, defaults.layout, layout);
            }
            if (typeof layout.navigation !== 'undefined') {
                handleLayoutNavigation.call(this, layout.navigation, !layout.extendExisting);
            }
            if (typeof layout.content !== 'undefined') {
                handleLayoutContent.call(this, layout.content, !layout.extendExisting);
            }
            if (typeof layout.sidebar !== 'undefined') {
                handleLayoutSidebar.call(this, layout.sidebar, !layout.extendExisting);
            }
        },

        /**
         * Handles the navigation part of the layout object.
         *
         * @param {Object} navigation The navigation config object.
         * @param {Boolean} applyDefaults Iff true default actions will be executed
         */
        handleLayoutNavigation = function(navigation, applyDefaults) {
            if (navigation.collapsed === true) {
                this.sandbox.emit('husky.navigation.collapse', true);
            } else if (applyDefaults === true) {
                this.sandbox.emit('husky.navigation.uncollapse');
            }

            if (navigation.hidden === true) {
                this.sandbox.emit('husky.navigation.hide');
            } else if (applyDefaults === true) {
                this.sandbox.emit('husky.navigation.show');
            }
        },

        /**
         * Handles the content part of the layout object.
         *
         * @param {Object} content The content config object.
         * @param {Boolean} applyDefaults Iff true default actions will be executed
         */
        handleLayoutContent = function(content, applyDefaults) {
            var width = content.width,
                leftSpace = (applyDefaults) ? !!content.leftSpace : content.leftSpace,
                rightSpace = (applyDefaults) ? !!content.rightSpace : content.rightSpace,
                topSpace = (applyDefaults) ? !!content.topSpace : content.topSpace;
            if (applyDefaults === true || !!width) {
                this.sandbox.emit('sulu.app.change-width', width, applyDefaults);
            }
            this.sandbox.emit('sulu.app.change-spacing', leftSpace, rightSpace, topSpace);
        },

        /**
         * Handles the sidebar part of the layout object.
         *
         * @param {Object} sidebar The sidebar config object. If false sidebar gets hidden.
         * @param {Boolean} applyDefaults Iff true default actions will be executed
         */
        handleLayoutSidebar = function(sidebar, applyDefaults) {
            if (!!sidebar && !!sidebar.url) {
                this.sandbox.emit('sulu.sidebar.set-widget', sidebar.url);
            } else if (applyDefaults === true) {
                this.sandbox.emit('sulu.sidebar.empty');
            }

            if (!!sidebar) {
                var width = sidebar.width || 'max';
                this.sandbox.emit('sulu.sidebar.change-width', width);
            } else if (applyDefaults === true) {
                this.sandbox.emit('sulu.sidebar.hide');
            }

            if (applyDefaults === true) {
                this.sandbox.emit('sulu.sidebar.reset-classes');
            }

            if (!!sidebar && !!sidebar.cssClasses) {
                this.sandbox.emit('sulu.sidebar.add-classes', sidebar.cssClasses);
            }
        },

        /**
         * Handles the components which are marked with a header property.
         * Generates defaults, handles tabs data if tabs are configured, starts the header-component.
         *
         * @param {Object|Function} header The header property found in the started component.
         *        If it's function it must return an object.
         * @param {Boolean} [header.noBack] If true the back icon won't be displayed.
         * @param {Object} [header.tabs] Object that contains configurations for the tabs.
         *        If not set no tabs will be displayed.
         * @param {String} [header.tabs.url] Url to fetch tabs related data from.
         * @param {Object} [header.tabs.data] tabs-data to pass to the header if no tabs-url is specified
         * @param {Object} [header.tabs.componentOptions] options to pass to the husky-tab-component
         * @param {Object} [header.tabs.options] options that get passed to all tab-components
         * @param {String|Object} [header.tabs.container] the container to render the tabs-content in.
         *        If not set the content gets inserted directly into the current component
         * @param {Object} [header.toolbar] Object that contains configurations for the toolbar.
         *        If not set no toolbar will be displayed.
         * @param {Array} [header.toolbar.buttons] array of arguments to pass to sulu.buttons.get to recieve the toolbar buttons
         * @param {Object} [header.toolbar.options] Object with options for the toolbar component.
         * @param {Object|Boolean} [header.toolbar.languageChanger] Object with url and callback to pass to the header.
         *        If true the default language changer will be rendered. Default is true.
         * @param {String} [header.title] Title to inject inject into the tabs or (if tabs not exist) into the current component
         * @param {String} [header.underline] Title underlined or not
         *
         * @example
         *
         *      header: {
         *          tabs: {
         *              url: 'url/to/tabsData',
         *              container: '#my-container-selector',
         *              options: {
         *                  myOptions: 'toPassToAllTabs'
         *              }
         *          },
         *          toolbar: {
         *              languageChanger: true
         *              buttons: {
         *                  save: {},
         *                  settings: {
         *                      options: {
         *                          dropdownItems: {
         *                              delete: {}
         *                          }
         *                      }
         *                  }
         *              }
         *          }
         *      }
         *
         */
        handleHeaderMarker = function(header) {
            // if the header is a function get the return value (could be a promise)
            if (typeof header === 'function') {
                header = header.call(this);
            }

            // check if header is now a promise
            if (!!header.then) {
                header.then(function(data) {
                    handleHeader.call(this, data);
                }.bind(this));
            } else {
                handleHeader.call(this, header);
            }
        },

        /**
         * Handles the header marker of a component.
         *
         * @param {Object} header The header config object.
         */
        handleHeader = function(header) {
            if (!header) {
                return false;
            }

            getTabsData.call(this, header).then(function(tabsData) {
                var $container = this.sandbox.dom.createElement('<div class="sulu-header"/>'),
                    $header = $('.sulu-header');

                if (!!$header.length) {
                    Husky.stop('.sulu-header');
                    $header.remove();
                }
                this.sandbox.dom.prepend('.content-column', $container);

                this.sandbox.start([{
                    name: 'header@suluadmin',
                    options: {
                        el: $container,
                        noBack: (typeof header.noBack !== 'undefined') ? header.noBack : false,
                        title: (!!header.title) ? header.title : false,
                        underline: header.hasOwnProperty('underline') ? header.underline : true,

                        toolbarOptions: (!!header.toolbar && !!header.toolbar.options) ? header.toolbar.options : {},
                        toolbarLanguageChanger: (!!header.toolbar && !!header.toolbar.languageChanger) ?
                            header.toolbar.languageChanger : false,
                        toolbarDisabled: !header.toolbar,
                        toolbarButtons: (!!header.toolbar && !!header.toolbar.buttons) ? header.toolbar.buttons : [],

                        tabsData: tabsData,
                        tabsContainer: (!!header.tabs && !!header.tabs.container) ? header.tabs.container : this.options.el,
                        tabsParentOption: this.options,
                        tabsOption: (!!header.tabs && !!header.tabs.options) ? header.tabs.options : {},
                        tabsComponentOptions: (!!header.tabs && !!header.tabs.componentOptions) ? header.tabs.componentOptions : {}
                    }
                }]);

            }.bind(this));
        },

        /**
         * Loades and prepares the tabs-data for a header object
         * @param header {Object} the header object
         * @returns {Deferred} a deferred-object with a then method
         */
        getTabsData = function(header) {
            var loaded = this.sandbox.data.deferred();
            if (!header.tabs || !header.tabs.url) {
                loaded.resolve((!!header.tabs) ? header.tabs.data : null);
                return loaded;
            }
            this.sandbox.util.load(header.tabs.url).then(function(data) {
                var tabsData = parseContentTabs.call(this, data, this.options.id);
                loaded.resolve(tabsData);
            }.bind(this));
            return loaded;
        },

        /**
         * Executes handlers before the load-component-data-hook
         */
        executeBeforeDataHandler = function() {
            //TODO this.view is deprecated for resetting the layout. use 'layout: {}' instead
            if (!!this.view && !this.layout) {
                // if a view has no layout specified use the default one
                handleLayoutMarker.call(this, {});
            }
            if (!!this.layout) {
                handleLayoutMarker.call(this, this.layout);
            }
        },

        /**
         * Executes handlers after the load-component-data-hook
         */
        executeAfterDataHandler = function() {
            var headerStarted = $.Deferred();
            if (!!this.header) {
                handleHeaderMarker.call(this, this.header);
                this.sandbox.once('sulu.header.initialized', function() {
                    headerStarted.resolve();
                }.bind(this));
            } else {
                headerStarted.resolve();
            }
            return headerStarted;
        };

    return function(app) {
        /**
         * Gets executed every time BEFORE a component gets initialized.
         * Loads data if needed and start executing component handlers
         */
        app.components.before('initialize', function() {
            //load view data before rendering tabs
            var dataLoaded = $.Deferred(),
                afterData = $.Deferred(),
                resolveData = function(data) {
                    if (!!data) {
                        this.data = data;
                    }
                    executeAfterDataHandler.call(this).then(function() {
                        afterData.resolve();
                    }.bind(this));
                };

            executeBeforeDataHandler.call(this);

            if (!!this.loadComponentData && typeof this.loadComponentData === 'function') {
                dataLoaded = this.loadComponentData.call(this);
            } else {
                dataLoaded.resolve();
            }

            if (!!dataLoaded.then) {
                dataLoaded.then(resolveData.bind(this));
            } else {
                resolveData.call(this, dataLoaded);
            }

            return $.when(dataLoaded, afterData);
        });
    };
});
