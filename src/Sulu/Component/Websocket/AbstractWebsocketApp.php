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
     * initialize clients container
     */
    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    /**
     * Returns query of upgrade request
     * @param ConnectionInterface $conn
     * @return array
     */
    protected function getQuery(ConnectionInterface $conn)
    {
        return $conn->WebSocket->request->getUrl(true)->getQuery();
    }

    /**
     * Indicates that a parameter in query, of the upgrade request, exists
     * @param ConnectionInterface $conn
     * @param string $parameterName
     * @return bool
     */
    protected function hasQueryParameter(ConnectionInterface $conn, $parameterName)
    {
        return array_key_exists($parameterName, $this->getQuery($conn));
    }

    /**
     * Returns a parameter in query, of the upgrade request
     * If the parameter not exists it returns the given default value
     * @param ConnectionInterface $conn
     * @param string $parameterName
     * @param mixed $default
     * @return bool
     */
    protected function getQueryParameter(ConnectionInterface $conn, $parameterName, $default = null)
    {
        if($this->hasQueryParameter($conn, $parameterName)){
            return $this->getQuery($conn)[$parameterName];
        }

        return $default;
    }
}
