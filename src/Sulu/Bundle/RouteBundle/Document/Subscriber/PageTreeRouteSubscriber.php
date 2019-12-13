<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\RouteBundle\PageTree\PageTreeUpdaterInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles relation between articles and pages.
 */
class PageTreeRouteSubscriber implements EventSubscriberInterface
{
    const ROUTE_PROPERTY = 'routePath';

    const TAG_NAME = 'sulu_article.article_route';

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var PropertyEncoder
     */
    protected $propertyEncoder;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var SessionInterface
     */
    protected $liveSession;

    /**
     * @var PageTreeUpdaterInterface
     */
    protected $routeUpdater;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param PropertyEncoder $propertyEncoder
     * @param DocumentInspector $documentInspector
     * @param StructureMetadataFactoryInterface $metadataFactory
     * @param SessionInterface $liveSession
     * @param PageTreeUpdaterInterface $routeUpdater
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        PropertyEncoder $propertyEncoder,
        DocumentInspector $documentInspector,
        StructureMetadataFactoryInterface $metadataFactory,
        SessionInterface $liveSession,
        PageTreeUpdaterInterface $routeUpdater
    ) {
        $this->documentManager = $documentManager;
        $this->propertyEncoder = $propertyEncoder;
        $this->documentInspector = $documentInspector;
        $this->metadataFactory = $metadataFactory;
        $this->liveSession = $liveSession;
        $this->routeUpdater = $routeUpdater;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // should be called before the live resource-segment will be updated
            Events::PUBLISH => ['handlePublish', 10],
            // should be called after the live resource-segment was be updated
            Events::MOVE => ['handleMove', -1000],
        ];
    }

    /**
     * Update route-paths of articles which are linked to the given page-document.
     *
     * @param AbstractMappingEvent $event
     */
    public function handlePublish(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof PageDocument || !$this->hasChangedResourceSegment($document)) {
            return;
        }

        $this->routeUpdater->update($document);
    }

    /**
     * Update route-paths of articles which are linked to the given page-document.
     *
     * @param MoveEvent $event
     */
    public function handleMove(MoveEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof PageDocument) {
            return;
        }

        foreach ($this->documentInspector->getLocales($document) as $locale) {
            $this->routeUpdater->update($this->documentManager->find($document->getUuid(), $locale));
        }
    }

    /**
     * Returns true if the resource-segment was changed in the draft page.
     *
     * @param PageDocument $document
     *
     * @return bool
     */
    private function hasChangedResourceSegment(PageDocument $document)
    {
        $metadata = $this->metadataFactory->getStructureMetadata('page', $document->getStructureType());

        $urlProperty = $metadata->getPropertyByTagName('sulu.rlp');
        $urlPropertyName = $this->propertyEncoder->localizedContentName(
            $urlProperty->getName(),
            $document->getLocale()
        );

        $liveNode = $this->getLiveNode($document);
        $url = $liveNode->getPropertyValueWithDefault($urlPropertyName, null);

        return $url && $url !== $document->getResourceSegment();
    }

    /**
     * Returns the live node for given document.
     *
     * @param PathBehavior $document
     *
     * @return NodeInterface
     */
    private function getLiveNode(PathBehavior $document)
    {
        return $this->liveSession->getNode($document->getPath());
    }
}
