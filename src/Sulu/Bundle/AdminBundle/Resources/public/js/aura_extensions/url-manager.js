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
                 * @param {Mixed} template
                 * @param {Function} handler
                 */
                sandbox.urlManager.setUrl = function(key, template, handler) {
                    urlStore[key] = {
                        template: template,
                        handler: handler
                    };
                };

                /**
                 * @method getUrl
                 * @param {String} key
                 * @param {Object} data
                 */
                sandbox.urlManager.getUrl = function(key, data) {
                    var urlEntry = urlStore[key],
                        template;

                    if (!urlEntry) {
                        return null;
                    }

                    if (urlEntry.handler) {
                        data = urlEntry.handler.call(this, data);
                    }

                    _.extend(data, { languageCode: AppConfig.getUser().locale }, {});

                    template = urlEntry.template;

                    if (typeof template === 'function') {
                        template = urlEntry.template.call(this, data);
                    }

                    return sandbox.template.parse(template, data);
                };
            }
        };
    });
})();
