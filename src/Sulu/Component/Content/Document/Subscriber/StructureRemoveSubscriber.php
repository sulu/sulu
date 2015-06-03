<?php

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;

/**
 * Remove routes and references associated with content
 */
class StructureRemoveSubscriber implements EventSubscriberInterface
{
    private $inspector;
    private $documentManager;
    private $metadataFactory;

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $inspector,
        MetadataFactoryInterface $metadataFactory
    ) {
        $this->documentManager = $documentManager;
        $this->inspector = $inspector;
        $this->metadataFactory = $metadataFactory;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::REMOVE => array('handleRemove', 550),
        );
    }

    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        // TODO: This is not a good indicator. There should be a RoutableBehavior here.
        if (!$document instanceof StructureBehavior) {
            return;
        }

        $this->removeReferences($document);
        $this->recursivelyRemoveRoutes($document);
    }

    private function recursivelyRemoveRoutes($document)
    {
        $referrers = $this->inspector->getReferrers($document);

        foreach ($referrers as $document) {
            if ($document instanceof RouteDocument) {
                $this->recursivelyRemoveRoutes($document);
                $this->documentManager->remove($document);
                continue;
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
