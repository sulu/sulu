<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Metadata;

use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadataInterface;
use Metadata\Driver\DriverInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Bundle\SearchBundle\Search\SuluSearchEvents;
use Sulu\Bundle\SearchBundle\Search\Event\StructureMetadataLoadEvent;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Compat\PropertyInterface;
use Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;
use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentStructureFactory;
use Sulu\Component\Content\Structure\Factory\StructureFactory;
use Sulu\Component\Content\Structure\Block;
use Sulu\Component\Content\Document\ContentInstanceFactory;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;

/**
 * Provides a Metadata Driver for massive search-bundle
 */
class StructureDriver implements DriverInterface
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var DocumentStructureFactory
     */
    private $structureFactory;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    public function __construct(
        Factory $factory, 
        MetadataFactory $metadataFactory,
        StructureFactory $structureFactory
    )
    {
        $this->factory = $factory;
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
    }

    /**
     * Loads metadata for a given class if its derived from StructureInterface
     *
     * @param \ReflectionClass $class
     * @throws \InvalidArgumentException
     * @return IndexMetadataInterface|null
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        if (!ContentInstanceFactory::isWrapped($class->name)) {
            return;
        }

        $indexMeta = $this->factory->makeIndexMetadata($class->name);
        $documentMeta = $this->metadataFactory->getMetadataForClass(ContentInstanceFactory::getRealName($class->name));
        $structureType = ContentInstanceFactory::getStructureType($class->name);

        $structure = $this->structureFactory->getStructure($documentMeta->getAlias(), $structureType);

        $indexMeta->setIndexName('content');
        $indexMeta->setIdField('uuid');
        $indexMeta->setLocaleField('locale');

        foreach ($structure->getModelProperties() as $property) {

            if ($property instanceof Block) {
                $propertyMapping = new ComplexMetadata();
                foreach ($property->getComponents() as $component) {
                    $index = 0;
                    foreach ($component->getChildren() as $componentProperty) {
                        $indexMeta->addFieldMapping(
                            'content.' . $property->getName() . '.value[' . $index++ . '][' . $componentProperty->getName() .']',
                            array(
                                'type' => 'string',
                            )
                        );
                    }
                }
            } else {
                $this->mapProperty($property, $indexMeta);
            }
        }

        if ($structure->hasPropertyWithTagName('sulu.rlp')) {
            $prop = $structure->getPropertyByTagName('sulu.rlp');
            $indexMeta->setUrlField($this->getPropertyName($prop));
        }

        if ($class->isSubclassOf(TitleBehavior::class) && !$indexMeta->getTitleField()) {
            $indexMeta->addFieldMapping(
                'title',
                array(
                    'type' => 'string',
                )
            );
        }

        if ($class->isSubclassOf(WebspaceBehavior::class)) {
            // index the webspace
            $indexMeta->addFieldMapping('webspaceName', array('type' => 'string'));
        }

        return $indexMeta;
    }

    private function mapProperty(Property $property, $metadata)
    {
        if (!$property->hasTag('sulu.search.field')) {
            return;
        }

        $tag = $property->getTag('sulu.search.field');
        $tagAttributes = $tag['attributes'];

        if ($metadata instanceof ClassMetadata && isset($tagAttributes['role'])) {
            switch ($tagAttributes['role']) {
                case 'title':
                    $metadata->setTitleField($property->getName());
                    $metadata->addFieldMapping($this->getPropertyName($property), array('type' => 'string'));
                    break;
                case 'description':
                    $metadata->setDescriptionField($this->getPropertyName($property));
                    $metadata->addFieldMapping($this->getPropertyName($property), array('type' => 'string'));
                    break;
                case 'image':
                    $metadata->setImageUrlField($this->getPropertyName($property));
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Unknown search field role "%s", role must be one of "%s"',
                            $tagAttributes['role'],
                            implode(', ', array('title', 'description', 'image'))
                        )
                    );
            }

            return;
        }

        if (!isset($tagAttributes['index']) || $tagAttributes['index'] !== 'false') {
            $metadata->addFieldMapping(
                $this->getPropertyName($property),
                array(
                    'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                )
            );
        }
    }

    private function getPropertyName(Property $property)
    {
        return 'content.' . $property->getName() . '.value';
    }
}
