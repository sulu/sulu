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
        };

    return function(app) {

        app.components.before('initialize', function() {
            if (!!this.header) {
                var $content, $header, startHeader,
                    breadcrumb, toolbarTemplate, toolbarParentTemplate, tabsOptions, toolbarOptions, tabsFullControl;
                
                // if header is a function get the data from the return value of the function
                if (typeof this.header === 'function') {
                    this.header = this.header.call(this);
                }
                
                // insert the header-container
                $header = this.sandbox.dom.createElement('<div id="sulu-header-container"/>');
                this.sandbox.dom.append('body', $header);
                
                // append the container 
                $content = this.sandbox.dom.createElement('<div id="sulu-content-container"/>');
                this.html($content);

                /**
                 * Function for starting the header
                 * @param tabsData {array} Array of Data to pass on to the tabs component
                 */
                startHeader = function(tabsData) {

                    // set the variables for the header-component-options properties
                    breadcrumb = (!!this.header.breadcrumb) ? this.header.breadcrumb : null;
                    toolbarTemplate = (!!this.header.toolbar && !!this.header.toolbar.template) ? this.header.toolbar.template : 'default';
                    toolbarParentTemplate = (!!this.header.toolbar && !!this.header.toolbar.parentTemplate) ? this.header.toolbar.parentTemplate : null;
                    tabsOptions = (!!this.header.tabs && !!this.header.tabs.options) ? this.header.tabs.options : {};
                    toolbarOptions = (!!this.header.toolbar && !!this.header.toolbar.options) ? this.header.toolbar.options : {},
                    tabsFullControl = (!!this.header.tabs && typeof this.header.tabs.fullControl === 'boolean') ? this.header.tabs.fullControl : false;

                    this.sandbox.start([
                        {
                            name: 'header@suluadmin',
                            options: {
                                el: $header,
                                tabsData: tabsData,
                                heading: this.sandbox.translate(this.header.title),
                                breadcrumb: breadcrumb,
                                toolbarTemplate: toolbarTemplate,
                                toolbarParentTemplate: toolbarParentTemplate,
                                tabsComponentOptions: this.options,
                                contentEl: $content,
                                toolbarOptions: toolbarOptions,
                                tabsOptions: tabsOptions,
                                tabsFullControl: tabsFullControl
                            }
                        }
                    ]);
                };

                // if a url for the tabs is set load the data first, else start the header right away
                if (!!this.header.tabs && !!this.header.tabs.url) {
                    this.sandbox.util.load(this.header.tabs.url).then(function(data) {
                        var contentNavigation = JSON.parse(data);

                        // start header with tabs data passed
                        parseContentTabs.call(this, contentNavigation, this.options.id, startHeader.bind(this));
                    }.bind(this));
                } else {
                    startHeader.call(this, null);
                }
            }
        });
    };
});
