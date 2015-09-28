<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\StructureTypeFilingBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\AbstractFilingSubscriber;
use Sulu\Component\DocumentManager\Events;

/**
 * Automatically set the parent at a pre-determined location.
 */
class StructureTypeFilingSubscriber extends AbstractFilingSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['handlePersist', 485],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function generatePath(PersistEvent $event)
    {
        $document = $event->getDocument();

        $currentPath = $this->basePath;
        if ($event->hasParentNode()) {
            $currentPath = $event->getParentNode()->getPath();
        }
        $parentName = $this->getParentName($document);

        return sprintf('%s/%s', $currentPath, $parentName);
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($document)
    {
        return $document instanceof StructureTypeFilingBehavior;
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentName($document)
    {
        return $document->getStructureType();
    }
}
