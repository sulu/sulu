(function() {

    'use strict';

    define([], {

        initialize: function(app) {
            /**
             * Userproperties
             */
            app.sandbox.sulu.user = app.sandbox.util.extend(false, {}, SULU.user);

            /*********
             * Sulu namespace
             *********/
            app.sandbox.sulu = {};

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
                            temp[array[i].id] = i;
                        } else {
                            temp.push(array[i].id);
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

                        if (userFields) {
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
                                        if (attributesArray.indexOf(attrname)<0) {
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
                    url = '/admin/api/users/'+ SULU.user.id +'/settings/' + key;
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

            /*********
             * MISC
             *********/

            /**
             * initializes sulu list-toolbar with column options and datagrid
             * @param key Settings key
             * @param url Url to load fields from
             * @param listToolbarOptions
             * @param datagridOptions
             */
            app.sandbox.sulu.initListToolbarAndList = function(key, url, listToolbarOptions, datagridOptions) {
                this.sandbox.sulu.loadUrlAndMergeWithSetting.call(this, key, ['translation', 'default', 'editable', 'validation', 'width'], url, function(data) {

                    var toolbarDefaults = {
                            columnOptions: {
                                data: data,
                                key: key,
                                url: url
                            },
                            instanceName: 'content',
                            inHeader: false
                        },
                        toolbarOptions = this.sandbox.util.extend(true, {}, toolbarDefaults, listToolbarOptions),
                        gridDefaults = {
                            paginationOptions: {
                                pageSize: 10
                            },
                            pagination: true,
                            sortable: true,
                            selectItem: {
                                type: 'checkbox'
                            },
                            removeRow: false,
                            excludeFields: ['']
                        },
                        gridOptions = this.sandbox.util.extend(true, {}, gridDefaults, datagridOptions);

                    //start list-toolbar component
                    this.sandbox.start([
                        {
                            name: 'list-toolbar@suluadmin',
                            options: toolbarOptions
                        }
                    ]);

                    gridOptions.fieldsData = data;
                    gridOptions.searchInstanceName = gridOptions.searchInstanceName ? gridOptions.searchInstanceName : toolbarOptions.instanceName;
                    gridOptions.columnOptionsInstanceName = gridOptions.columnOptionsInstanceName ? gridOptions.columnOptionsInstanceName : toolbarOptions.instanceName;
                    gridOptions.contentContainer = '#content';

                    // start datagrid
                    this.sandbox.start([
                        {
                            name: 'datagrid@husky',
                            options: gridOptions
                        }
                    ]);

                }.bind(this));
            };
        }
    });
})();
