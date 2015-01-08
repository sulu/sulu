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
        'ws-manager': '../../suluwebsocket/js/component/ws-manager/main'
    }
});

define(['config', 'ws-manager'], function(Config, WsManager) {

    return {
        name: 'Sulu Websocket Bundle',

        initialize: function(app) {

            'use strict';

            WsManager.init(Config.get('sulu.websocket.server'), Config.get('sulu.websocket.apps'));
        }
    }
});
