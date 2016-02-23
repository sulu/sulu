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
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Remove routes and references associated with content.
 */
class StructureRemoveSubscriber implements EventSubscriberInterface
{
    private $metadataFactory;

    public function __construct(
        MetadataFactoryInterface $metadataFactory
    ) {
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
        $this->removeDocument($event->getContext()->getDocumentManager(), $document);
    }

    public function removeDocument(DocumentManagerInterface $documentManager, $document)
    {
        // TODO: This is not a good indicator. There should be a RoutableBehavior here.
        if (!$document instanceof StructureBehavior) {
            return;
        }

        if ($document instanceof ChildrenBehavior) {
            foreach ($document->getChildren() as $child) {
                $this->removeDocument($documentManager, $child);
            }
        }

        $this->removeReferences($documentManager->getInspector(), $document);
        $this->recursivelyRemoveRoutes($documentManager, $document);
    }

    private function recursivelyRemoveRoutes(
        DocumentManagerInterface $documentManager, 
        $document
    )
    {
        $referrers = $documentManager->getInspector()->getReferrers($document);

        foreach ($referrers as $document) {
            if ($document instanceof RouteDocument) {
                $this->recursivelyRemoveRoutes($documentManager, $document);
                $documentManager->remove($document);
                continue;
            }
        }
    }

    private function removeReferences(DocumentInspector $inspector, $document)
    {
        $node = $inspector->getNode($document);

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
     * @param NodeInterface     $node
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
