<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Core;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Event\CreateEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responsible for instantiating documents from PHPCR nodes and
 * setting the document in the event so that other listeners can
 * take further actions (such as hydrating it for example).
 *
 * NOTE: This should always be the first thing to be called
 */
class InstantiatorSubscriber implements EventSubscriberInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(
        MetadataFactoryInterface $metadataFactory
    ) {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => ['handleHydrate', 500],
            Events::CREATE => ['handleCreate', 500],
        ];
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        // don't need to instantiate the document if it is already existing.
        if ($event->hasDocument()) {
            return;
        }

        $node = $event->getNode();

        $document = $this->getDocumentFromNode($node);
        $event->setDocument($document);
    }

    /**
     * @param mixed $event
     */
    public function handleCreate(CreateEvent $event)
    {
        $metadata = $this->metadataFactory->getMetadataForAlias($event->getAlias());
        $document = $this->instantiateFromMetadata($metadata);
        $event->setDocument($document);
    }

    /**
     * Instantiate a new document. The class is determined from
     * the mixins present in the PHPCR node for legacy reasons.
     *
     * @param NodeInterface $node
     *
     * @return object
     */
    private function getDocumentFromNode(NodeInterface $node)
    {
        $metadata = $this->metadataFactory->getMetadataForPhpcrNode($node);

        return $this->instantiateFromMetadata($metadata);
    }

    /**
     * @param Metadata $metadata
     *
     * @return object
     */
    private function instantiateFromMetadata(Metadata $metadata)
    {
        $class = $metadata->getClass();

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf(
                'Document class "%s" does not exist', $class
            ));
        }

        $document = new $class();

        return $document;
    }
}
