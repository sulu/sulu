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

use Ratchet\ConnectionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Abstract websocket app
 */
abstract class AbstractWebsocketApp implements WebsocketAppInterface
{
    /**
     * @var \SplObjectStorage
     */
    private $clients;

    /**
     * @var array
     */
    private $contexts;

    /**
     * @var string
     */
    protected $name;

    /**
     * initialize clients container
     */
    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->contexts = array();
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn)
    {
        if (!$this->getContext($conn)->isValid()) {
            $conn->close();
        } else {
            $this->clients->attach($conn);
        }
    }

    /**
     * {@inheritdoc}
     */
    function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns websocket context for given connection
     * @param ConnectionInterface $conn
     * @return ConnectionContextInterface
     */
    protected function getContext(ConnectionInterface $conn)
    {
        if (!array_key_exists($conn->resourceId, $this->contexts)) {
            $this->contexts[$conn->resourceId] = $this->createContext($conn);
        }

        return $this->contexts[$conn->resourceId];
    }

    /**
     * Returns new created websocket context object
     * @return ConnectionContextInterface
     */
    protected function createContext(ConnectionInterface $conn)
    {
        return new ConnectionContext($conn);
    }

    /**
     * {@inheritdoc}
     */
    function __toString()
    {
        return $this->getName();
    }
}
