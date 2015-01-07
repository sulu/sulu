<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket;

/**
 * Interface for a app-manager
 */
interface AppManagerInterface
{
    /**
     * @param string $route The URI the client will connect to
     * @param AppInterface $app Your application to server for the route. If not specified, assumed to be for a WebSocket
     * @param array $allowedOrigins An array of hosts allowed to connect (same host by default), ['*'] for any
     * @param string $httpHost Override the $httpHost variable provided in the __construct
     */
    public function add($route, AppInterface $app, $allowedOrigins = array('*'), $httpHost = null);

    /**
     * Run the server by entering the event loop
     */
    public function run();
}
