<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\EventSubscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvalidationSubscriber implements EventSubscriberInterface
{
    /**
     * @var HandlerInvalidateStructureInterface
     */
    private $handler;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @param HandlerInvalidateStructureInterface $handler
     * @param StructureManagerInterface $structureManager
     * @param DocumentInspector $documentInspector
     */
    public function __construct(
        HandlerInvalidateStructureInterface $handler,
        StructureManagerInterface $structureManager,
        DocumentInspector $documentInspector
    ) {
        $this->handler = $handler;
        $this->structureManager = $structureManager;
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PUBLISH => ['invalidateDocumentForPublishing', -512],
        ];
    }

    public function invalidateDocumentForPublishing(PublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof StructureBehavior) {
            return;
        }

        $structureBridge = $this->structureManager->wrapStructure(
            $this->documentInspector->getMetadata($document)->getAlias(),
            $this->documentInspector->getStructureMetadata($document)
        );

        $structureBridge->setDocument($document);

        $this->handler->invalidateStructure($structureBridge);
    }
}
