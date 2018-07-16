<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Collection;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Lazily hydrate query results.
 *
 * TODO: Performance -- try fetch depth like in teh PHPCR-ODM ChildrenCollection
 */
class ChildrenCollection extends AbstractLazyCollection
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var array
     */
    private $options;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @param NodeInterface $parentNode
     * @param EventDispatcherInterface $dispatcher
     * @param string $locale
     * @param array $options
     */
    public function __construct(
        NodeInterface $parentNode,
        EventDispatcherInterface $dispatcher,
        $locale,
        $options = []
    ) {
        $this->parentNode = $parentNode;
        $this->dispatcher = $dispatcher;
        $this->locale = $locale;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $this->initialize();
        $childNode = $this->documents->current();

        $hydrateEvent = new HydrateEvent($childNode, $this->locale, $this->options);
        $this->dispatcher->dispatch(Events::HYDRATE, $hydrateEvent);

        return $hydrateEvent->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        if (true === $this->initialized) {
            return;
        }

        $this->documents = $this->parentNode->getNodes();
        $this->initialized = true;
    }
}
