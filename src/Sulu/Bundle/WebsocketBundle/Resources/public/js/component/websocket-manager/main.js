/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Provides Websocket connections
 */
define([
    'websocket/wrapper',
    'websocket/dummy'
], function(Client, DummyClient) {

    'use strict';

    var defaults = {enabled: false, port: 9876, httpHost: 'localhost', ssl: false};

    return {
        apps: {},

        clients: {},

        config: {},

        url: null,

        init: function(config, apps) {
            this.config = config;
            this.apps = apps;
        },

        getConfig: function(name) {
            return this.config[name] || defaults[name];
        },

        getUrl: function(appName) {
            if (this.url === null) {
                var host = this.getConfig('httpHost'),
                    port = this.getConfig('port'),
                    ssl = this.getConfig('ssl');

                this.url = [(ssl ? 'wss://' : 'ws://'), host, ':', port].join('');
            }

            // TODO generate route with params
            return this.url + this.apps[appName].route;
        },

        getClient: function(appName) {
            if (!this.clients[appName]) {
                this.clients[appName] = this.createClient(appName);
                this.clients[appName].connect();
            }

            return this.clients[appName];
        },

        createClient: function(appName) {
            return new Client(this.apps[appName], this.getUrl(appName), this.getConfig('enabled'));
        },

        createDummyClient: function(appName) {
            return new DummyClient(this.apps[appName]);
        }
    };
});
