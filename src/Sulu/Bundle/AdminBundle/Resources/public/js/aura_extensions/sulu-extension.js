(function() {

    'use strict';

    define([], {

        initialize: function(app) {
            /*********
             * Sulu namespace
             *********/
            app.sandbox.sulu = {};

            /**
             * Userproperties
             */
            app.sandbox.sulu.user = app.sandbox.util.extend(false, {}, SULU.user);

            /*********
             * Locales
             *********/
            app.sandbox.sulu.locales = SULU.locales;

            /*********
             * user
             *********/
            app.sandbox.sulu.user = app.sandbox.util.extend(true, {}, SULU.user);

            /*********
             * USER SETTINGS
             *********/

                // load settings
            app.sandbox.sulu.userSettings = app.sandbox.util.extend(true, {}, SULU.user.settings);
            var getObjectIds = function(array, swap) {
                    var temp = swap ? {} : [], i;
                    for (i = 0; i < array.length; i++) {
                        if (swap) {
                            temp[array[i].name] = i;
                        } else {
                            temp.push(array[i].name);
                        }
                    }
                    return temp;
                },

                /**
                 * triggered when data was loaded from server
                 * @event sulu.fields.loaded
                 * @params {Object} data Fields data
                 */
                SULU_FIELDS_LOADED = 'sulu.fields.loaded';

            /**
             * Object to store that by views
             * @type {Object}
             */
            app.sandbox.sulu.viewStates = {};

            /**
             * Stores the info that a node got deleted, so other views are
             * able to display a success label
             */
            app.sandbox.sulu.unlockDeleteSuccessLabel = function() {
                app.sandbox.sulu.viewStates.nodeDeleted = true;
            };

            /**
             * Actually shows a success label if delete description and title. But only
             * if a node actually got deleted beforehand
             * @param description {String} the description of the label
             */
            app.sandbox.sulu.triggerDeleteSuccessLabel = function(description) {
                if (app.sandbox.sulu.viewStates.nodeDeleted === true) {
                    description = description || 'labels.success.content-deleted-desc';
                    app.sandbox.emit('sulu.labels.success.show', description, 'labels.success');
                    delete app.sandbox.sulu.viewStates.nodeDeleted;
                }
            };

            /**
             * load user settings
             * @param key
             * @param url Where to get data from, if not already available
             * @param callback Function to return settings value
             */
            app.sandbox.sulu.loadUserSetting = function(key, url, callback) {
                if (!!app.sandbox.sulu.userSettings[key]) {
                    callback(app.sandbox.sulu.userSettings[key]);
                } else {
                    // get from server
                    app.sandbox.util.load(url)
                        .then(function(data) {
                            app.sandbox.sulu.userSettings[key] = data;
                            callback(data);
                        }.bind(this))
                        .fail(function(data) {
                            app.sandbox.logger.log('data could not be loaded:', data);
                        }.bind(this));
                }
            };

            /**
             * loads an url and matches it against user settings
             * @param key Defines which setting to compare with
             * @param attributesArray Defines which Attributes should NOT be taken from user-settings and from fields API instead
             * @param url Where
             * @param callback
             */
            app.sandbox.sulu.loadUrlAndMergeWithSetting = function(key, attributesArray, url, callback) {
                this.sandbox.util.load(url)
                    .then(function(data) {
                        var userFields = app.sandbox.sulu.getUserSetting(key),
                            serverFields = data,
                            settingsArray = [],
                            newSetting,
                            serverindex, serverindexLeft, userKeys, serverKeysLeft, serverKeys, serverKeysSwap;

                        if (!!userFields) {
                            serverKeysLeft = getObjectIds.call(this, serverFields);
                            serverKeys = getObjectIds.call(this, serverFields);
                            serverKeysSwap = getObjectIds.call(this, serverFields, true);
                            userKeys = getObjectIds.call(this, userFields);

                            // keep all user settings if they still exist
                            this.sandbox.util.foreach(userKeys, function(key, index) {
                                // get index of setting from server fields
                                serverindex = serverKeys.indexOf(key);
                                if (serverindex >= 0) {

                                    newSetting = serverFields[serverindex];
                                    for (var attrname in userFields[index]) {
                                        if (attributesArray.indexOf(attrname) < 0) {
                                            newSetting[attrname] = userFields[index][attrname];
                                        }
                                    }
                                    settingsArray.push(newSetting);

                                    // remove from server keys
                                    serverindexLeft = serverKeysLeft.indexOf(key);
                                    serverKeysLeft.splice(serverindexLeft, 1);
                                }
                            }.bind(this));
                            // add new ones
                            this.sandbox.util.foreach(serverKeysLeft, function(key) {
                                settingsArray.push(serverFields[serverKeysSwap[key]]);
                            }.bind(this));
                        } else {
                            settingsArray = serverFields;
                        }

                        app.sandbox.sulu.userSettings[key] = settingsArray;
                        app.sandbox.emit(SULU_FIELDS_LOADED, data);
                        callback(settingsArray);

                    }.bind(this));
            };

            /**
             * returns settings for a specified key
             * @param key
             * @returns mixed
             */
            app.sandbox.sulu.getUserSetting = function(key) {
                return (typeof app.sandbox.sulu.userSettings[key] !== 'undefined') ? app.sandbox.sulu.userSettings[key] : null;
            };

            /**
             * saves data locally and to database
             * @param key
             * @param value
             * @param url Defines where to save data to
             */
            app.sandbox.sulu.saveUserSetting = function(key, value, url) {
                app.sandbox.sulu.userSettings[key] = value;

                if (!url) {
                    url = '/admin/api/users/' + SULU.user.id + '/settings/' + key;
                }

                var data = {
                    key: key,
                    value: value
                };
                // save to server
                app.sandbox.util.ajax({
                    type: 'PUT',
                    url: url,
                    data: data,
                    processData: true,
                    success: function(response) {

                    }.bind(this),
                    error: function(response) {
                        app.sandbox.logger.log("error", response);
                    }
                });
            };

            /**
             * Shows a standard delete warning dialog
             * @param callback {Function} callback function to execute after dialog got closed. The callback gets always
             *                            executed (with true or false as first argument, whether the dialog got
             *                            confirmed or not)
             * @param title {String} custom title of the dialog
             * @param description {String} custom description of the dialog
             */
            app.sandbox.sulu.showDeleteDialog = function(callback, title, description) {
                // check if callback is a function
                if (!!callback && typeof(callback) !== 'function') {
                    throw 'callback is not a function';
                }
                title = (typeof title === 'string') ? title : 'sulu.overlay.be-careful';
                description = (typeof description === 'string') ? description : 'sulu.overlay.delete-desc';

                // show warning dialog
                app.sandbox.emit('sulu.overlay.show-warning',
                    title,
                    description,

                    function() {
                        // cancel callback
                        callback(false);
                    }.bind(this),

                    function() {
                        // ok callback
                        callback(true);
                    }.bind(this)
                );
            };

            /*********
             * MISC
             *********/

            var isFirst,

                prefixIfNotFirst = function(addressString, prefix) {
                    if (!isFirst) {
                        addressString += prefix;
                    } else {
                        isFirst = false;
                    }
                    return addressString;
                },

                /**
                 * Adds the user specific order params to the url
                 * @param url
                 * @param order
                 */
                insertOrderParamsInUrl = function(url, order) {
                    if (!!order) {
                        var idxBy = url.indexOf('sortBy'),
                            idxOrder = url.indexOf('sortOrder'),
                            divider = '&';

                        if (idxBy === -1 && idxOrder === -1) {
                            if (url.indexOf('?') === -1) {
                                divider = '?';
                            }
                            return url + divider + 'sortBy=' + order.attribute + '&sortOrder=' + order.direction;
                        } else if (idxBy > -1 && idxOrder > -1) {
                            url = url.replace(/(sortBy=(\w)+)/, 'sortBy=' + order.attribute);
                            url = url.replace(/(sortOrder=(\w)+)/, 'sortOrder=' + order.direction);
                            return url;
                        } else {
                            this.sandbox.logger.error('Invalid list url! Either sortBy or sortOrder or both are missing!');
                        }
                    }
                    return url;
                };

            /**
             * creates an address string from an object
             * @param address
             * @returns {string}
             */
            app.sandbox.sulu.createAddressString = function(address) {
                var addressString = '';

                if (!address && typeof address !== 'object') {
                    throw new Error('no valid address object');
                }

                isFirst = true;

                // street
                if (!!address.street) {
                    addressString = prefixIfNotFirst(addressString, '');
                    addressString += address.street;
                }
                // number
                if (!!address.number) {
                    addressString = prefixIfNotFirst(addressString, ' ');
                    addressString += address.number;
                }
                // addition
                if (!!address.addition) {
                    addressString = prefixIfNotFirst(addressString, ' - ');
                    addressString += address.addition;
                }
                // zip
                if (!!address.zip) {
                    addressString = prefixIfNotFirst(addressString, ', ');
                    addressString += address.zip;
                }

                // zip
                if (!!address.city) {
                    if (!!address.city) {
                        addressString = prefixIfNotFirst(addressString, ' ');
                    } else {
                        addressString = prefixIfNotFirst(addressString, ', ');
                    }
                    addressString += address.city;
                }

                // state
                if (!!address.state) {
                    addressString = prefixIfNotFirst(addressString, ', ');
                    addressString += address.state;
                }

                // country
                if (!!address.country) {
                    addressString = prefixIfNotFirst(addressString, ', ');
                    addressString += address.country.name;
                }

                return addressString;
            };

            /**
             * initializes sulu list-toolbar with column options and datagrid
             * @param key {String} Settings key
             * @param url {String} Url to load fields from
             * @param listToolbarOptions {Object}
             * @param datagridOptions {Object}
             */
            app.sandbox.sulu.initListToolbarAndList = function(key, url, listToolbarOptions, datagridOptions) {
                var orderKey = key + 'Order',
                    fieldsKey = key + 'Fields',
                    pageSizeKey = key + 'PageSize',
                    limit = this.sandbox.sulu.getUserSetting(pageSizeKey),
                    order = this.sandbox.sulu.getUserSetting(orderKey);

                this.sandbox.sulu.loadUrlAndMergeWithSetting.call(
                    this,
                    fieldsKey,
                    ['translation', 'default', 'editable', 'validation', 'width'],
                    url,
                    function(data) {
                        var toolbarDefaults = {
                                columnOptions: {
                                    data: data,
                                    key: fieldsKey,
                                    url: url
                                },
                                instanceName: 'content',
                                inHeader: false
                            },
                            toolbarOptions = this.sandbox.util.extend(true, {}, toolbarDefaults, listToolbarOptions),
                            gridDefaults = {
                                view: 'table',
                                pagination: 'dropdown',
                                matchings: data,
                                viewOptions: {
                                    table: {
                                        noItemsText: 'public.empty-list',
                                        stickyHeader: true
                                    }
                                }
                            },
                            paginationOptionsDefaults = {
                                dropdown: {
                                    limit: limit
                                }
                            },
                            gridOptions;

                        if (!!limit) {
                            gridDefaults['paginationOptions'] = paginationOptionsDefaults;
                        }

                        gridOptions = this.sandbox.util.extend(true, {}, gridDefaults, datagridOptions);

                        // replace default order by custom order settings
                        gridOptions.url = insertOrderParamsInUrl(gridOptions.url,order);

                        gridOptions.searchInstanceName = gridOptions.searchInstanceName || toolbarOptions.instanceName;
                        gridOptions.columnOptionsInstanceName =
                            gridOptions.columnOptionsInstanceName || toolbarOptions.instanceName;

                        //start list-toolbar and datagrid
                        this.sandbox.start([
                            {
                                name: 'list-toolbar@suluadmin',
                                options: toolbarOptions
                            },
                            {
                                name: 'datagrid@husky',
                                options: gridOptions
                            }
                        ]);

                        // save page size when changed
                        this.sandbox.on('husky.datagrid.page-size.changed', function(size) {
                            this.sandbox.sulu.saveUserSetting(pageSizeKey, size);
                        }.bind(this));

                        // save sorting when changed
                        this.sandbox.on('husky.datagrid.data.sort', function(data) {
                            this.sandbox.sulu.saveUserSetting(orderKey, data);
                        }.bind(this));
                    }.bind(this)
                );
            };

            /**
             * Gets matchings data from user-settings and initializes a datagrid only
             * @param key {String} the user settings key
             * @param url {String} url to load the matchings data from. (needed, but only used if matchings are not
             *                     cached with the key)
             * @param datagridOptions {Object} options to pass to the datagrid component
             */
            app.sandbox.sulu.initList = function(key, url, datagridOptions) {
                this.sandbox.sulu.loadUrlAndMergeWithSetting.call(
                    this,
                    key,
                    ['translation', 'default', 'editable', 'validation', 'width'],
                    url,
                    function(data) {
                        // the default options
                        var options = {
                            view: 'table',
                            pagination: 'dropdown',
                            matchings: data
                        };

                        // merge default options with passed ones
                        options = this.sandbox.util.extend(true, {}, options, datagridOptions);

                        //start list-toolbar and datagrid
                        this.sandbox.start([
                            {
                                name: 'datagrid@husky',
                                options: options
                            }
                        ]);
                    }.bind(this));
            };
        }
    });
})();
