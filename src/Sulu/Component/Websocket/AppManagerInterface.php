<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket;

use React\EventLoop\LoopInterface;

/**
 * Interface for a app-manager.
 */
interface AppManagerInterface
{
    /**
     * @param string                $route          The URI the client will connect to
     * @param WebsocketAppInterface $app            Your application to server for the route. If not specified, assumed to be for a WebSocket
     * @param array                 $allowedOrigins An array of hosts allowed to connect (same host by default), ['*'] for any
     * @param string                $httpHost       Override the $httpHost variable provided in the __construct
     */
    public function add($route, WebsocketAppInterface $app, $allowedOrigins = ['*'], $httpHost = null);

    /**
     * Run the server by entering the event loop.
     */
    public function run();

    /**
     * Returns apps and configuration.
     *
     * @return array
     */
    public function getApps();

    /**
     * Return websocket app.
     *
     * @param string $name
     *
     * @return WebsocketAppInterface
     */
    public function getApp($name);

    /**
     * Returns port to listen on.
     *
     * @return int
     */
    public function getPort();

    /**
     * Returns HTTP hostname clients intend to connect to.
     *
     * @return string
     */
    public function getHttpHost();

    /**
     * Return IP address to bind to.
     *
     * @return string
     */
    public function getIpAddress();

    /**
     * Return used event loop if null default loop will be created.
     *
     * @return LoopInterface
     */
    public function getLoop();
}
