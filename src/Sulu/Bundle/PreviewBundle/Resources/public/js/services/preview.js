/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'underscore',
    'config',
    'app-config',
    'services/husky/mediator',
    'websocket-manager'
], function($, _, Config, AppConfig, Mediator, WebsocketManager) {

    'use strict';

    var constants = {
            WEBSOCKET_APP_NAME: 'admin',
            MESSAGE_HANDLER_NAME: 'sulu_preview.preview',
            MODE_OFF: 'off'
        },

        template = _.template([
            '<div style="height: 100%;"',
            '     data-aura-component="preview@sulupreview"',
            '     data-aura-permissions=\'<%= JSON.stringify(permissions) %>\'',
            '     data-aura-mode="<%= mode %>"',
            '<% if (!!webspace) { %>',
            '     data-aura-webspace="<%= webspace %>"',
            '<% } %>',
            '/>'
        ].join('')),

        /**
         * Contains information about delay and websocket mode.
         *
         * @type {{delay, mode}}
         */
        config = Config.get('sulu_preview'),

        /**
         * Returns property sequence. E.g. ['content', 0, 'title']
         *
         * @param {$} $element
         * @param {String} prefix
         *
         * @returns {String[]}
         */
        getSequence = function($element, prefix) {
            var sequence = [],
                $parents = $element.parents('*[data-mapper-property]'),
                item = $element.parents('*[data-mapper-property-tpl]')[0],
                parentProperty;

            if (!!prefix) {
                sequence.push(prefix);
            }

            while (!$element.data('element')) {
                if ($element.length === 0) {
                    return false;
                }

                $element = $element.parent();
            }

            if ($parents.length > 0) {
                parentProperty = $($parents[0]).data('mapperProperty');
                sequence = sequence.concat([
                    parentProperty,
                    $(item).index(),
                    $element.data('mapperProperty')
                ]);
            } else {
                sequence.push($element.data('mapperProperty'));
            }

            return sequence;
        },

        /**
         * Returns property-name for sequence.
         *
         * @param {String[]} sequence
         *
         * @returns {String}
         */
        getPropertyName = function(sequence) {
            for (var i = 1, length = sequence.length, propertyNameParts = [sequence[0]]; i < length; i++) {
                propertyNameParts.push('[' + sequence[i] + ']');
            }

            return propertyNameParts.join('');
        },

        /**
         * Returns value for given element.
         *
         * @param {$} $element
         *
         * @returns {*}
         */
        getValue = function($element) {
            while (!$element.data('element')) {
                if ($element.length === 0) {
                    return false;
                }

                $element = $element.parent();
            }

            var element = $element.data('element');
            if (!!$element.data('elementGroup')) {
                element = $element.data('elementGroup');
            }

            return element.getValue();
        },

        /**
         * Creates update message and send it server.
         *
         * @param {{send}} client
         * @param {String} webspace
         * @param {Object} data
         *
         * @returns {$.Deferred}
         */
        sendUpdate = function(client, webspace, data) {
            return client.send(constants.MESSAGE_HANDLER_NAME, {
                command: 'update',
                webspaceKey: webspace,
                data: data
            }).then(function(handler, message) {
                if (!message || !message.data) {
                    return;
                }

                Mediator.emit('sulu.preview.update-content', message.data);
            }.bind(this));
        },

        /**
         * Creates update context message and send it server.
         *
         * @param {{send}} client
         * @param {String} webspace
         * @param {Object} context
         * @param {Object} data
         *
         * @returns {$.Deferred}
         */
        sendContextUpdate = function(client, webspace, context, data) {
            return client.send(constants.MESSAGE_HANDLER_NAME, {
                command: 'update-context',
                webspaceKey: webspace,
                context: context,
                data: data
            }).then(function(handler, message) {
                if (!message || !message.response) {
                    return;
                }

                // clear the preview first (firefox does not override but append)
                Mediator.emit('sulu.preview.set-content', '');
                Mediator.emit('sulu.preview.set-content', message.response);
            }.bind(this));
        },

        /**
         * Initializes new preview.
         *
         * @param {Object} permissions
         * @param {String} webspace
         *
         * @returns {Preview}
         */
        initialize = function(permissions, webspace) {
            var webspaceDeferred = $.Deferred();

            Mediator.emit('sulu.sidebar.set-widget', null, template({
                permissions: permissions || {},
                webspace: webspace,
                mode: config.mode
            }));


            Mediator.once('sulu.preview.webspace', function(webspace) {
                webspaceDeferred.resolve(webspace);
            });

            return new Preview(WebsocketManager.getClient(constants.WEBSOCKET_APP_NAME), webspaceDeferred);
        };

    /**
     * Constructor for preview.
     *
     * @param {{send}} client
     * @param {$.Deferred} promise
     *
     * @constructor
     */
    function Preview(client, promise) {
        this.client = client;
        this.promise = promise;

        this.promise.then(function(webspace) {
            this.webspace = webspace;

            Mediator.on('sulu.preview.webspace', this.setWebspace.bind(this));
            Mediator.on('sulu.preview.render', this.render.bind(this));
        }.bind(this));
    }

    /**
     * Start preview with given parameters.
     *
     * @param {String} className
     * @param {String} id
     * @param {String} locale
     * @param {{}} data
     *
     * @returns {$.Deferred}
     */
    Preview.prototype.start = function(className, id, locale, data) {
        this.data = data;

        this.promise.then(function() {
            this.client.send(constants.MESSAGE_HANDLER_NAME, {
                command: 'start',
                class: className,
                id: id,
                user: AppConfig.getUser().id,
                webspaceKey: this.getWebspace(),
                locale: locale,
                data: this.data
            }).then(function(handler, message) {
                if (!message || !message.response) {
                    return;
                }

                Mediator.emit('sulu.preview.set-content', message.response);
            }).fail(function(handler, message) {
                Mediator.emit('sulu.preview.error', message.code, message.message);
            }.bind(this));
        }.bind(this));
    };

    /**
     * Stops preview.
     *
     * @returns {$.Deferred}
     */
    Preview.prototype.stop = function() {
        this.promise.then(function() {
            this.client.send(constants.MESSAGE_HANDLER_NAME, {
                command: 'stop'
            }).fail(function(handler, message) {
                Mediator.emit('sulu.preview.error', message.code, message.message);
            }.bind(this));

            Mediator.off('sulu.preview.webspace');
            Mediator.off('sulu.preview.render');
            Mediator.off('sulu.preview.update');

            if (!!this.$element) {
                this.$element.off('keyup');
                this.$element.off('change');
            }
        }.bind(this));
    };

    /**
     * Update preview with given element value.
     *
     * @param {$} $elements
     * @param {String} prefix
     *
     * @returns {$.Deferred}
     */
    Preview.prototype.update = function($elements, prefix) {
        // FIXME combine all updates and only send one request when promise is not resolved.

        this.promise.then(function() {
            var data = {};

            $elements.each(function() {
                var sequence = getSequence($(this), prefix);
                data[getPropertyName(sequence)] = getValue($(this));
            });

            return sendUpdate(this.client, this.getWebspace(), data);
        }.bind(this));
    };

    /**
     * Update single property in preview with given value.
     *
     * @param {String} propertyName
     * @param {*} value
     *
     * @returns {$.Deferred}
     */
    Preview.prototype.updateProperty = function(propertyName, value) {
        // FIXME combine all updates and only send one request when promise is not resolved.

        this.promise.then(function() {
            var data = {};
            data[propertyName] = value;

            return sendUpdate(this.client, this.getWebspace(), data);
        }.bind(this));
    };

    /**
     * Update context for object in preview.
     *
     * @param {Object} context
     * @param {Object} data
     *
     * @returns {$.Deferred}
     */
    Preview.prototype.updateContext = function(context, data) {
        this.promise.then(function() {
            return sendContextUpdate(this.client, this.getWebspace(), context, data);
        }.bind(this));
    };

    /**
     * Bind preview dom-events to given element.
     *
     * @param {$} $element
     * @param {String} prefix
     */
    Preview.prototype.bindDomEvents = function($element, prefix) {
        this.$element = $element;

        var changeFilter = '.preview-change-update, input[type="checkbox"].preview-update, input[type="radio"].preview-update, select.preview-update, div.preview-update',
            keyupFilter = '.preview-update:not(' + changeFilter + ', .no-preview-update)';

        $element.on('keyup', keyupFilter, _.debounce(function(e) {
            this.update($(e.currentTarget), prefix);
        }.bind(this), config.delay));

        $element.on('change', changeFilter, _.debounce(function(e) {
            this.update($(e.currentTarget), prefix);
        }.bind(this), 10));

        Mediator.on('sulu.preview.update', _.debounce(function($element, value) {
            var sequence = getSequence($element, prefix);
            var propertyName = getPropertyName(sequence);
            var data = {};
            data[propertyName] = value;

            sendUpdate(this.client, this.getWebspace(), data);
        }.bind(this), config.delay));
    };

    /**
     * Returns webspace.
     *
     * @returns {String}
     */
    Preview.prototype.getWebspace = function() {
        return this.webspace;
    };

    /**
     * Set webspace and re-render preview with it.
     *
     * @param {String} webspace
     */
    Preview.prototype.setWebspace = function(webspace) {
        this.webspace = webspace;

        this.render();
    };

    /**
     * Render preview and set-content of component.
     *
     * @returns {$.Deferred}
     */
    Preview.prototype.render = function() {
        return this.client.send(constants.MESSAGE_HANDLER_NAME, {
            command: 'render',
            webspaceKey: this.getWebspace()
        }).then(function(handler, message) {
            if (!message) {
                return;
            }

            Mediator.emit('sulu.preview.set-content', message.response);
        }.bind(this)).fail(function(handler, message) {
            Mediator.emit('sulu.preview.error', message.code, message.message);
        }.bind(this));
    };

    return {
        /**
         * Initializes new preview.
         *
         * @param {Object} permissions
         * @param {String} webspace
         *
         * @returns {Preview}
         */
        initialize: function(permissions, webspace) {
            if (config.mode === constants.MODE_OFF) {
                return new Preview(WebsocketManager.createDummyClient(constants.WEBSOCKET_APP_NAME), $.Deferred());
            }

            return initialize(permissions, webspace);
        },

        /**
         * Returns property sequence. E.g. ['content', 0, 'title']
         *
         * @param {$} $element
         * @param {String} prefix
         *
         * @returns {String[]}
         */
        getSequence: function($element, prefix) {
            return getSequence($element, prefix);
        },

        /**
         * Destroys given preview object.
         *
         * @param {Preview} preview
         */
        destroy: function(preview) {
            preview.stop();
            Mediator.emit('sulu.sidebar.empty');
        }
    };
});
