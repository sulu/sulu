<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Templating;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wrapper for templating engine.
 */
class EventAwareEngine implements EngineInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * Indicates if the engine was initiliazed.
     *
     * @var bool
     */
    private $initialized = false;

    public function __construct(EngineInterface $engine, EventDispatcherInterface $eventDispatcher)
    {
        $this->engine = $engine;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = [])
    {
        $this->initialize();

        return $this->engine->render($name, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function renderResponse($view, array $parameters = [], Response $response = null)
    {
        $this->initialize();

        return $this->engine->renderResponse($view, $parameters, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        $this->initialize();

        return $this->engine->exists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        $this->initialize();

        return $this->engine->supports($name);
    }

    /**
     * Throw the event initialize once.
     */
    private function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $this->eventDispatcher->dispatch(EngineEvents::INITIALIZE);
        $this->initialized = true;
    }
}
