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
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\Property\Property;
use Sulu\Component\Content\Document\Structure\ManagedStructure;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\Content\Exception\MandatoryPropertyException;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StructureSubscriber implements EventSubscriberInterface
{
    const STRUCTURE_TYPE_FIELD = 'template';

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var LegacyPropertyFactory
     */
    private $legacyPropertyFactory;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @param PropertyEncoder             $encoder
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param DocumentInspector $inspector
     * @param LegacyPropertyFactory $legacyPropertyFactory
     */
    public function __construct(
        PropertyEncoder $encoder,
        ContentTypeManagerInterface $contentTypeManager,
        DocumentInspector $inspector,
        LegacyPropertyFactory $legacyPropertyFactory
    ) {
        $this->encoder = $encoder;
        $this->contentTypeManager = $contentTypeManager;
        $this->inspector = $inspector;
        $this->legacyPropertyFactory = $legacyPropertyFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [
                // persist should happen before content is mapped
                ['handlePersist', 0],
                // staged properties must be commited before title subscriber
                ['handlePersistStagedProperties', 50],
                // setting the structure should happen very early
                ['handlePersistStructureType', 100],
            ],
            // hydrate should happen afterwards
            Events::HYDRATE => ['handleHydrate', 0],
            Events::CONFIGURE_OPTIONS => 'configureOptions',
        ];
    }

    /**
     * @param ConfigureOptionsEvent $event
     */
    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();
        $options->setDefaults(
            [
                'load_ghost_content' => true,
                'clear_missing_content' => false,
                'ignore_required' => false,
            ]
        );
        $options->setAllowedTypes(
            [
                'load_ghost_content' => 'bool',
                'clear_missing_content' => 'bool',
                'ignore_required' => 'bool',
            ]
        );
    }

    /**
     * Set the structure type early so that subsequent subscribers operate
     * upon the correct structure type.
     *
     * @param PersistEvent $event
     */
    public function handlePersistStructureType(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supportsBehavior($document)) {
            return;
        }

        $structureMetadata = $this->inspector->getStructureMetadata($document);

        $structure = $document->getStructure();
        if ($structure instanceof ManagedStructure) {
            $structure->setStructureMetadata($structureMetadata);
        }
    }

    /**
     * Commit the properties, which are only staged on the structure yet.
     *
     * @param PersistEvent $event
     */
    public function handlePersistStagedProperties(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supportsBehavior($document)) {
            return;
        }

        $document->getStructure()->commitStagedData($event->getOption('clear_missing_content'));
    }

    /**
     * {@inheritdoc}
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supportsBehavior($document)) {
            return;
        }

        $node = $event->getNode();
        $propertyName = $this->getStructureTypePropertyName($document, $event->getLocale());
        $structureType = $node->getPropertyValueWithDefault($propertyName, null);
        $document->setStructureType($structureType);

        if (false === $event->getOption('load_ghost_content', false)) {
            if ($this->inspector->getLocalizationState($document) === LocalizationState::GHOST) {
                $structureType = null;
            }
        }

        $container = $this->getStructure($document, $structureType);

        // Set the property container
        $event->getAccessor()->set(
            'structure',
            $container
        );
    }

    /**
     * {@inheritdoc}
     */
    public function handlePersist(PersistEvent $event)
    {
        // Set the structure type
        $document = $event->getDocument();

        if (!$this->supportsBehavior($document)) {
            return;
        }

        if (!$document->getStructureType()) {
            return;
        }

        if (!$event->getLocale()) {
            return;
        }

        $node = $event->getNode();
        $locale = $event->getLocale();
        $options = $event->getOptions();

        $this->mapContentToNode($document, $node, $locale, $options['ignore_required']);

        $node->setProperty(
            $this->getStructureTypePropertyName($document, $locale),
            $document->getStructureType()
        );
    }

    private function supportsBehavior($document)
    {
        return $document instanceof StructureBehavior;
    }

    private function getStructureTypePropertyName($document, $locale)
    {
        if ($document instanceof LocalizedStructureBehavior) {
            return $this->encoder->localizedSystemName(self::STRUCTURE_TYPE_FIELD, $locale);
        }

        // TODO: This is the wrong namespace, it should be the system namespcae, but we do this for initial BC
        return $this->encoder->contentName(self::STRUCTURE_TYPE_FIELD);
    }

    /**
     * @param mixed $document
     *
     * @return ManagedStructure
     */
    private function createStructure($document)
    {
        return new ManagedStructure(
            $this->contentTypeManager,
            $this->legacyPropertyFactory,
            $this->inspector,
            $document
        );
    }

    /**
     * Map to the content properties to the node using the content types.
     *
     * @param mixed $document
     * @param NodeInterface $node
     * @param string $locale
     * @param bool $ignoreRequired
     *
     * @throws MandatoryPropertyException
     */
    private function mapContentToNode($document, NodeInterface $node, $locale, $ignoreRequired)
    {
        $structure = $document->getStructure();
        $webspaceName = $this->inspector->getWebspace($document);
        $metadata = $this->inspector->getStructureMetadata($document);

        foreach ($metadata->getProperties() as $propertyName => $structureProperty) {
            $realProperty = $structure->getProperty($propertyName);
            $value = $realProperty->getValue();

            if (false === $ignoreRequired && $structureProperty->isRequired() && null === $value) {
                throw new MandatoryPropertyException(
                    sprintf(
                        'Property "%s" in structure "%s" is required but no value was given. Loaded from "%s"',
                        $propertyName,
                        $metadata->getName(),
                        $metadata->resource
                    )
                );
            }

            $contentTypeName = $structureProperty->getContentTypeName();
            $contentType = $this->contentTypeManager->get($contentTypeName);

            // TODO: Only write if the property has been modified.

            $legacyProperty = $this->legacyPropertyFactory->createTranslatedProperty($structureProperty, $locale);
            $legacyProperty->setValue($value);

            $contentType->remove(
                $node,
                $legacyProperty,
                $webspaceName,
                $locale,
                null
            );

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

    /**
     * Return the a structure for the document.
     *
     * - If the Structure already exists on the document, use that.
     * - If the Structure type is given, then create a ManagedStructure - this
     *   means that the structure is already persisted on the node and it has data.
     * - If none of the above applies then create a new, empty, Structure.
     *
     * @param object $document
     * @param string $structureType
     *
     * @return StructureInterface
     */
    private function getStructure($document, $structureType)
    {
        if ($structureType) {
            return $this->createStructure($document);
        }

        if ($document->getStructure()) {
            return $document->getStructure();
        }

        return new Structure();
    }
}
