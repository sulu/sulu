<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Websocket\MessageDispatcher;

use Ratchet\ConnectionInterface;
use Sulu\Component\Websocket\ConnectionContext\AuthenticatedConnectionContext;

/**
 * Extends the message dispatcher app with authentication
 */
class AuthenticatedMessageDispatcherApp extends MessageDispatcherApp
{
    /**
     * @var string
     */
    private $firewall;

    public function __construct($appName, MessageDispatcherInterface $messageDispatcher, $firewall)
    {
        parent::__construct($appName, $messageDispatcher);

        $this->firewall = $firewall;
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext(ConnectionInterface $conn)
    {
        return new AuthenticatedConnectionContext($this->firewall, $conn);
    }
}
