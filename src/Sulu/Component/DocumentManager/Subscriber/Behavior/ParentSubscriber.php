<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\DocumentManager\Subscriber\Behavior;

use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Behavior\TimestampBehavior;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\MetadataFactory;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\Behavior\ParentBehavior;
use PHPCR\NodeInterface;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * Set the parent and children on the doucment
 */
class ParentSubscriber implements EventSubscriberInterface
{
    private $proxyFactory;
    private $dispatcher;
    private $registry;
    private $metadataFactory;

    public function __construct(
        LazyLoadingGhostFactory $proxyFactory,
        EventDispatcherInterface $dispatcher,
        DocumentRegistry $registry,
        MetadataFactory $metadataFactory
    )
    {
        $this->proxyFactory = $proxyFactory;
        $this->dispatcher = $dispatcher;
        $this->registry = $registry;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::HYDRATE => 'handleHydrate',
        );
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();
        $node = $event->getNode();

        if ($document instanceof ParentBehavior) {
            $this->mapParent($document, $node);
        }
    }

    private function mapParent($document, NodeInterface $node)
    {
        $childDocument = $document;
        $childNode = $node;
        $eventDispatcher = $this->dispatcher;
        $registry = $this->registry;
        $parentNode = $childNode->getParent();
        $parentMetadata = $this->metadataFactory->getMetadataForPhpcrNode($parentNode);

        $initializer = function (
            LazyLoadingInterface $document, 
            $method, 
            array $parameters, 
            &$initializer
        ) use (
            $childDocument,
            $parentNode,
            $eventDispatcher,
            $registry
        )
        {
            $locale = $registry->getLocaleForDocument($childDocument);

            $hydrateEvent = new HydrateEvent($parentNode, $locale);
            $hydrateEvent->setDocument($document);
            $eventDispatcher->dispatch(Events::HYDRATE, $hydrateEvent);

            $initializer = null;
        };

        $parentDocument = $this->proxyFactory->createProxy($parentMetadata->getClass(), $initializer);
        $document->setParent($parentDocument);
    }
}
