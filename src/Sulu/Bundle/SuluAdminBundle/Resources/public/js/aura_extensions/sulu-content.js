define([], function() {

    'use strict';

    /**
     * parses content tabs into the right format
     *
     * @param contentNavigation - the navigation JSON from server
     * @param id - id of current element (used for url generation)
     * @param callback - returns parsed navigation element
     */
     var parseContentTabs = function(contentNavigation, id, callback) {
            var navigation, hasNew, hasEdit;

            try {
                // try parse
                navigation = JSON.parse(contentNavigation);
            } catch (e) {
                // string already parsed
                navigation = contentNavigation;
            }

            // get url from backbone
            this.sandbox.emit('navigation.url', function(url) {
                var items = [];
                // check action
                this.sandbox.util.foreach(navigation.items, function(content) {
                    // check DisplayMode (new or edit) and show menu item or don't
                    hasNew = content.contentDisplay.indexOf('new') >= 0;
                    hasEdit = content.contentDisplay.indexOf('edit') >= 0;
                    if ((!id && hasNew) || (id && hasEdit)) {
                        content.action = parseActionUrl.call(this, content.action, url, id);
                        if (content.action === url) {
                            content.selected = true;
                        }
                        items.push(content);
                    }
                }.bind(this));
                navigation.url = url;
                navigation.items = items;

                // if callback isset call it
                if (!!callback && typeof callback === 'function') {
                    callback(navigation);
                } else { // else emit event "navigation.item.column.show"
                    this.sandbox.emit('navigation.item.column.show', {
                        data: navigation
                    });
                }
            }.bind(this));
        },

        /**
         * parses an url into
         * @param actionString
         * @param url
         * @param id
         * @returns {string}
         */
        parseActionUrl = function(actionString, url, id) {
            // if first char is '/' use absolute url
            if (actionString.substr(0, 1) === '/') {
                return actionString.substr(1, actionString.length);
            }
            // FIXME: ugly removal
            if (id) {
                var strSearch = 'edit:' + id;
                url = url.substr(0, url.indexOf(strSearch) + strSearch.length);
            }
            return  url + '/' + actionString;
        },

        /**
         * Handles the components which are marked with a view property
         * @param {boolean} view The property found
         */
        handleViewMarked = function(view) {
            this.sandbox.emit('sulu.view.initialize', view);
        },

        /**
         * Handles the components which are marked with a fullSize property
         * @param fullSize
         */
        handleFullSizeMarked = function(fullSize) {
            if (fullSize.width === true || fullSize.height === true) {
                this.sandbox.emit('sulu.app.full-size', !!fullSize.width, !!fullSize.height, !!fullSize.keepPaddings);
            }
        },

        /**
         * Handles the the components which are marked with a header property.
         * Generates defaults, handles tabs data if tabs are configured, starts the header-component
         *
         * @param {Object|Function} [header] the header property found in the started component. If it's function it must return an object
         * @param {String} [header.title] title in the header
         * @param {Array} [header.breadcrumb] breadcrumb object which gets passed to the header-component
         * @param {Object} [header.toolbar] object that contains configurations for the toolbar - if not set no toolbar will be displayed
         * @param {Object} [header.tabs] object that contains configurations for the tabs - if not set no tabs will be displayed
         * @param {Boolean} [header.noBack] If true the back icon won't be displayed
         *
         * @param {Array|String} [header.toolbar.template] array of toolbar items to pass to the header component, can also be a string representing a template (e.g. 'default')
         * @param {Array|String} [header.toolbar.parentTemplate] same as toolbar.template, gets merged with toolbar template
         * @param {Object} [header.toolbar.options] object with options for the toolbar component
         * @param {Object|Boolean} [header.toolbar.languageChanger] Object with url and callback to pass to the header. If true system language changer will be rendered. Default is true
         *
         * @param {String} [header.tabs.url] Url to fetch tabs related data from
         * @param {Boolean} [header.tabs.fullControl] If true the header just displayes the tabs, but doesn't start the content-component
         * @param {Object} [header.tabs.options] options to pass to the tabs-component. Often used together with the fullControl-option
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
        handleHeaderMarked = function(header) {
            var $content, $header, startHeader,
                breadcrumb, toolbarTemplate, toolbarParentTemplate, tabsOptions, toolbarOptions, tabsFullControl,
                toolbarDisabled, toolbarLanguageChanger, noBack, squeezed, titleColor;

            // if header is a function get the data from the return value of the function
            if (typeof header === 'function') {
                header = header.call(this);
            }

            // insert the header-container
            $header = this.sandbox.dom.createElement('<div id="sulu-header-container"/>');
            this.sandbox.dom.append('body', $header);

            // insert the content-container
            $content = this.sandbox.dom.createElement('<div id="sulu-content-container"/>');
            this.html($content);

            /**
             * Function for starting the header
             * @param tabsData {array} Array of Data to pass on to the tabs component
             * @private
             */
            startHeader = function(tabsData) {

                // set the variables for the header-component-options properties
                breadcrumb = (!!header.breadcrumb) ? header.breadcrumb : null;
                toolbarDisabled = (typeof header.toolbar === 'undefined') ? true : false;
                toolbarTemplate = (!!header.toolbar && !!header.toolbar.template) ? header.toolbar.template : 'default';
                toolbarParentTemplate = (!!header.toolbar && !!header.toolbar.parentTemplate) ? header.toolbar.parentTemplate : null;
                tabsOptions = (!!header.tabs && !!header.tabs.options) ? header.tabs.options : {};
                toolbarOptions = (!!header.toolbar && !!header.toolbar.options) ? header.toolbar.options : {},
                tabsFullControl = (!!header.tabs && typeof header.tabs.fullControl === 'boolean') ? header.tabs.fullControl : false;
                toolbarLanguageChanger = (!!header.toolbar && !!header.toolbar.languageChanger) ? header.toolbar.languageChanger : true;
                noBack = (typeof header.noBack !== 'undefined') ? header.noBack : false;
                squeezed = (!!this.fullSize && this.fullSize.width === true && this.fullSize.keepPaddings !== true) ? true : false;
                titleColor = (!!header.titleColor) ? header.titleColor : null;

                this.sandbox.start([
                    {
                        name: 'header@suluadmin',
                        options: {
                            el: $header,
                            tabsData: tabsData,
                            heading: this.sandbox.translate(header.title),
                            breadcrumb: breadcrumb,
                            toolbarTemplate: toolbarTemplate,
                            toolbarParentTemplate: toolbarParentTemplate,
                            contentComponentOptions: this.options,
                            contentEl: $content,
                            toolbarOptions: toolbarOptions,
                            tabsOptions: tabsOptions,
                            tabsFullControl: tabsFullControl,
                            toolbarDisabled: toolbarDisabled,
                            toolbarLanguageChanger: toolbarLanguageChanger,
                            noBack: noBack,
                            squeezed: squeezed,
                            titleColor: titleColor
                        }
                    }
                ]);
            };

            // if a url for the tabs is set load the data first, else start the header with no tabs
            if (!!header.tabs && !!header.tabs.url) {
                this.sandbox.util.load(header.tabs.url).then(function(data) {
                    var contentNavigation = JSON.parse(data);

                    // start header with tabs data passed
                    parseContentTabs.call(this, contentNavigation, this.options.id, startHeader.bind(this));
                }.bind(this));
            } else {
                startHeader.call(this, null);
            }
        };

    return function(app) {

        /**
         * Gets executed every time BEFORE a component gets initialized
         * looks in the component for various properties and executes a handler
         * that goes with the found matched property
         */
        app.components.before('initialize', function() {
            if (!!this.header) {
                handleHeaderMarked.call(this, this.header);
            }

            if (!!this.view) {
                handleViewMarked.call(this, this.view);
            }

            if (!!this.fullSize) {
                handleFullSizeMarked.call(this, this.fullSize);
            }
        });
    };
});
