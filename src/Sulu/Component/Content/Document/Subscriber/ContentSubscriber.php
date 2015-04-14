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
use Sulu\Component\Content\Structure\Factory\StructureFactory;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Document\Property\PropertyContainer;
use Sulu\Component\Content\Document\Property\ManagedPropertyContainer;
use Sulu\Component\Content\Document\Property\Property;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;

class ContentSubscriber extends AbstractMappingSubscriber
{
    const STRUCTURE_TYPE_FIELD = 'template';

    private $contentTypeManager;
    private $inspector;
    private $legacyPropertyFactory;

    /**
     * @param PropertyEncoder $encoder<
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param StructureFactory $structureFactory
     */
    public function __construct(
        PropertyEncoder $encoder,
        ContentTypeManagerInterface $contentTypeManager,
        DocumentInspector $inspector,
        LegacyPropertyFactory $legacyPropertyFactory
    )
    {
        parent::__construct($encoder);
        $this->contentTypeManager = $contentTypeManager;
        $this->inspector = $inspector;
        $this->legacyPropertyFactory = $legacyPropertyFactory;
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
            $container = $this->createPropertyContainer($document);
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

        if (!$document->getStructureType()) {
            return;
        }

        $node = $event->getNode();
        $locale = $event->getLocale();
        $node->setProperty(
            $this->encoder->localizedSystemName('template', $locale),
            $document->getStructureType()
        );

        $this->mapContentToNode($document, $node, $locale);
    }

    /**
     * @param mixed $document
     * @param NodeInterface $node
     */
    private function createPropertyContainer($document)
    {
        return new ManagedPropertyContainer(
            $this->contentTypeManager,
            $this->legacyPropertyFactory,
            $this->inspector,
            $document
        );
    }

    /**
     * Map to the content properties to the node using the content types
     *
     * @param mixed $document
     * @param NodeInterface $node
     */
    private function mapContentToNode($document, NodeInterface $node, $locale)
    {
        $propertyContainer = $document->getContent();
        $webspaceName = $this->inspector->getWebspace($document);
        $structure = $this->inspector->getStructure($document);

        if ($propertyContainer instanceof ManagedPropertyContainer) {
            $propertyContainer->setStructure($structure);
        }

        foreach ($structure->getProperties(true) as $propertyName => $structureProperty) {
            $contentTypeName = $structureProperty->getContentTypeName();
            $contentType = $this->contentTypeManager->get($contentTypeName);

            $legacyProperty = $this->legacyPropertyFactory->createTranslatedProperty($structureProperty, $locale);
            $realProperty = $propertyContainer->getProperty($propertyName);
            $legacyProperty->setValue($realProperty->getValue());

            $contentType->write(
                $node,
                $legacyProperty,
                null,
                $webspaceName,
                $locale,
                null
            );
        }
    }
}
