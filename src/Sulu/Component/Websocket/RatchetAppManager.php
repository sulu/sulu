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

use Ratchet\App;
use React\EventLoop\LoopInterface;

/**
 * Class manages retchat websocket apps
 */
class RatchetAppManager implements AppManagerInterface
{
    /**
     * Port to listen on. If 80, assuming production, Flash on 843 otherwise expecting Flash to be proxied through 8843
     * @var int
     */
    private $port;

    /**
     * HTTP hostname clients intend to connect to. MUST match JS `new WebSocket('ws://$httpHost');`
     * @var string
     */
    private $httpHost;

    /**
     * IP address to bind to. Default is localhost/proxy only. '0.0.0.0' for any machine.
     * @var string
     */
    private $ipAddress;

    /**
     * Specific React\EventLoop to bind the application to. null will create one for you.
     * @var LoopInterface
     */
    private $loop;

    /**
     * Retchat app
     * @var App
     */
    private $ratchetApp;

    /**
     * @param int $port Port to listen on. If 80, assuming production, Flash on 843 otherwise expecting Flash to be proxied through 8843
     * @param string $httpHost HTTP hostname clients intend to connect to. MUST match JS `new WebSocket('ws://$httpHost');`
     * @param string $ipAddress IP address to bind to. Default is localhost/proxy only. '0.0.0.0' for any machine.
     * @param LoopInterface $loop Specific React\EventLoop to bind the application to. null will create one for you.
     */
    function __construct($port, $httpHost = 'localhost', $ipAddress = '127.0.0.1', $loop)
    {
        $this->port = $port;
        $this->httpHost = $httpHost;
        $this->ipAddress = $ipAddress;
        $this->loop = $loop;

        $this->ratchetApp = new App($httpHost, $port, $ipAddress, $loop);
    }

    /**
     * {@inheritdoc}
     */
    public function add($route, AppInterface $app, $allowedOrigins = array('*'), $httpHost = null)
    {
        $this->ratchetApp->route($route, $app, $allowedOrigins, $httpHost);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->ratchetApp->run();
    }
}
