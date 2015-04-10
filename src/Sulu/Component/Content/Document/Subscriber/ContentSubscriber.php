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

use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\DocumentManager\Event\AbstractDocumentNodeEvent;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\MetadataFactory as DocumentMetadataFactory;
use Sulu\Component\Content\Type\ContentTypeManagerInterface;
use Sulu\Component\Content\Structure\Factory\StructureFactory;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Document\Property\PropertyContainer;
use Sulu\Component\Content\Document\Property\ManagedPropertyContainer;
use Sulu\Component\Content\Document\Property\Property;

class ContentSubscriber extends AbstractMappingSubscriber
{
    private $contentTypeManager;
    private $structureFactory;
    private $documentMetadataFactory;

    /**
     * @param PropertyEncoder $encoder<
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param StructureFactory $structureFactory
     */
    public function __construct(
        PropertyEncoder $encoder,
        ContentTypeManagerInterface $contentTypeManager,
        DocumentMetadataFactory $documentMetadataFactory,
        StructureFactory $structureFactory
    )
    {
        parent::__construct($encoder);
        $this->contentTypeManager = $contentTypeManager;
        $this->structureFactory = $structureFactory;
        $this->documentMetadataFactory = $documentMetadataFactory;
    }

    /**
     * {@inheritDoc}
     */
    protected function supports($document)
    {
        return $document instanceof ContentBehavior;
    }

    /**
     * {@inheritDoc}
     */
    public function doHydrate(HydrateEvent $event)
    {
        // Set the structure type
        $node = $event->getNode();
        $document = $event->getDocument();
        $propertyName = $this->encoder->localizedSystemName('template', $event->getLocale());
        $value = $node->getPropertyValueWithDefault($propertyName, null);

        $document->setStructureType($value);

        if ($value) {
            $container = $this->createPropertyContainer($document, $node);
        } else {
            $container = new PropertyContainer();
        }

        // Set the property container
        $event->getAccessor()->set(
            'content',
            $container
        );
    }

    /**
     * {@inheritDoc}
     */
    public function doPersist(PersistEvent $event)
    {
        // Set the structure type
        $document = $event->getDocument();
        $node = $event->getNode();

        if (!$document->getStructureType()) {
            return;
        }

        $node->setProperty(
            $this->encoder->localizedSystemName('template', $event->getLocale()),
            $document->getStructureType()
        );

        // Map the content to the node
        $structure = $this->getStructure($document);

        // Document title is mandatory
        $document->getContent()->getProperty('title')->setValue($document->getTitle());

        foreach ($structure->getChildren() as $propertyName => $structureProperty) {
            $contentTypeName = $structureProperty->getContentTypeName();
            $contentType = $this->contentTypeManager->get($contentTypeName);
            $document->getContent()->getProperty($propertyName);

            // TODO: The following logic is duplicated in the ManagedPropertyContainer
            if (true === $structureProperty->isLocalized()) {
                $locale = $document->getLocale();
                $phpcrName = $this->encoder->localizedContentName($propertyName, $locale);
            } else {
                $phpcrName = $this->encoder->contentname($propertyName);
            }

            $realProperty = $document->getContent()->getProperty($propertyName);
            $property = new Property($phpcrName, $document);

            $property->setValue($realProperty->getValue());

            $contentType->write(
                $node,
                $property,
                null,
                null,
                null,
                null
            );
        }
    }

    private function createPropertyContainer($document, NodeInterface $node)
    {
        return new ManagedPropertyContainer(
            $this->contentTypeManager,
            $node,
            $this->encoder,
            $this->getStructure($document),
            $document
        );
    }

    private function getStructure($document)
    {
        $documentAlias = $this->documentMetadataFactory->getMetadataForClass(get_class($document))->getAlias();
        return $this->structureFactory->getStructure($documentAlias, $document->getStructureType());
    }
}
