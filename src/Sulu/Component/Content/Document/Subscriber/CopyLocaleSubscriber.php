<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\CopyLocaleEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CopyLocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool
    ) {
        $this->resourceLocatorStrategyPool = $resourceLocatorStrategyPool;
        $this->documentInspector = $documentInspector;
        $this->documentManager = $documentManager;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::COPY_LOCALE => 'handleCopyLocale',
        ];
    }

    public function handleCopyLocale(CopyLocaleEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof UuidBehavior) {
            return;
        }

        $destLocales = $event->getDestLocales();
        $uuid = $document->getUuid();

        $webspaceKey = null;
        $resourceLocatorStrategy = null;
        if ($document instanceof ResourceSegmentBehavior && $document instanceof WebspaceBehavior) {
            $webspaceKey = $document->getWebspaceName();
            $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);
        }

        $parentUuid = null;
        if ($document instanceof ParentBehavior) {
            $parentDocument = $this->documentInspector->getParent($document);

            if (null !== $parentDocument) {
                $parentUuid = $this->documentInspector->getUuid($parentDocument);
            }
        }

        foreach ($destLocales as $destLocale) {
            $destDocument = $this->documentManager->find(
                $uuid,
                $destLocale
            );

            if ($destDocument instanceof LocaleBehavior) {
                $destDocument->setLocale($destLocale);
            }

            if ($destDocument instanceof TitleBehavior && $document instanceof TitleBehavior) {
                $destDocument->setTitle($document->getTitle());
            }

            if ($destDocument instanceof StructureBehavior && $document instanceof StructureBehavior) {
                $destDocument->setStructureType($document->getStructureType());
                $destDocument->getStructure()->bind($document->getStructure()->toArray());
            }

            if ($destDocument instanceof WorkflowStageBehavior) {
                $documentAccessor = new DocumentAccessor($destDocument);
                $documentAccessor->set(WorkflowStageSubscriber::PUBLISHED_FIELD, null);
            }

            if ($destDocument instanceof ExtensionBehavior && $document instanceof ExtensionBehavior) {
                $destDocument->setExtensionsData($document->getExtensionsData());
            }

            // TODO: This can be removed if RoutingAuto replaces the ResourceLocator code.
            if ($destDocument instanceof ResourceSegmentBehavior
                && $destDocument instanceof TitleBehavior
                && null !== $resourceLocatorStrategy
                && null !== $parentUuid
                && null !== $webspaceKey) {
                $resourceLocator = $resourceLocatorStrategy->generate(
                    $destDocument->getTitle(),
                    $parentUuid,
                    $webspaceKey,
                    $destLocale
                );

                $destDocument->setResourceSegment($resourceLocator);
            }

            $this->documentManager->persist($destDocument, $destLocale);
        }
    }
}
