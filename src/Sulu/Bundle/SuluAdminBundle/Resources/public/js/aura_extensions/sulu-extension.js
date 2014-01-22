(function() {

    'use strict';

    define([], {

        initialize: function(app) {

            /*********
             * USER SETTINGS
             *********/

            // load settings
            var settings = app.sandbox.util.extend(true, {}, SULU.user.settings);

            /**
             * load user settings
             * @param key
             * @param url Where to get data from, if not already available
             * @param callback Function to return settings value
             */
            app.sandbox.sulu.loadUserSetting = function(key, url, callback) {
                if (settings[key]) {
                    callback(settings[key])
                } else {
                    // get from server
                    app.sandbox.util.load(url)
                        .then(function(data) {
                            settings[key] = data;
                            callback(data);
                        }.bind(this))
                        .fail(function(data) {
                            app.sandbox.logger.log('data could not be loaded:', data);
                        }.bind(this));
                }
            };

            /**
             * returns settings for a specified key
             * @param key
             * @returns {}
             */
            app.sandbox.sulu.getUserSetting = function(key) {
                return settings[key] ? settings[key] : null;
            };

            /**
             * saves data locally and to database
             * @param key
             * @param value
             * @param url Defines where to save data to
             */
            app.sandbox.sulu.saveUserSetting = function(key, value, url) {
                console.log('SETTING',settings);
                settings[key] = value;

                var data = {
                    key: key,
                    data: value
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
            }

            /*********
             * MISC
             *********/

            /**
             * initializes sulu list-toolbar with column options
             * @param container
             * @param key
             * @param url
             */
            app.sandbox.sulu.initListToolbar = function(container, key, url) {
                this.sandbox.util.load(url)
                    .then(function(data) {
                        this.sandbox.start([
                            {
                                name: 'list-toolbar@suluadmin',
                                options: {
                                    el: container,
                                    columnOptions: {
                                        data: data,
                                        key: key,
                                        url: url
                                    }
                                }
                            }
                        ]);
                    }.bind(this));
            }
        }
    });
})();
