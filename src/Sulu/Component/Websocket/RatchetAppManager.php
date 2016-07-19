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

use Ratchet\App;
use React\EventLoop\LoopInterface;
use Sulu\Bundle\WebsocketBundle\App\WebsocketApp;

/**
 * Class manages ratchet websocket apps.
 */
class RatchetAppManager implements AppManagerInterface
{
    /**
     * Port to listen on. If 80, assuming production, Flash on 843 otherwise expecting Flash to be proxied through 8843.
     *
     * @var int
     */
    private $port;

    /**
     * HTTP hostname clients intend to connect to. MUST match JS `new WebSocket('ws://$httpHost');`.
     *
     * @var string
     */
    private $httpHost;

    /**
     * IP address to bind to. Default is localhost/proxy only. '0.0.0.0' for any machine.
     *
     * @var string
     */
    private $ipAddress;

    /**
     * Specific React\EventLoop to bind the application to. null will create one for you.
     *
     * @var LoopInterface
     */
    private $loop;

    /**
     * Ratchet app.
     *
     * @var WebsocketApp
     */
    private $ratchetApp;

    /**
     * Container for websocket apps.
     *
     * @var array
     */
    private $apps = [];

    /**
     * @param int           $port      Port to listen on. If 80, assuming production, Flash on 843 otherwise expecting Flash to be proxied through 8843
     * @param string        $httpHost  HTTP hostname clients intend to connect to. MUST match JS `new WebSocket('ws://$httpHost');`
     * @param string        $ipAddress IP address to bind to. Default is localhost/proxy only. '0.0.0.0' for any machine
     * @param LoopInterface $loop      Specific React\EventLoop to bind the application to. null will create one for you
     */
    public function __construct(
        $port,
        $httpHost = 'localhost',
        $ipAddress = '127.0.0.1',
        LoopInterface $loop = null
    ) {
        $this->port = $port;
        $this->httpHost = $httpHost;
        $this->ipAddress = $ipAddress;
        $this->loop = $loop;
    }

    /**
     * {@inheritdoc}
     */
    public function add($route, WebsocketAppInterface $app, $allowedOrigins = ['*'], $httpHost = null)
    {
        $this->apps[$app->getName()] = [
            'route' => $route,
            'app' => $app,
            'name' => $app->getName(),
            'allowedOrigins' => $allowedOrigins,
            'httpHost' => $httpHost ?: $this->getHttpHost(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->ratchetApp = new WebsocketApp($this->getHttpHost(), $this->getPort(), $this->getIpAddress(), $this->loop);

        foreach ($this->apps as $app) {
            // TODO introduce session config for this: $sessionApp = new SessionProvider($app['app'], $this->sessionHandler);
            $this->ratchetApp->route($app['route'], $app['app'], $app['allowedOrigins'], $app['httpHost']);
        }

        $this->ratchetApp->run();
    }

    /**
     * {@inheritdoc}
     */
    public function getApps()
    {
        return $this->apps;
    }

    /**
     * {@inheritdoc}
     */
    public function getApp($name)
    {
        return $this->apps[$name]['app'];
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpHost()
    {
        return $this->httpHost;
    }

    /**
     * {@inheritdoc}
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Return used event loop if null default loop will be created.
     *
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }
}
