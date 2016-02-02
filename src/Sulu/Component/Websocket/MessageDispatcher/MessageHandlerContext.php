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

use Sulu\Component\Websocket\ConnectionContext\ConnectionContextInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Message handler connection context.
 *
 * It holds detail information about the upgrade request of the websocket connection, the handler name and the user can
 * put his own session variables into a parameter bag.
 */
class MessageHandlerContext implements ConnectionContextInterface
{
    /**
     * @var ConnectionContextInterface
     */
    protected $context;

    /**
     * @var string
     */
    private $handlerName;

    /**
     * @var ParameterBag
     */
    private $parameters;

    public function __construct(ConnectionContextInterface $context, $handlerName)
    {
        $this->context = $context;
        $this->handlerName = $handlerName;

        $parameterName = 'parameters.' . $handlerName;
        if (!$this->context->has($parameterName)) {
            $this->context->set($parameterName, new ParameterBag());
        }

        $this->parameters = $this->context->get($parameterName);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->context->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->context->getRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function getSession()
    {
        return $this->context->getSession();
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($firewall)
    {
        return $this->context->getToken($firewall);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($firewall)
    {
        return $this->context->getUser($firewall);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->context->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return $this->context->isValid();
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
    public function all()
    {
        return $this->parameters->all();
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        $this->parameters->set($name, $value);
    }

    /**
     * Clear all parameter.
     */
    public function clear()
    {
        $this->parameters->clear();
    }
}
