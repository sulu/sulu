(function() {

    'use strict';

    define(['app-config'], function(AppConfig) {

        return {

            name: 'url-manager',

            initialize: function(app) {
                var sandbox = app.sandbox,
                    urlStore = {},
                    keyModifiers = [];

                sandbox.urlManager = {};

                /**
                 * Set a url in the url store
                 * @method setUrl
                 * @param {String} key
                 * @param {Function} template
                 * @param {Function} handler
                 * @param {Function} keyModifier
                 */
                sandbox.urlManager.setUrl = function(key, template, handler, keyModifier) {
                    urlStore[key] = {
                        template: template,
                        handler: handler
                    };

                    if (!!keyModifier) {
                        keyModifiers.push(keyModifier);
                    }
                };

                /**
                 * @method getUrl
                 * @param {String} key
                 * @param {Object} data
                 */
                sandbox.urlManager.getUrl = function(key, data) {
                    var urlEntry, template;

                    if (key in urlStore) {
                        urlEntry = urlStore[key];
                    } else {
                        for (var i = -1, length = keyModifiers.length, modifiedKey; ++i < length;) {
                            modifiedKey = keyModifiers[i](key);
                            if (modifiedKey in urlStore) {
                                urlEntry = urlStore[modifiedKey];
                                break;
                            }
                        }
                    }

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
