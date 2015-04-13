<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;

class RouteSubscriber extends AbstractMappingSubscriber
{
    const DOCUMENT_TARGET_FIELD = 'content';

    private $proxyFactory;
    private $documentRegistry;

    /**
     * @param PropertyEncoder  $encoder
     * @param DocumentAccessor $accessor
     * @param ProxyFactory     $proxyFactory
     */
    public function __construct(
        PropertyEncoder $encoder,
        ProxyFactory $proxyFactory,
        DocumentRegistry $documentRegistry
    ) {
        parent::__construct($encoder);
        $this->proxyFactory = $proxyFactory;
        $this->documentRegistry = $documentRegistry;
    }

    public function supports($document)
    {
        return $document instanceof RouteBehavior;
    }

    /**
     * @param HydrateEvent $event
     */
    public function doHydrate(AbstractMappingEvent $event)
    {
        $node = $event->getNode();
        $document = $event->getDocument();

        $targetNode = $node->getPropertyValueWithDefault(
            $this->encoder->systemName(self::DOCUMENT_TARGET_FIELD),
            null
        );

        $targetDocument = null;
        if ($targetNode) {
            $targetDocument = $this->proxyFactory->createProxyForNode($document, $targetNode);
        }

        $document->setTargetDocument($targetDocument);
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $node = $event->getNode();
        $document = $event->getDocument();
        $targetDocument = $document->getTargetDocument();

        $targetNode = null;
        if ($targetDocument) {
            $targetNode = $this->documentRegistry->getNodeForDocument($targetDocument);
        }

        $node->setProperty(
            $this->encoder->systemName(self::DOCUMENT_TARGET_FIELD),
            $targetNode
        );
    }
}
