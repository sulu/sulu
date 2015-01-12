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

use Guzzle\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @var string
     */
    protected $name;

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
     * {@inheritdoc}
     */
    function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    /**
     * Returns query of upgrade request
     * @param ConnectionInterface $conn
     * @return array
     */
    protected function getQuery(ConnectionInterface $conn)
    {
        return $this->getRequest($conn)->getUrl(true)->getQuery();
    }

    /**
     * Returns request of connection
     * @param ConnectionInterface $conn
     * @return RequestInterface
     */
    protected function getRequest(ConnectionInterface $conn)
    {
        return $conn->WebSocket->request;
    }

    /**
     * Returns session of connection
     * @param ConnectionInterface $conn
     * @return SessionInterface
     */
    protected function getSession(ConnectionInterface $conn)
    {
        return $conn->Session;
    }

    /**
     * Returns user for given firewall
     * @param string $firewall
     * @return UserInterface
     */
    protected function getUser(ConnectionInterface $conn, $firewall)
    {
        $serializedToken = $this->getSession($conn)->get('_security_' . $firewall);
        $token = unserialize($serializedToken);

        return $token->getUser();
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    function __toString()
    {
        return $this->getName();
    }
}
