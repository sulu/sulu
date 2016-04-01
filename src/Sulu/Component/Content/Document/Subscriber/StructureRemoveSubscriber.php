<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove routes and references associated with content.
 */
class StructureRemoveSubscriber implements EventSubscriberInterface
{
    private $inspector;
    private $documentManager;
    private $metadataFactory;

    public function __construct(
        DocumentManager $documentManager,
        DocumentInspector $inspector,
        MetadataFactoryInterface $metadataFactory
    ) {
        $this->documentManager = $documentManager;
        $this->inspector = $inspector;
        $this->metadataFactory = $metadataFactory;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::REMOVE => ['handleRemove', 550],
        ];
    }

    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        $this->removeDocument($document);
    }

    public function removeDocument($document)
    {
        if ($document instanceof ChildrenBehavior) {
            foreach ($document->getChildren() as $child) {
                $this->removeDocument($child);
            }
        }

        if ($document instanceof StructureBehavior) {
            $this->removeReferences($document);
            $this->removeRoute($document);
        }
    }

    /**
     * Removes related route of given document.
     *
     * @param StructureBehavior $document
     */
    private function removeRoute(StructureBehavior $document)
    {
        foreach ($this->inspector->getReferrers($document) as $referrer) {
            if ($referrer instanceof RouteBehavior) {
                $this->documentManager->remove($referrer);
            }
        }
    }

    private function removeReferences($document)
    {
        $node = $this->inspector->getNode($document);

        $references = $node->getReferences();

        foreach ($references as $reference) {
            $referrer = $reference->getParent();
            $metadata = $this->metadataFactory->getMetadataForPhpcrNode($referrer);

            if ($metadata->getClass() === RouteDocument::class) {
                continue;
            }

            $this->dereferenceProperty($node, $reference);
        }
    }

    /**
     * Remove the given property, or the value which references the node (when
     * multi-valued).
     *
     * @param NodeInterface $node
     * @param PropertyInterface $property
     */
    private function dereferenceProperty(NodeInterface $node, PropertyInterface $property)
    {
        if (false === $property->isMultiple()) {
            $property->remove();

            return;
        }

        // dereference from multi-valued referring properties
        $values = $property->getValue();
        foreach ($values as $i => $referencedNode) {
            if ($referencedNode->getIdentifier() === $node->getIdentifier()) {
                unset($values[$i]);
            }
        }

        $property->getParent()->setProperty($property->getName(), $values);
    }
}
