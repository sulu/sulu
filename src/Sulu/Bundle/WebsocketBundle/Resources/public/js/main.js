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
        'websocket-manager': '../../suluwebsocket/js/component/websocket-manager/main',
        'websocket/abstract': '../../suluwebsocket/js/component/websocket-manager/client/client',
        'websocket/client': '../../suluwebsocket/js/component/websocket-manager/client/websocket',
        'websocket/fallback': '../../suluwebsocket/js/component/websocket-manager/client/ajax',
        'websocket/wrapper': '../../suluwebsocket/js/component/websocket-manager/client/wrapper',
        'websocket/dummy': '../../suluwebsocket/js/component/websocket-manager/client/dummy'
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
