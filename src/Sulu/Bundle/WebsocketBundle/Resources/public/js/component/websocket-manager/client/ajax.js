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
    var AjaxClient = function(app, id) {
        // parent constructor
        Client.call(this, app, id);

        /**
         * Type of websocket-client
         * @type {string}
         */
        this.type = Client.TYPE_AJAX;
    };

    AjaxClient.prototype = Object.create(Client.prototype);

    AjaxClient.prototype.doSend = function(handler, message) {
        var def = $.Deferred();

        $.ajax({
            url: '/admin/websocket/' + this.app.name,
            type: 'POST',
            data: {message: this.generateMessage(handler, message, {})}
        }).then(function(data) {
            this._onMessage.notify(data);

            if (!data.error) {
                def.resolve(data.handler, data.message);
            } else {
                def.reject(data.handler, data.message);
            }
        }.bind(this));

        return def.promise();
    };

    return AjaxClient;
});
