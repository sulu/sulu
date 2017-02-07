<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\Metadata;

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\Field\Expression;
use Massive\Bundle\SearchBundle\Search\Metadata\Field\Value;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadataInterface;
use Massive\Bundle\SearchBundle\Search\Metadata\ProviderInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Metadata\MetadataFactory;

/**
 * Provides a Metadata Driver for massive search-bundle.
 */
class StructureProvider implements ProviderInterface
{
    const FIELD_STRUCTURE_TYPE = '_structure_type';
    const FIELD_TEASER_DESCRIPTION = '_teaser_description';
    const FIELD_TEASER_MEDIA = '_teaser_media';

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var string
     */
    private $mapping;

    /**
     * @var StructureMetadataFactory
     */
    private $structureFactory;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    /**
     * @param Factory $factory
     * @param MetadataFactory $metadataFactory
     * @param StructureMetadataFactory $structureFactory
     * @param ExtensionManagerInterface $extensionManager
     * @param array $mapping
     */
    public function __construct(
        Factory $factory,
        MetadataFactory $metadataFactory,
        StructureMetadataFactory $structureFactory,
        ExtensionManagerInterface $extensionManager,
        array $mapping = []
    ) {
        $this->factory = $factory;
        $this->mapping = $mapping;
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
        $this->extensionManager = $extensionManager;
    }

    /**
     * loads metadata for a given class if its derived from StructureInterface.
     *
     * @param object $object
     *
     * @return IndexMetadataInterface|null
     */
    public function getMetadataForObject($object)
    {
        if (!$object instanceof StructureBehavior) {
            return;
        }

        $documentMetadata = $this->metadataFactory->getMetadataForClass(get_class($object));
        $structure = $this->structureFactory->getStructureMetadata(
            $documentMetadata->getAlias(),
            $object->getStructureType()
        );

        return $this->getMetadata($documentMetadata, $structure);
    }

    public function getMetadata(Metadata $documentMetadata, StructureMetadata $structure)
    {
        $classMetadata = $this->factory->createClassMetadata($documentMetadata->getClass());
        $class = $documentMetadata->getReflectionClass();

        $indexMeta = $this->factory->createIndexMetadata();
        $indexMeta->setIdField($this->factory->createMetadataField('uuid'));
        $indexMeta->setLocaleField($this->factory->createMetadataField('locale'));

        $indexName = 'page';

        // See if the mapping overrides the default index and category name
        foreach ($this->mapping as $className => $mapping) {
            if ($documentMetadata->getAlias() !== $className &&
                $class->name !== $className &&
                false === $class->isSubclassOf($className)
            ) {
                continue;
            }

            $indexName = $mapping['index'];
        }

        if ($indexName === 'page') {
            $indexMeta->setIndexName(
                new Expression(
                    sprintf(
                        '"page_"~object.getWebspaceName()~(object.getWorkflowStage() == %s ? "_published" : "")',
                        WorkflowStage::PUBLISHED
                    )
                )
            );
        } else {
            $indexMeta->setIndexName(new Value($indexName));
        }

        foreach ($structure->getProperties() as $property) {
            if ($property instanceof BlockMetadata) {
                $propertyMapping = new ComplexMetadata();
                foreach ($property->getComponents() as $component) {
                    foreach ($component->getChildren() as $componentProperty) {
                        if (false === $componentProperty->hasTag('sulu.search.field')) {
                            continue;
                        }

                        $tag = $componentProperty->getTag('sulu.search.field');
                        $tagAttributes = $tag['attributes'];

                        if (!isset($tagAttributes['index']) || $tagAttributes['index'] !== 'false') {
                            $propertyMapping->addFieldMapping(
                                $property->getName() . '.' . $componentProperty->getName(),
                                [
                                    'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                                    'field' => $this->factory->createMetadataProperty(
                                        '[' . $componentProperty->getName() . ']'
                                    ),
                                    'aggregate' => true,
                                    'indexed' => false,
                                ]
                            );
                        }
                    }
                }

                $indexMeta->addFieldMapping(
                    $property->getName(),
                    [
                        'type' => 'complex',
                        'mapping' => $propertyMapping,
                        'field' => $this->getContentField($property),
                    ]
                );
            } else {
                $this->mapProperty($property, $indexMeta);
            }
        }

        if ($class->isSubclassOf(ExtensionBehavior::class)) {
            $extensions = $this->extensionManager->getExtensions($structure->getName());
            foreach ($extensions as $extension) {
                foreach ($extension->getFieldMapping() as $name => $mapping) {
                    $indexMeta->addFieldMapping($name, $mapping);
                }
            }
        }

        if ($class->isSubclassOf(ResourceSegmentBehavior::class)) {
            $indexMeta->setUrlField($this->factory->createMetadataField('resourceSegment'));
        }

        if (!$indexMeta->getTitleField()) {
            if ($class->isSubclassOf(TitleBehavior::class)) {
                $indexMeta->setTitleField($this->factory->createMetadataProperty('title'));

                $indexMeta->addFieldMapping(
                    'title',
                    [
                        'type' => 'string',
                        'field' => $this->factory->createMetadataField('title'),
                        'aggregate' => true,
                        'indexed' => false,
                    ]
                );
            }
        }

