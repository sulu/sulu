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
                 * @param {Mixed} urlTpl
                 * @param {Function} handler
                 */
                sandbox.urlManager.setUrl = function(key, urlTpl, handler) {
                    urlStore[key] = {
                        template: urlTpl,
                        handler: handler
                    };
                },

                /**
                 * @method getUrl
                 * @param {String} key
                 * @param {Object} data
                 */
                sandbox.urlManager.getUrl = function(key, data) {
                    var urlEntry = urlStore[key],
                        urlTemplate = null;

                    if (!urlEntry) {
                        return null;
                    }

                    _.extend(data, { languageCode: AppConfig.getUser().locale }, {});

                    if (urlEntry.handler) {
                        data = urlEntry.handler.call(this, data);
                    }

                    urlTemplate = urlEntry.template;

                    if (typeof urlTemplate === 'function') {
                        urlTemplate = urlEntry.template.call(this, data);
                    }

                    return sandbox.template.parse(urlTemplate, data);
                }
            }
        };
    });
})();
