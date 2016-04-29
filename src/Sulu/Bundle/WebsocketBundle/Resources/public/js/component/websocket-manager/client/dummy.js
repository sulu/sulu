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
     * Prototype for dummy-client which provides an abstraction layer.
     *
     * @constructor
     */
    var DummyClient = function(app, id) {
        // parent constructor
        Client.call(this, app, id);

        /**
         * Type of websocket-client
         * @type {string}
         */
        this.type = Client.TYPE_AJAX;
    };

    DummyClient.prototype = Object.create(Client.prototype);

    DummyClient.prototype.doSend = function() {
        var def = $.Deferred();

        def.resolve();

        return def.promise();
    };

    return DummyClient;
});
