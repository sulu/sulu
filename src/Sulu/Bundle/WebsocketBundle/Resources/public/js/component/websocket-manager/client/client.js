/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {
    /**
     * Prototype for websocket-abstract-client which provides an abstraction layer
     *
     * @constructor
     */
    var Client = function(app) {
        /**
         * @type {Object}
         */
        this.app = app;

        /**
         * Default empty type
         * @type {string}
         */
        this.type = 'NONE';
    };

    Client.prototype.TYPE_WEBSOCKET = 'WEBSOCKET';
    Client.prototype.TYPE_AJAX = 'AJAX';

    Client.prototype.send = function(handler, message) {
        // abstract
    };

    Client.prototype.getType = function() {
        return this.type;
    };

    return Client;
});
