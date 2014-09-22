<?php

namespace Sulu\Bundle\SearchBundle\Search\Metadata;

use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadataInterface;
use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Bundle\SearchBundle\Search\SuluSearchEvents;
use Sulu\Bundle\SearchBundle\Search\Event\StructureMetadataLoadEvent;

/**
 * Provides a Metadata Driver for massive search-bundle
 * @package Sulu\Bundle\SearchBundle\Metadata
 */
class StructureDriver implements DriverInterface
{
    protected $factory;
    protected $eventDispatcher;

    public function __construct(Factory $factory, EventDispatcherInterface $eventDispatcher)
    {
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * loads metadata for a given class if its derived from StructureInterface
     * @param \ReflectionClass $class
     * @return IndexMetadataInterface|null
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        if (!$class->implementsInterface('Sulu\Component\Content\StructureInterface')) {
            return null;
        }

        if ($class->isAbstract()) {
            return null;
        }

        /** @var StructureInterface $structure */
        $structure = $class->newInstance();

        $indexMeta = $this->factory->makeIndexMetadata($class->name);

        $indexMeta->setIndexName('content');
        $indexMeta->setIdField('uuid');
        $indexMeta->setLocaleField('languageCode');

        foreach ($structure->getProperties(true) as $property) {
            if ($property->hasTag('sulu.search.field')) {
                $tag = $property->getTag('sulu.search.field');
                $tagAttrs = $tag->getAttributes();

                if ((isset($tagAttrs['index']) && $tagAttrs['index'] !== 'false') || !isset($tagAttrs['index'])) {
                    $indexMeta->addFieldMapping($property->getName(), array(
                        'type' => isset($tagAttrs['type']) ? $tagAttrs['type'] : 'string',
                    ));
                }

                if (isset($tagAttrs['role'])) {
                    switch ($tagAttrs['role']) {
                        case 'title':
                            $indexMeta->setTitleField($property->getName());
                            break;
                        case 'description':
                            $indexMeta->setDescriptionField($property->getName());
                            break;
                        case 'image':
                            $indexMeta->setImageUrlField($property->getName());
                            break;
                        default:
                            throw new \InvalidArgumentException(sprintf(
                                'Unknown search field role "%s", role must be one of "%s"',
                                $tagAttrs['role'], implode(', ', array('title', 'description', 'image'))
                            ));
                    }
                }
            }
        }

        if ($structure->hasTag('sulu.rlp')) {
            $prop = $structure->getPropertyByTagName('sulu.rlp');
            $indexMeta->setUrlField($prop->getName());
        }

        if (!$indexMeta->getTitleField()) {
            if ($structure->hasTag('sulu.node.name')) {
                $prop = $structure->getPropertyByTagName('sulu.node.name');
                $indexMeta->setTitleField($prop->getName());

                $indexMeta->addFieldMapping($prop->getName(), array(
                    'type' => 'string',
                ));
            }
        }

        $this->eventDispatcher->dispatch(
            SuluSearchEvents::STRUCTURE_LOAD_METADATA, new StructureMetadataLoadEvent($structure, $indexMeta)
        );

        return $indexMeta;
    }
}
