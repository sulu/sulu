(function() {

    'use strict';

    define(['app-config'], function(AppConfig) {

        return {

            name: 'url-manager',

            initialize: function(app) {
                var sandbox = app.sandbox,
                    urlStore = {};

                sandbox.urlManager = {};

                /**
                 * Set a url in the url store
                 * @method setUrl
                 * @param {String} key
                 * @param {String} urlTpl
                 */
                sandbox.urlManager.setUrl = function(key, urlTpl) {
                    urlStore[key] = urlTpl
                },

                /**
                 * @method getUrl
                 * @param {String} key
                 * @param {Object} data
                 */
                sandbox.urlManager.getUrl = function(key, data) {
                    _.extend(data, { languageCode: AppConfig.getUser().locale }, {});
                    return sandbox.template.parse(urlStore[key], data);
                }
            }
        };
    });
})();
