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
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;

class ContentSubscriber extends AbstractMappingSubscriber
{
    const STRUCTURE_TYPE_FIELD = 'template';

    private $contentTypeManager;
    private $structureFactory;
    private $metadataFactory;
    private $documentInspector;

    /**
     * @param PropertyEncoder $encoder<
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param StructureFactory $structureFactory
     */
    public function __construct(
        PropertyEncoder $encoder,
        ContentTypeManagerInterface $contentTypeManager,
        DocumentMetadataFactory $metadataFactory,
        StructureFactory $structureFactory,
        DocumentInspector $documentInspector
    )
    {
        parent::__construct($encoder);
        $this->contentTypeManager = $contentTypeManager;
        $this->structureFactory = $structureFactory;
        $this->metadataFactory = $metadataFactory;
        $this->documentInspector = $documentInspector;
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
        $propertyName = $this->encoder->localizedSystemName(self::STRUCTURE_TYPE_FIELD, $event->getLocale());
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

        $this->mapContentToNode($document, $node);
    }

    /**
     * @param mixed $document
     * @param NodeInterface $node
     */
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

    /**
     * @param mixed $document
     */
    private function getStructure($document)
    {
        $documentAlias = $this->metadataFactory->getMetadataForClass(get_class($document))->getAlias();
        return $this->structureFactory->getStructure($documentAlias, $document->getStructureType());
    }

    /**
     * Map to the content properties to the node using the content types
     *
     * @param mixed $document
     * @param NodeInterface $node
     */
    private function mapContentToNode($document, NodeInterface $node)
    {
        $propertyContainer = $document->getContent();
        $structure = $this->getStructure($document);
        $webspaceName = $this->documentInspector->getWebspace($document);
        $locale = $this->documentInspector->getLocale($document);

        // If the content container is managed, then update the structure
        // as it may have changed.
        if ($propertyContainer instanceof ManagedPropertyContainer) {
            $propertyContainer->setStructure($structure);
        }

        // Document title is mandatory
        // TODO: This is duplicated inthe TitleSubscriber
        $document->getContent()->getProperty('title')->setValue($document->getTitle());

        foreach ($structure->getChildren() as $propertyName => $structureProperty) {
            $contentTypeName = $structureProperty->getContentTypeName();
            $contentType = $this->contentTypeManager->get($contentTypeName);

            $phpcrName = $this->encoder->fromProperty($structureProperty, $locale);

            $realProperty = $propertyContainer->getProperty($propertyName);
            $property = new Property($phpcrName, $document);

            // TODO: This is a hack to avoid breaking the content type API
            $property->setStructureProperty($structureProperty);

            $property->setValue($realProperty->getValue());

            $contentType->write(
                $node,
                $property,
                null,
                $webspaceName,
                $locale,
                null
            );
        }
    }
}