        if ($class->isSubclassOf(WebspaceBehavior::class)) {
            // index the webspace
            $indexMeta->addFieldMapping(
                'webspace_key',
                [
                    'type' => 'string',
                    'field' => $this->factory->createMetadataProperty('webspaceName'),
                ]
            );
        }

        if ($class->isSubclassOf(WorkflowStageBehavior::class)) {
            $indexMeta->addFieldMapping(
                'state',
                [
                    'type' => 'string',
                    'field' => $this->factory->createMetadataExpression(
                        'object.getWorkflowStage() == 1 ? "test" : "published"'
                    ),
                ]
            );
            $indexMeta->addFieldMapping(
                'published',
                [
                    'type' => 'date',
                    'field' => $this->factory->createMetadataExpression(
                        'object.getPublished()'
                    ),
                ]
            );
        }

        $indexMeta->addFieldMapping(
            self::FIELD_STRUCTURE_TYPE,
            [
                'type' => 'string',
                'stored' => true,
                'indexed' => true,
                'field' => $this->factory->createMetadataProperty('structureType'),
            ]
        );

        $classMetadata->addIndexMetadata('_default', $indexMeta);

        return $classMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata()
    {
        $metadatas = [];
        foreach ($this->metadataFactory->getAliases() as $alias) {
            $metadata = $this->metadataFactory->getMetadataForAlias($alias);

            if (!$this->structureFactory->hasStructuresFor($alias)) {
                continue;
            }

            foreach ($this->structureFactory->getStructures($alias) as $structure) {
                $structureMetadata = $this->getMetadata($metadata, $structure);
                $metadatas[] = $structureMetadata;
            }
        }

        return $metadatas;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForDocument(Document $document)
    {
        if (!$document->hasField(self::FIELD_STRUCTURE_TYPE)) {
            return;
        }

        $className = $document->getClass();
        $structureType = $document->getField(self::FIELD_STRUCTURE_TYPE)->getValue();
        $documentMetadata = $this->metadataFactory->getMetadataForClass($className);
        $structure = $this->structureFactory->getStructureMetadata($documentMetadata->getAlias(), $structureType);

        return $this->getMetadata($documentMetadata, $structure);
    }

    private function mapProperty(PropertyMetadata $property, $metadata)
    {
        if ($metadata instanceof IndexMetadata && $property->hasTag('sulu.teaser.description')) {
            $this->mapTeaserDescription($property, $metadata);
        }
        if ($metadata instanceof IndexMetadata && $property->hasTag('sulu.teaser.media')) {
            $this->mapTeaserMedia($property, $metadata);
        }

        if (false === $property->hasTag('sulu.search.field')) {
            return;
        }

        $tag = $property->getTag('sulu.search.field');
        $tagAttributes = $tag['attributes'];

        if ($metadata instanceof IndexMetadata && isset($tagAttributes['role'])) {
            switch ($tagAttributes['role']) {
                case 'title':
                    $metadata->setTitleField($this->getContentField($property));
                    $metadata->addFieldMapping(
                        $property->getName(),
                        [
                            'field' => $this->getContentField($property),
                            'type' => 'string',
                            'aggregate' => true,
                            'indexed' => false,
                        ]
                    );
                    break;
                case 'description':
                    $metadata->setDescriptionField($this->getContentField($property));
                    $metadata->addFieldMapping(
                        $property->getName(),
                        [
                            'field' => $this->getContentField($property),
                            'type' => 'string',
                            'aggregate' => true,
                            'indexed' => false,
                        ]
                    );
                    break;
                case 'image':
                    $metadata->setImageUrlField($this->getContentField($property));
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Unknown search field role "%s", role must be one of "%s"',
                            $tagAttributes['role'],
                            implode(', ', ['title', 'description', 'image'])
                        )
                    );
            }

            return;
        }

        if (!isset($tagAttributes['index']) || $tagAttributes['index'] !== 'false') {
            $metadata->addFieldMapping(
                $property->getName(),
                [
                    'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                    'field' => $this->getContentField($property),
                    'aggregate' => true,
                    'indexed' => false,
                ]
            );
        }
    }

    private function getContentField(PropertyMetadata $property)
    {
        $field = $this->factory->createMetadataExpression(
            sprintf(
                'object.getStructure().%s.getValue()',
                $property->getName()
            )
        );

        return $field;
    }

    private function mapTeaserDescription(PropertyMetadata $property, IndexMetadata $metadata)
    {
        $metadata->addFieldMapping(
            self::FIELD_TEASER_DESCRIPTION,
            [
                'type' => 'string',
                'field' => $this->getContentField($property),
                'aggregate' => true,
                'indexed' => false,
            ]
        );
    }

    private function mapTeaserMedia(PropertyMetadata $property, IndexMetadata $metadata)
    {
        $metadata->addFieldMapping(
            self::FIELD_TEASER_MEDIA,
            [
                'type' => 'json',
                'field' => $this->getContentField($property),
                'aggregate' => true,
                'indexed' => false,
            ]
        );
    }
}
