<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\MessageDispatcher;

use Doctrine\Common\Cache\Cache;
use Ratchet\ConnectionInterface;
use Sulu\Component\Websocket\ConnectionContext\AuthenticatedConnectionContext;
use Sulu\Component\Websocket\ConnectionContext\ConnectionContextInterface;

/**
 * Extends the message dispatcher app with authentication.
 */
class AuthenticatedMessageDispatcherApp extends MessageDispatcherApp
{
    /**
     * @var string
     */
    private $firewallName;

    public function __construct(
        $appName,
        MessageDispatcherInterface $messageDispatcher,
        $firewallName,
        Cache $contextsCache
    ) {
        parent::__construct($appName, $messageDispatcher, $contextsCache);

        $this->firewallName = $firewallName;
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext(ConnectionInterface $conn)
    {
        return new AuthenticatedConnectionContext($this->firewallName, $conn);
    }

    /**
     * {@inheritdoc}
     */
    protected function createMessageHandlerContext(ConnectionContextInterface $context, $handlerName)
    {
        return new AuthenticatedMessageHandlerContext($context, $handlerName);
    }
}
