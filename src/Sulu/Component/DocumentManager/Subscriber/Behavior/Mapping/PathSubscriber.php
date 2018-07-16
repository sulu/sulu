<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set the path on the document.
 */
class PathSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @param DocumentInspector $documentInspector
     */
    public function __construct(DocumentInspector $documentInspector)
    {
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [
                ['setInitialPath', 0],
                ['setFinalPath', -495],
            ],
            Events::HYDRATE => 'setFinalPath',
        ];
    }

    /**
     * Sets the path at the beginning of persisting.
     *
     * @param AbstractMappingEvent $event
     */
    public function setInitialPath(AbstractMappingEvent $event)
    {
        $this->setPath($event);
    }

    /**
     * Sets the path at the very end, in case the path has been changed in the persisting process.
     *
     * @param AbstractMappingEvent $event
     */
    public function setFinalPath(AbstractMappingEvent $event)
    {
        $this->setPath($event);
    }

    /**
     * @param AbstractMappingEvent $event
     */
    private function setPath(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof PathBehavior) {
            return;
        }

        $event->getAccessor()->set('path', $this->documentInspector->getPath($document));
    }
}
