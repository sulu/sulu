<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
 * TODO: Performance -- try fetch depth like in the PHPCR-ODM ChildrenCollection
 */
class ChildrenCollection extends AbstractLazyCollection
{
    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @param string $locale
     * @param array $options
     */
    public function __construct(
        private NodeInterface $parentNode,
        private EventDispatcherInterface $dispatcher,
        private $locale,
        private $options = []
    ) {
    }

    public function current()
    {
        $this->initialize();
        $childNode = $this->documents->current();

        $hydrateEvent = new HydrateEvent($childNode, $this->locale, $this->options);
        $this->dispatcher->dispatch($hydrateEvent, Events::HYDRATE);

        return $hydrateEvent->getDocument();
    }

    protected function initialize()
    {
        if (true === $this->initialized) {
            return;
        }

        $this->documents = $this->parentNode->getNodes();
        $this->initialized = true;
    }
}
