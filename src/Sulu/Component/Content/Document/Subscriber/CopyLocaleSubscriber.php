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
use Sulu\Component\Content\Metadata\PropertyMetadata;
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
    public const PAGE_TREE_ROUTE_TYPE = 'page_tree_route';

    public const ROUTE_PROPERTY = 'routePath';

    public function __construct(
        private DocumentManagerInterface $documentManager,
        private DocumentInspector $documentInspector,
        private ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
    ) {
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

        $destLocale = $event->getDestLocale();
        $uuid = $document->getUuid();

        $webspaceKey = null;
        $resourceLocatorStrategy = null;
        if ($document instanceof WebspaceBehavior) {
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
            $documentStructure = $document->getStructure()->toArray();

            $destDocument->setStructureType($document->getStructureType());
            $destDocument->getStructure()->bind($documentStructure);
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

        $this->documentManager->persist($destDocument, $destLocale, ['omit_modified_domain_event' => true]);

        $event->setDestDocument($destDocument);
    }

    /**
     * Returns encoded "routePath" property-name.
     */
    public function getPageTreeRoutePropertyName(StructureBehavior $document): string
    {
        $metadata = $this->documentInspector->getStructureMetadata($document);

        if (null === $metadata) {
            return self::ROUTE_PROPERTY;
        }

        $properties = $metadata->getProperties();

        /** @var PropertyMetadata $property */
        foreach ($properties as $property) {
            if (self::PAGE_TREE_ROUTE_TYPE === $property->getName()) {
                return $property->getName();
            }
        }

        return self::ROUTE_PROPERTY;
    }
}
