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
use Sulu\Component\Content\Document\Behavior\LocalizedContentBehavior;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Exception\MandatoryPropertyException;

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
    public static function getSubscribedEvents()
    {
        return array(
            Events::PERSIST => array(
                // persist should happen before content is mapped
                array('handlePersist', 0),
                // setting the structure should happen very early
                array('handlePersistStructureType', 100),
            ),
            // hydrate should happen afterwards
            Events::HYDRATE => array('handleHydrate', 0),
            Events::CONFIGURE_OPTIONS => 'configureOptions',
        );
    }

    /**
     * @param ConfigureOptionsEvent $event
     */
    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();
        $options->setDefaults(array(
            'hydrate.load_ghost_content' => true
        ));
        $options->setAllowedTypes(array(
            'hydrate.load_ghost_content' => 'bool',
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function supports($document)
    {
        return $document instanceof ContentBehavior;
    }

    /**
     * Set the structure type early so that subsequent subscribers operate
     * upon the correct structure type
     *
     * @param PersistEvent $event
     */
    public function handlePersistStructureType(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $structure = $this->inspector->getStructure($document);

        $propertyContainer = $document->getContent();
        if ($propertyContainer instanceof ManagedPropertyContainer) {
            $propertyContainer->setStructure($structure);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function doHydrate(HydrateEvent $event)
    {
        // Set the structure type
        $node = $event->getNode();
        $document = $event->getDocument();

        $propertyName = $this->getStructureTypePropertyName($document, $event->getLocale());
        $value = $node->getPropertyValueWithDefault($propertyName, null);
        $document->setStructureType($value);

        if (false === $event->getOption('hydrate.load_ghost_content', false)) {
            if ($this->inspector->getLocalizationState($document) === LocalizationState::GHOST) {
                $value = null;
            }
        }

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
            $this->getStructureTypePropertyName($document, $locale),
            $document->getStructureType()
        );

        $this->mapContentToNode($document, $node, $locale);
    }

    private function getStructureTypePropertyName($document, $locale)
    {
        if ($document instanceof LocalizedContentBehavior) {
            return $this->encoder->localizedSystemName(self::STRUCTURE_TYPE_FIELD, $locale);
        }

        // TODO: This is the wrong namespace, it should be the system namespcae, but we do this for initial BC
        return $this->encoder->contentName(self::STRUCTURE_TYPE_FIELD);
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

        foreach ($structure->getModelProperties() as $propertyName => $structureProperty) {
            $contentTypeName = $structureProperty->getContentTypeName();
            $contentType = $this->contentTypeManager->get($contentTypeName);

            // TODO: Only write if the property has been modified.

            $legacyProperty = $this->legacyPropertyFactory->createTranslatedProperty($structureProperty, $locale);
            $realProperty = $propertyContainer->getProperty($propertyName);

            $value = $realProperty->getValue();

            if ($structureProperty->isRequired() && empty($value)) {
                throw new MandatoryPropertyException(sprintf(
                    'Property "%s" in structure "%s" is required but no value was given. Loaded from "%s"',
                    $propertyName,
                    $structure->getName(),
                    $structure->resource
                ));
            }

            $legacyProperty->setValue($value);

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
