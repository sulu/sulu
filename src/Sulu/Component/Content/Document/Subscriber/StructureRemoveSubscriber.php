<?php

/*
 * This file is part of the Sulu.
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
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove routes and references associated with content.
 */
class StructureRemoveSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var MetadataFactoryInterface
     */
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::CONFIGURE_OPTIONS => ['configureOptions', 0],
            Events::REMOVE => ['handleRemove', 550],
        ];
    }

    /**
     * Adds the options for this subscriber to the OptionsResolver.
     *
     * @param ConfigureOptionsEvent $event
     */
    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();

        $options->setDefault('dereference', false);
        $options->addAllowedTypes('dereference', 'bool');
    }

    /**
     * Removes the references to the given node, if the dereference option is set.
     *
     * @param RemoveEvent $event
     */
    public function handleRemove(RemoveEvent $event)
    {
        if ($event->getOption('dereference')) {
            $document = $event->getDocument();
            $this->removeReferencesToDocument($document);
        }
    }

    /**
     * Removes the references from the given document.
     *
     * @param object $document
     */
    private function removeReferencesToDocument($document)
    {
        // TODO: This is not a good indicator. There should be a RoutableBehavior here.
        if (!$document instanceof StructureBehavior) {
            return;
        }

        if ($document instanceof ChildrenBehavior) {
            foreach ($document->getChildren() as $child) {
                $this->removeReferencesToDocument($child);
            }
        }

        $this->removeReferences($document);
        $this->recursivelyRemoveRoutes($document);
    }

    /**
     * Removes all routes from the given Document.
     *
     * @param object $document
     */
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

    /**
     * Removes all the references to the given document.
     *
     * @param object $document
     */
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
