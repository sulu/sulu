(function () {

    'use strict';

    define([], function () {
        return {
            name: 'sulu-navigation',

            initialize: function (app) {
                app.sandbox.sulu = {
                    navigation: {

                        // Navigation

                        /**
                         * parses content tabs into the right format
                         *
                         * @param contentNavigation - the navigation JSON from server
                         * @param id - id of current element (used for url generation)
                         * @param callback - returns parsed navigation element
                         */
                        parseContentNavigation: function (contentNavigation, id, callback) {
                            var navigation, hasNew, hasEdit;

                            try {
                                // try parse
                                navigation = JSON.parse(contentNavigation);
                            } catch (e) {
                                // string already parsed
                                navigation = contentNavigation;
                            }

                            // get url from backbone
                            app.sandbox.emit('navigation.url', function (url) {
                                var items = [];
                                // check action
                                app.sandbox.util.foreach(navigation.items, function (content) {
                                    // check DisplayMode (new or edit) and show menu item or don't
                                    hasNew = content.contentDisplay.indexOf('new') >= 0;
                                    hasEdit = content.contentDisplay.indexOf('edit') >= 0;
                                    if ((!id && hasNew) || (id && hasEdit)) {
                                        content.action = this.parseActionUrl(content.action, url, id);
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
                                    app.sandbox.emit('navigation.item.column.show', {
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
                        parseActionUrl: function (actionString, url, id) {
                            // if first char is '/' use absolute url
                            if (actionString.substr(0, 1) === '/') {
                                return actionString.substr(1, actionString.length);
                            }
                            // TODO: FIXIT: ugly removal
                            if (id) {
                                var strSearch = 'edit:' + id;
                                url = url.substr(0, url.indexOf(strSearch) + strSearch.length);
                            }
                            return  url + '/' + actionString;
                        }
                    }
                }
            }
        }
    });
})();
