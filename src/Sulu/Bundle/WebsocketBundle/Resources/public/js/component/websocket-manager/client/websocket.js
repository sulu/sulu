/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['websocket/abstract', 'jquery'], function(Client, $) {

    /**
     * Prototype for websocket-client which provides an abstraction layer
     *
     * @constructor
     */
    var WebsocketClient = function(app, socket, id) {
            // parent constructor
            Client.call(this, app, id);

            /**
             * Type of websocket-client
             * @type {string}
             */
            this.type = Client.TYPE_WEBSOCKET;

            /**
             * Open websocket client
             * @type {Object}
             */
            this.socket = socket;

            /**
             * Container for open messages
             * @type {{}}
             */
            this.messages = {};

            // Message handler
            this.socket.onmessage = function(e) {
                var data = JSON.parse(e.data);

                this._onMessage.notify(data);

                if (!!data.options && !!data.options.id && !!this.messages[data.options.id]) {
                    if (!data.error) {
                        this.messages[data.options.id].resolve(data.handler, data.message);
                    } else {
                        this.messages[data.options.id].reject(data.handler, data.message);
                    }

                    // remove handler
                    this.messages[data.options.id] = null;
                } else if (!!data.handler && !!this.handlers[data.handler]) {
                    this.handlers[data.handler].notify(data.message);
                }
            }.bind(this);
        };

    WebsocketClient.prototype = Object.create(Client.prototype);

    WebsocketClient.prototype.doSend = function(handler, message) {
        var id = this.generateUuid();

        this.messages[id] = $.Deferred();

        this.socket.send(this.generateMessage(handler, message, {id: id}));

        return this.messages[id];
    };

    return WebsocketClient;
});
