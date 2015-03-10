/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery'], function($) {
    /**
     * Prototype for websocket-abstract-client which provides an abstraction layer
     *
     * @constructor
     */
    var Client = function(app, id) {
        /**
         * @type {Object}
         */
        this.app = app;

        /**
         * Default empty type
         * @type {string}
         */
        this.type = 'NONE';

        /**
         * Deferred called progress callbacks with each message
         * @type {Deferred}
         * @private
         */
        this._onMessage = $.Deferred();

        /**
         * Container for handlers
         * @type {{}}
         */
        this.handlers = {};

        // if id is not passed generate unique id
        if (!id) {
            id = this.generateUuid();
        }

        /**
         * Uuid if current connection
         * @type {string}
         */
        this.id = id;
    };

    Client.TYPE_WEBSOCKET = 'WEBSOCKET';
    Client.TYPE_AJAX = 'AJAX';

    Client.prototype.generateMessage = function(handler, message, options) {
        return JSON.stringify({handler: handler, message: message, options: options})
    };

    Client.prototype.send = function(handler, message) {
        return this.doSend(handler, message);
    };

    Client.prototype.getType = function() {
        return this.type;
    };

    Client.prototype.addHandler = function(name, handler) {
        if (!this.handlers[name]) {
            this.handlers[name] = $.Deferred();
        }
        this.handlers[name].progress(handler);
    };

    Client.prototype.onMessage = function(handler) {
        this._onMessage.progress(handler);
    };

    /**
     * Generates a GUID string.
     * @returns {String} The generated GUID.
     * @example af8a8416-6e18-a307-bd9c-f2c947bbb3aa
     * @author Slavik Meltser (slavik@meltser.info).
     * @link http://slavik.meltser.info/?p=142
     */
    Client.prototype.generateUuid = function() {
        function _p8(s) {
            var p = (Math.random().toString(16) + "000000000").substr(2, 8);
            return s ? "-" + p.substr(0, 4) + "-" + p.substr(4, 4) : p;
        }

        return _p8() + _p8(true) + _p8(true) + _p8();
    };

    return Client;
});
