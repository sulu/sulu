<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Search\Metadata;

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\Field\Expression;
use Massive\Bundle\SearchBundle\Search\Metadata\Field\Value;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadataInterface;
use Massive\Bundle\SearchBundle\Search\Metadata\ProviderInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\ItemMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Metadata\MetadataFactory;

/**
 * Provides a Metadata Driver for massive search-bundle.
 */
class StructureProvider implements ProviderInterface
{
    public const FIELD_STRUCTURE_TYPE = '_structure_type';

    public const FIELD_TEASER_DESCRIPTION = '_teaser_description';

    public const FIELD_TEASER_MEDIA = '_teaser_media';

    public const FIELD_WEBSPACE_KEY = 'webspace_key';

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var string
     */
    private $mapping;

    /**
     * @var StructureMetadataFactoryInterface
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

    public function __construct(
        Factory $factory,
        MetadataFactory $metadataFactory,
        StructureMetadataFactoryInterface $structureFactory,
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

        $documentMetadata = $this->metadataFactory->getMetadataForClass(\get_class($object));
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
        $indexMeta->setLocaleField($this->factory->createMetadataField('originalLocale'));

        $indexName = 'page';
        $decorate = false;

        // See if the mapping overrides the default index and category name
        foreach ($this->mapping as $className => $mapping) {
            if ($documentMetadata->getAlias() !== $className
                && $class->name !== $className
                && false === $class->isSubclassOf($className)
            ) {
                continue;
            }

            $indexName = $mapping['index'];
            if ($mapping['decorate_index']) {
                $decorate = true;
            }
        }

        $indexMeta->setIndexName($this->createIndexNameField($documentMetadata, $indexName, $decorate));

        foreach ($structure->getProperties() as $property) {
            $this->mapProperty($property, $indexMeta);
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
            $field = $this->factory->createMetadataField('resourceSegment');
            if ($class->isSubclassOf(RedirectTypeBehavior::class)) {
                $expression = <<<'EOT'
                    (object.getRedirectType() === %s
                        ? (object.getRedirectTarget() ? object.getRedirectTarget().getResourceSegment())
                        : (object.getRedirectType() === %s
                            ? object.getRedirectExternal()
                            : object.getResourceSegment()
                        )
                    )
EOT;

                $field = new Expression(
                    \sprintf(
                        $expression,
                        RedirectType::INTERNAL,
                        RedirectType::EXTERNAL
                    )
                );
            }

            $indexMeta->setUrlField($field);
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
                static::FIELD_WEBSPACE_KEY,
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

        if ($class->isSubclassOf(LocalizedAuthorBehavior::class)) {
            $indexMeta->addFieldMapping(
                'authored',
                [
                    'type' => 'date',
                    'field' => $this->factory->createMetadataExpression(
                        'object.getAuthored()'
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

    /**
     * @param IndexMetadata|ComplexMetadata $metadata
     * @param string|null $condition
     */
    private function mapProperty(ItemMetadata $property, $metadata, string $prefix = '', $condition = null)
    {
        $propertyName = $prefix . $property->getName();

        if ($metadata instanceof IndexMetadata) {
            $field = $this->factory->createMetadataExpression(
                \sprintf(
                    'object.getStructure().%s.getValue()',
                    $property->getName()
                ),
                $condition
            );
        } else {
            $field = $this->factory->createMetadataProperty(
                '[' . $property->getName() . ']',
                $condition
            );
        }

        if ($property instanceof BlockMetadata) {
            $propertyMapping = new ComplexMetadata();

            foreach ($property->getComponents() as $component) {
                /** @var \Sulu\Component\Content\Metadata\PropertyMetadata $componentProperty */
                foreach ($component->getChildren() as $componentProperty) {
                    $this->mapProperty(
                        $componentProperty,
                        $propertyMapping,
                        $component->getName() . '_',
                        'type === \'' . $component->getName() . '\' && massive_search_value("[settings][schedules_enabled]", false) !== true && massive_search_value("[settings][hidden]", false) !== true'
                    );
                }
            }

            // add field for block only if block contains least one mapped field
            if (!empty($propertyMapping->getFieldMapping())) {
                $metadata->addFieldMapping(
                    $propertyName,
                    [
                        'type' => 'complex',
                        'mapping' => $propertyMapping,
                        'field' => $field,
                    ]
                );
            }

            return;
        }

        if ($metadata instanceof IndexMetadata && $property->hasTag('sulu.teaser.description')) {
            $metadata->addFieldMapping(
                self::FIELD_TEASER_DESCRIPTION,
                [
                    'type' => 'string',
                    'field' => $field,
                    'aggregate' => true,
                    'indexed' => false,
                ]
            );
        }
        if ($metadata instanceof IndexMetadata && $property->hasTag('sulu.teaser.media')) {
            $metadata->addFieldMapping(
                self::FIELD_TEASER_MEDIA,
                [
                    'type' => 'json',
                    'field' => $field,
                    'aggregate' => true,
                    'indexed' => false,
                ]
            );
        }

        if (false === $property->hasTag('sulu.search.field')) {
            return;
        }

        $tag = $property->getTag('sulu.search.field');
        $tagAttributes = $tag['attributes'];

        if ($metadata instanceof IndexMetadata && isset($tagAttributes['role'])) {
            switch ($tagAttributes['role']) {
                case 'title':
                    $metadata->setTitleField($field);
                    $metadata->addFieldMapping(
                        $propertyName,
                        [
                            'field' => $field,
                            'type' => 'string',
                            'aggregate' => true,
                            'indexed' => false,
                        ]
                    );
                    break;
                case 'description':
                    $metadata->setDescriptionField($field);
                    $metadata->addFieldMapping(
                        $propertyName,
                        [
                            'field' => $field,
                            'type' => 'string',
                            'aggregate' => true,
                            'indexed' => false,
                        ]
                    );
                    break;
                case 'image':
                    $metadata->setImageUrlField($field);
                    break;
                default:
                    throw new \InvalidArgumentException(
                        \sprintf(
                            'Unknown search field role "%s", role must be one of "%s"',
                            $tagAttributes['role'],
                            \implode(', ', ['title', 'description', 'image'])
                        )
                    );
            }

            return;
        }

        if (!isset($tagAttributes['index']) || 'false' !== $tagAttributes['index']) {
            $metadata->addFieldMapping(
                $propertyName,
                [
                    'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                    'field' => $field,
                    'aggregate' => true,
                    'indexed' => isset($tagAttributes['index']) && 'indexed' === $tagAttributes['index'],
                ]
            );
        }
    }

    private function createIndexNameField(Metadata $documentMetadata, $indexName, $decorate)
    {
        if (!$decorate) {
            return new Value($indexName);
        }

        $expression = '"' . $indexName . '"';
        if ($documentMetadata->getReflectionClass()->isSubclassOf(WebspaceBehavior::class)) {
            $expression .= '~"_"~object.getWebspaceName()';
        }
        if ($documentMetadata->getReflectionClass()->isSubclassOf(WorkflowStageBehavior::class)) {
            $expression .= '~(object.getWorkflowStage() == ' . WorkflowStage::PUBLISHED . ' ? "_published" : "")';
        }

        return new Expression($expression);
    }
}
