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
     * @param {Function} callback Receives parsed navigation items.
     */
    var parseContentTabs = function(contentNavigation, id, callback) {
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

            // if callback is set, call it
            if (!!callback && typeof callback === 'function') {
                callback(items);
            } else { // else emit event "navigation.item.column.show"
                this.sandbox.emit('navigation.item.column.show', {
                    data: items
                });
            }
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
                var strSearch = 'edit:' + id;
                url = url.substr(0, url.indexOf(strSearch) + strSearch.length);
            }

            return url + '/' + actionString;
        },

        /**
         * Handles the components which are marked with a view property.
         *
         * @param {Object} view The view property of a component.
         */
        handleViewMarker = function(view) {
            this.sandbox.emit('sulu.view.initialize', view);
        },

        /**
         * Handles layout marked components
         *
         * @param {Object|Boolean|Function} layout The layout object or true for default values.
         *        If a function, gets called and takes the return value to work with.
         * @param {Boolean} [layout.changeNothing] If true the layout as it is won't be touched.
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
            if (!layout.changeNothing) {
                layout = this.sandbox.util.extend(true, {}, defaults.layout, layout);
                handleLayoutNavigation.call(this, layout.navigation);
                handleLayoutContent.call(this, layout.content);
                handleLayoutSidebar.call(this, layout.sidebar);
            }
        },

        /**
         * Handles the navigation part of the layout object.
         *
         * @param {Object} navigation The navigation config object.
         */
        handleLayoutNavigation = function(navigation) {
            if (navigation.collapsed === true) {
                this.sandbox.emit('husky.navigation.collapse', true);
            } else {
                this.sandbox.emit('husky.navigation.uncollapse');
            }

            if (navigation.hidden === true) {
                this.sandbox.emit('husky.navigation.hide');
            } else {
                this.sandbox.emit('husky.navigation.show');
            }
        },

        /**
         * Handles the content part of the layout object.
         *
         * @param {Object} content The content config object.
         */
        handleLayoutContent = function(content) {
            var width = content.width,
                leftSpace = !!content.leftSpace,
                rightSpace = !!content.rightSpace,
                topSpace = !!content.topSpace;
            this.sandbox.emit('sulu.app.change-width', width);
            this.sandbox.emit('sulu.app.change-spacing', leftSpace, rightSpace, topSpace);
            this.sandbox.emit('sulu.app.toggle-shrinker', false);
        },

        /**
         * Handles the sidebar part of the layout object.
         *
         * @param {Object} sidebar The sidebar config object. If false sidebar gets hidden.
         */
        handleLayoutSidebar = function(sidebar) {
            if (!!sidebar && !!sidebar.url) {
                this.sandbox.emit('sulu.sidebar.set-widget', sidebar.url);
            } else {
                this.sandbox.emit('sulu.sidebar.empty');
            }

            if (!!sidebar) {
                var width = sidebar.width || 'max';
                this.sandbox.emit('sulu.sidebar.change-width', width);
            } else {
                this.sandbox.emit('sulu.sidebar.hide');
            }

            this.sandbox.emit('sulu.sidebar.reset-classes');

            if (!!sidebar && !!sidebar.cssClasses) {
                this.sandbox.emit('sulu.sidebar.add-classes', sidebar.cssClasses);
            }
        },

        /**
         * Handles the the components which are marked with a header property.
         * Generates defaults, handles tabs data if tabs are configured, starts the header-component.
         *
         * @param {Object|Function} header The header property found in the started component.
         *        If it's function it must return an object.
         * @param {String} [header.title] Title in the header.
         * @param {Array} [header.breadcrumb] Breadcrumb object which gets passed to the header-component.
         * @param {Boolean} [header.noBack] If true the back icon won't be displayed.
         * @param {Object} [header.tabs] Object that contains configurations for the tabs.
         *        If not set no tabs will be displayed.
         * @param {String} [header.tabs.url] Url to fetch tabs related data from.
         * @param {Boolean} [header.tabs.fullControl] If true the header just displays the tabs,
         *        but doesn't start the content-component.
         * @param {Object} [header.tabs.options] Options to pass to the tabs-component.
         *        Often used together with the fullControl-option.
         * @param {Object} [header.toolbar] Object that contains configurations for the toolbar.
         *        If not set no toolbar will be displayed.
         * @param {Array|String} [header.toolbar.template] Array of toolbar items to pass to the header component,
         *        can also be a string representing a template (e.g. 'default')
         * @param {Array|String} [header.toolbar.parentTemplate] Same as toolbar.template,
         *        gets merged with toolbar template.
         * @param {Object} [header.toolbar.options] Object with options for the toolbar component.
         * @param {Object|Boolean} [header.toolbar.languageChanger] Object with url and callback to pass to the header.
         *        If true the default language changer will be rendered. Default is true.
         *
         * @example
         *
         *      header: {
         *          tabs: {
         *              url: 'url/to/tabsData',
         *          },
         *          title: 'My title',
         *          breadcrumb: [{title: 'Crumb 1', link: 'contacts/contact'}, {title: 'Crumb 2', event: 'sulu.navigation.clicked}],
         *          toolbar {
         *              languageChanger: true
         *              template: 'default'
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
            var $content, changeHeader, options;

            // insert the content-container
            $content = this.sandbox.dom.createElement('<div id="sulu-content-container"/>');
            this.html($content);

            /**
             * Function for starting the header
             * @param {Array} tabsData Array of data to pass on to the tabs component.
             * @private
             */
            changeHeader = function(tabsData) {
                // set the variables for the header-component-options properties
                var toolbarLanguageChanger = true;

                if (!!header.toolbar) {
                    toolbarLanguageChanger = !!header.toolbar.languageChanger ? header.toolbar.languageChanger : false;
                }

                options = {
                    tabsData: tabsData,
                    heading: this.sandbox.translate(header.title),
                    breadcrumb: (!!header.breadcrumb) ? header.breadcrumb : null,
                    toolbarTemplate: (!!header.toolbar && !!header.toolbar.template) ?
                        header.toolbar.template : 'default',
                    toolbarParentTemplate: (!!header.toolbar && !!header.toolbar.parentTemplate) ?
                        header.toolbar.parentTemplate : null,
                    contentComponentOptions: this.options,
                    contentEl: $content,
                    toolbarOptions: (!!header.toolbar && !!header.toolbar.options) ? header.toolbar.options : {},
                    tabsOptions: (!!header.tabs && !!header.tabs.options) ? header.tabs.options : {},
                    tabsFullControl: (!!header.tabs && typeof header.tabs.fullControl === 'boolean') ?
                        header.tabs.fullControl : false,
                    toolbarDisabled: (typeof header.toolbar === 'undefined'),
                    toolbarLanguageChanger: toolbarLanguageChanger,
                    noBack: (typeof header.noBack !== 'undefined') ? header.noBack : false,
                    titleColor: (!!header.titleColor) ? header.titleColor : null
                };

                if (header.tabs === false) {
                    options.tabsOptions = false;
                }

                this.sandbox.emit('sulu.header.change', options);
            }.bind(this);

            // if a url for the tabs is set load the data first, else start the header with no tabs
            if (!!header.tabs && !!header.tabs.url) {
                this.sandbox.util.load(header.tabs.url).then(function(data) {
                    // start header with tabs data passed
                    parseContentTabs.call(this, data, this.options.id, changeHeader);
                }.bind(this));
            } else {
                changeHeader(null);
            }
        };

    return function(app) {
        /**
         * Gets executed every time BEFORE a component gets initialized.
         * Checks various properties (header, view, layout) of an component
         * and executes the related handler.
         */
        app.components.before('initialize', function() {
            if (!!this.header) {
                handleHeaderMarker.call(this, this.header);
            }

            if (!!this.view) {
                handleViewMarker.call(this, this.view);

                // if a view has no layout specified use the default one
                if (!this.layout) {
                    handleLayoutMarker.call(this, {});
                }
            }

            if (!!this.layout) {
                handleLayoutMarker.call(this, this.layout);
            }
        });
    };
});
