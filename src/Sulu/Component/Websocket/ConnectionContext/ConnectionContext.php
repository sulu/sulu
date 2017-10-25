<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\ConnectionContext;

use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

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
     * @var array
     */
    private $query = null;

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
        $this->id = uniqid('', true);
        $this->parameters = new ParameterBag();

        if (!isset($conn->httpRequest)) {
            if (isset($conn->resourceId)) {
                $this->id = $conn->resourceId;
            }

            return;
        }

        $this->request = $conn->httpRequest;
        parse_str($this->request->getUri()->getQuery(), $this->query);

        $sessionName = ini_get('session.name');

        $cookies = $this->parseCookies($this->request->getHeader('cookie')[0]);
        if (array_key_exists($sessionName, $cookies)) {
            $this->id = $cookies[$sessionName];
        }
    }

    /**
     * Parses given cookie-line.
     *
     * @param string $cookieLine
     *
     * @return array
     */
    private function parseCookies($cookieLine)
    {
        $pieces = array_filter(array_map('trim', explode(';', $cookieLine)));

        $cookies = [];
        foreach ($pieces as $piece) {
            if (empty($piece) || !strpos($piece, '=')) {
                continue;
            }

            $pieceParts = explode('=', $piece);
            $cookies[$pieceParts[0]] = $pieceParts[1];
        }

        return $cookies;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        return $this->parameters->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return $this->parameters->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        $this->parameters->set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
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
     * @param ConnectionInterface $conn
     *
     * @return string
     */
    public static function getIdFromConnection(ConnectionInterface $conn)
    {
        $context = new self($conn);

        return $context->getId();
    }
}
