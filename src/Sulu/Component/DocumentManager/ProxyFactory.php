<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;
use Sulu\Component\DocumentManager\Collection\ReferrerCollection;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handle creation of proxies.
 */
class ProxyFactory
{
    /**
     * @var LazyLoadingGhostFactory
     */
    private $proxyFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var DocumentRegistry
     */
    private $registry;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @param LazyLoadingGhostFactory $proxyFactory
     * @param EventDispatcherInterface $dispatcher
     * @param DocumentRegistry $registry
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(
        LazyLoadingGhostFactory $proxyFactory,
        EventDispatcherInterface $dispatcher,
        DocumentRegistry $registry,
        MetadataFactoryInterface $metadataFactory
    ) {
        $this->proxyFactory = $proxyFactory;
        $this->dispatcher = $dispatcher;
        $this->registry = $registry;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Create a new proxy object from the given document for
     * the given target node.
     *
     * TODO: We only pass the document here in order to correctly evaluate its locale
     *       later. I wonder if it necessary.
     *
     * @param object $fromDocument
     * @param NodeInterface $targetNode
     * @param array $options
     *
     * @return \ProxyManager\Proxy\GhostObjectInterface
     */
    public function createProxyForNode($fromDocument, NodeInterface $targetNode, $options = [])
    {
        // if node is already registered then just return the registered document
        $locale = $this->registry->getOriginalLocaleForDocument($fromDocument);
        if ($this->registry->hasNode($targetNode, $locale)) {
            $document = $this->registry->getDocumentForNode($targetNode, $locale);

            // If the parent is not loaded in the correct locale, reload it in the correct locale.
            if ($this->registry->getOriginalLocaleForDocument($document) !== $locale) {
                $hydrateEvent = new HydrateEvent($targetNode, $locale);
                $hydrateEvent->setDocument($document);
                $this->dispatcher->dispatch(Events::HYDRATE, $hydrateEvent);
            }

            return $document;
        }

        $initializer = function (LazyLoadingInterface $document, $method, array $parameters, &$initializer) use (
            $fromDocument,
            $targetNode,
            $options,
            $locale
        ) {
            $hydrateEvent = new HydrateEvent($targetNode, $locale, $options);
            $hydrateEvent->setDocument($document);
            $this->dispatcher->dispatch(Events::HYDRATE, $hydrateEvent);

            $initializer = null;
        };

        $targetMetadata = $this->metadataFactory->getMetadataForPhpcrNode($targetNode);
        $proxy = $this->proxyFactory->createProxy($targetMetadata->getClass(), $initializer);
        $locale = $this->registry->getOriginalLocaleForDocument($fromDocument);
        $this->registry->registerDocument($proxy, $targetNode, $locale);

        return $proxy;
    }

    /**
     * Create a new children collection for the given document.
     *
     * @param object $document
     *
     * @return ChildrenCollection
     */
    public function createChildrenCollection($document, array $options = [])
    {
        $node = $this->registry->getNodeForDocument($document);
        $locale = $this->registry->getOriginalLocaleForDocument($document);

        return new ChildrenCollection(
            $node,
            $this->dispatcher,
            $locale,
            $options
        );
    }

    /**
     * Create a new collection of referrers from a list of referencing items.
     *
     * @param $document
     *
     * @return ReferrerCollection
     */
    public function createReferrerCollection($document)
    {
        $node = $this->registry->getNodeForDocument($document);
        $locale = $this->registry->getOriginalLocaleForDocument($document);

        return new ReferrerCollection(
            $node,
            $this->dispatcher,
            $locale
        );
    }
}
