define([], function() {

        'use strict';

        return function(app) {


            var settings = SULU.user.settings;


            app.sandbox.sulu.storage = {

                loadSettings: function(key, url, callback) {
                    if (!!settings[key]) {
                        callback(settings[key]);
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
                },

                saveSettings: function(key, value, url) {
                    settings[key] = value;

                    var data = {
                        key: key,
                        data: value //JSON.stringify(value)
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
            };

            app.sandbox.sulu.initListToolbar = function(container, key, url) {
                this.sandbox.sulu.storage.loadSettings(key, url, function(data) {
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
        };

    }
)
;
