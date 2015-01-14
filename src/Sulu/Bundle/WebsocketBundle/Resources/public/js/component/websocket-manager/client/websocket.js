/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['websocket/abstract'], function(Client) {

    /**
     * Prototype for websocket-client which provides an abstraction layer
     *
     * @constructor
     */
    var WebsocketClient = function(app, socket) {
        /**
         * @type {Object}
         */
        this.app = app;

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
    };

    WebsocketClient.prototype = Object.create(WebsocketClient.prototype);

    WebsocketClient.prototype.send = function(handler, message) {
        this.socket.send(JSON.stringify({handler: handler, message: message}));
    };

    return WebsocketClient;
});
