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
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\HttpCache\HandlerInvalidatePathInterface;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Listens on document-manager events and invalidates cached urls to prevent outdated
 * information in the cache.
 *
 * TODO: the url-gathering for changed documents should not be implemented in this class.
 */
class InvalidationSubscriber implements EventSubscriberInterface
{
    /**
     * @var HandlerInvalidatePathInterface
     */
    private $pathHandler;

    /**
     * @var HandlerInvalidateStructureInterface
     */
    private $structureHandler;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $environment;

    /**
     * @param HandlerInvalidatePathInterface $pathHandler
     * @param HandlerInvalidateStructureInterface $structureHandler
     * @param StructureManagerInterface $structureManager
     * @param DocumentInspector $documentInspector
     * @param ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool
     * @param WebspaceManagerInterface $webspaceManager
     * @param RequestStack $requestStack
     * @param string $environment - kernel envionment, dev, prod, etc
     */
    public function __construct(
        HandlerInvalidatePathInterface $pathHandler,
        HandlerInvalidateStructureInterface $structureHandler,
        StructureManagerInterface $structureManager,
        DocumentInspector $documentInspector,
        ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
        WebspaceManagerInterface $webspaceManager,
        RequestStack $requestStack,
        $environment
    ) {
        $this->pathHandler = $pathHandler;
        $this->structureHandler = $structureHandler;
        $this->structureManager = $structureManager;
        $this->documentInspector = $documentInspector;
        $this->resourceLocatorStrategyPool = $resourceLocatorStrategyPool;
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PUBLISH => ['invalidateDocumentBeforePublishing', 1024],
            Events::UNPUBLISH => ['invalidateDocumentBeforeUnpublishing', 1024],
            Events::REMOVE => ['invalidateDocumentBeforeRemoving', 1024],
        ];
    }

    /**
     * Invalidates the assigned structure and all urls in the locale of the document of an already published document
     * when it gets republished (eg on content change).
     * This method is executed before the actual publishing of the document to avoid purging new urls.
     *
     * @param PublishEvent $event
     */
    public function invalidateDocumentBeforePublishing(PublishEvent $event)
    {
        $document = $event->getDocument();

        if ($document instanceof StructureBehavior) {
            $this->invalidateDocumentStructure($document);
        }

        if ($document instanceof ResourceSegmentBehavior
            && $document instanceof WorkflowStageBehavior
            && $document->getPublished()
        ) {
            $this->invalidateDocumentUrls($document, $this->documentInspector->getLocale($document));
        }
    }

    /**
     * Invalidates the assigned structure and all urls in the locale of the document when a document gets unpublished.
     * This method is executed before the actual unpublishing of the document because the document must still
     * be published to gather the urls of the document.
     *
     * @param UnpublishEvent $event
     */
    public function invalidateDocumentBeforeUnpublishing(UnpublishEvent $event)
    {
        $document = $event->getDocument();

        if ($document instanceof StructureBehavior) {
            $this->invalidateDocumentStructure($document);
        }

        if ($document instanceof ResourceSegmentBehavior
            && $document instanceof WorkflowStageBehavior
            && $document->getPublished()
        ) {
            $this->invalidateDocumentUrls($document, $this->documentInspector->getLocale($document));
        }
    }

    /**
     * Invalidates the assigned structure and all urls in all locales of the document when a document gets removed.
     * This method is executed before the actual removing of the document because the document must still
     * exist to gather the urls of the document.
     *
     * @param RemoveEvent $event
     */
    public function invalidateDocumentBeforeRemoving(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if ($document instanceof StructureBehavior) {
            $this->invalidateDocumentStructure($document);
        }

        if ($document instanceof ResourceSegmentBehavior) {
            foreach ($this->documentInspector->getPublishedLocales($document) as $locale) {
                $this->invalidateDocumentUrls($document, $locale);
            }
        }
    }

    /**
     * Invalidates the structure of the given document.
     *
     * @param $document
     */
    private function invalidateDocumentStructure($document)
    {
        $structureBridge = $this->structureManager->wrapStructure(
            $this->documentInspector->getMetadata($document)->getAlias(),
            $this->documentInspector->getStructureMetadata($document)
        );
        $structureBridge->setDocument($document);

        $this->structureHandler->invalidateStructure($structureBridge);
    }

    /**
     * Invalidates all urls which are assigned to the given document in the given locale.
     *
     * @param $document
     * @param $locale
     */
    private function invalidateDocumentUrls($document, $locale)
    {
        foreach ($this->getLocaleUrls($document, $locale) as $url) {
            $this->pathHandler->invalidatePath($url);
        }
    }

    /**
     * Returns all urls of the given locale which are associated with the given document.
     * The returned array contains all current urls and all history urls.
     * The returned urls can contain placeholders (eg {host}).
     *
     * @param ResourceSegmentBehavior $document
     * @param string $locale
     *
     * @return array Urls of the given locale which are associated with the given document
     */
    private function getLocaleUrls(ResourceSegmentBehavior $document, $locale)
    {
        $uuid = ($document instanceof UuidBehavior) ? $document->getUuid() : null;
        $webspace = ($document instanceof WebspaceBehavior) ? $document->getWebspaceName() : null;
        if (!$locale || !$uuid || !$webspace) {
            return [];
        }

        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspace);

        // get current resource-locator and history resource-locators
        $resourceLocators = [];
        try {
            $resourceLocators[] = $resourceLocatorStrategy->loadByContentUuid($uuid, $webspace, $locale);
        } catch (ResourceLocatorNotFoundException $e) {
            // if no resource locator exists there is also no url to purge from the cache
        }

        $historyResourceLocators = $resourceLocatorStrategy->loadHistoryByContentUuid($uuid, $webspace, $locale);
        foreach ($historyResourceLocators as $historyResourceLocator) {
            $resourceLocators[] = $historyResourceLocator->getResourceLocator();
        }

        // get urls for resource-locators
        $urls = [];
        foreach ($resourceLocators as $resourceLocator) {
            $urls = array_merge($urls, $this->findUrlsByResourceLocator($resourceLocator, $locale, $webspace));
        }

        return $urls;
    }

    /**
     * Returns array of resource-locators with "http" and "https".
     *
     * @param string $resourceLocator
     * @param string $locale
     * @param string $webspace
     *
     * @return string[]
     */
    private function findUrlsByResourceLocator($resourceLocator, $locale, $webspace)
    {
        $scheme = 'http';
        if ($request = $this->requestStack->getCurrentRequest()) {
            $scheme = $request->getScheme();
        }

        return $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocator,
            $this->environment,
            $locale,
            $webspace,
            null,
            $scheme
        );
    }
}
