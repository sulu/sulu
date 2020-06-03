<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\ConnectionContext;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\QueryString;
use Ratchet\ConnectionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Simple websocket context.
 */
class ConnectionContext implements ConnectionContextInterface
{
    /**
     * @var RequestInterface
     */
    private $request = null;

    /**
     * @var QueryString
     */
    private $query = null;

    /**
     * @var SessionInterface
     */
    private $session = null;

    /**
     * @var ParameterBag
     */
    private $parameters;

    /**
     * @var string
     */
    private $id;

    public function __construct(ConnectionInterface $conn)
    {
        if (isset($conn->WebSocket)) {
            $this->request = $conn->WebSocket->request;
            $this->query = $this->request->getUrl(true)->getQuery();
        }

        if (isset($conn->Session)) {
            $this->session = $conn->Session;
        }

        $sessionName = \ini_get('session.name');

        if (isset($this->request) && isset($this->request->getCookies()[$sessionName])) {
            $this->id = $this->request->getCookies()[$sessionName];
        } elseif (isset($conn->resourceId)) {
            $this->id = $conn->resourceId;
        }

        $this->parameters = new ParameterBag();
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getToken($firewall)
    {
        if (null !== $this->session) {
            return \unserialize($this->session->get('_security_' . $firewall));
        }

        return;
    }

    public function getUser($firewall)
    {
        if (null !== ($token = $this->getToken($firewall))) {
            return $token->getUser();
        }

        return;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function isValid()
    {
        return true;
    }

    public function get($name)
    {
        return $this->parameters->get($name);
    }

    public function has($name)
    {
        return $this->parameters->has($name);
    }

    public function set($name, $value)
    {
        $this->parameters->set($name, $value);
    }

    public function clear()
    {
        $this->parameters->clear();
    }

    /**
     * Returns all parameters.
     *
     * @return array
     */
    public function all()
    {
        return $this->parameters->all();
    }

    /**
     * Return id of connection context for connection.
     *
     * @return string
     */
    public static function getIdFromConnection(ConnectionInterface $conn)
    {
        $context = new self($conn);

        return $context->getId();
    }
}
