<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Phpcr;

use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\NodeHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles the document reorder operation.
 */
class ReorderSubscriber implements EventSubscriberInterface
{
    /**
     * @var NodeHelperInterface
     */
    private $nodeHelper;

    public function __construct(NodeHelperInterface $nodeHelper)
    {
        $this->nodeHelper = $nodeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::REORDER => ['handleReorder', 500],
        ];
    }

    /**
     * Handle the reorder operation.
     *
     * @param ReorderEvent $event
     *
     * @throws DocumentManagerException
     */
    public function handleReorder(ReorderEvent $event)
    {
        $this->nodeHelper->reorder($event->getNode(), $event->getDestId());
    }
}
