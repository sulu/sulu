/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        suluwebsocket: '../../suluwebsocket/js',
        'websocket-manager': '../../suluwebsocket/js/component/websocket-manager/main'
    }
});

define(['config', 'websocket-manager'], function(Config, WebsocketManager) {

    return {
        name: 'Sulu Websocket Bundle',

        initialize: function(app) {

            'use strict';

            WebsocketManager.init(Config.get('sulu.websocket.server'), Config.get('sulu.websocket.apps'));
        }
    }
});
