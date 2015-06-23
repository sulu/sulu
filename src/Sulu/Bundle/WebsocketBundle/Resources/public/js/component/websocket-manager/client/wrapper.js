/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'websocket/abstract',
    'websocket/fallback',
    'websocket/client',
    'jquery'
], function(Client, FallbackClient, WebsocketClient, $) {

    /**
     * Prototype for wrapper-client which capsules ajax or websocket logic
     *
     * @constructor
     */
    var AjaxClient = function(app, websocketUrl, tryWebsocket) {
            // parent constructor
            Client.call(this, app);

            /**
             * Url to websocket app
             * @type {string}
             */
            this.websocketUrl = websocketUrl;

            /**
             * Should wrapper try to connect over websockets
             * @type {boolean}
             */
            this.tryWebsocket = tryWebsocket;

            /**
             * Detect initialized client
             * @type {Deferred}
             */
            this.initialized = $.Deferred();

            /**
             * Detect state of websocket
             * @type {Deferred}
             */
            this.websocket = $.Deferred();
        },

        /**
         * Returns true if there is a websocket enabled in browser
         * @returns {boolean}
         */
        wsDetection = function() {
            var support = 'MozWebSocket' in window ? 'MozWebSocket' : ('WebSocket' in window ? 'WebSocket' : null);
            // no support
            if (support === null) {
                this.sandbox.logger.log("Your browser doesn't support Websockets.");
                return false;
            }
            // let's invite Firefox to the party.
            if (window.MozWebSocket) {
                window.WebSocket = window.MozWebSocket;
            }
            // support exists
            return true;
        };

    AjaxClient.prototype = Object.create(Client.prototype);

    AjaxClient.prototype.connect = function() {
        if (!!this.tryWebsocket && wsDetection()) {
            // error or close detection
            this.websocket.fail(function() {
                this.connectFallback();
            }.bind(this));

            this.connectWebsocket();
        } else {
            this.connectFallback();
        }
    };

    AjaxClient.prototype.connectWebsocket = function() {
        this.socket = new WebSocket(this.websocketUrl);
        this.client = new WebsocketClient(this.app, this.socket, this.id);

        // redirect on message event
        this.client.onMessage(function(message) {
            this._onMessage.notify(message);
        }.bind(this));

        this.socket.onopen = function() {
            this.initialized.resolve();
        }.bind(this);

        this.socket.onerror = function() {
            this.websocket.reject();
        }.bind(this);

        this.socket.onclose = function() {
            this.websocket.reject();
        }.bind(this);
    };

    AjaxClient.prototype.connectFallback = function() {
        this.client = new FallbackClient(this.app, this.id);
        this.initialized.resolve();

        // redirect on message event
        this.client.onMessage(function(message) {
            this._onMessage.notify(message);
        }.bind(this));
    };

    AjaxClient.prototype.getType = function() {
        return this.client.getType();
    };

    AjaxClient.prototype.addHandler = function(name, handler) {
        this.client.addHandler(name, handler);
    };

    AjaxClient.prototype.doSend = function(handler, message) {
        var def = $.Deferred();

        // wait for initialized client
        this.initialized.then(function() {
            this.client.doSend(handler, message)
                .then(function(handler, message) {
                    def.resolve(handler, message);
                }.bind(this))
                .fail(function(handler, message) {
                    def.reject(handler, message);
                }.bind(this));
        }.bind(this));

        return def.promise();
    };

    return AjaxClient;
});
