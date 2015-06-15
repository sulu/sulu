<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\EventSubscriber\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Doctrine subscriber used to manipulate metadata.
 */
class MetadataSubscriber implements EventSubscriber
{
    /**
     * @var array
     */
    protected $objects;

    /**
     * Constructor
     *
     * @param array $objects
     */
    public function __construct($objects)
    {
        $this->objects = $objects;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::loadClassMetadata,
        );
    }

    /**
     * Load the class data, mapping the created and changed fields
     * to datetime fields.
     *
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $event->getClassMetadata();

        $this->process($metadata);

        if (!$metadata->isMappedSuperclass) {
            $this->setAssociationMappings($metadata, $event->getEntityManager()->getConfiguration());
        } else {
            $this->unsetAssociationMappings($metadata);
        }
    }

    private function process(ClassMetadataInfo $metadata)
    {
        foreach ($this->objects as $application => $classes) {
            foreach ($classes as $class) {
                if (isset($class['model']) && $class['model'] === $metadata->getName()) {
                    $metadata->isMappedSuperclass = false;

                    if (isset($class['repository'])) {
                        $metadata->setCustomRepositoryClass($class['repository']);
                    }
                }
            }
        }
    }

    private function setAssociationMappings(ClassMetadataInfo $metadata, Configuration $configuration)
    {
        foreach (class_parents($metadata->getName()) as $parent) {
            $parentMetadata = new ClassMetadata(
                $parent,
                $configuration->getNamingStrategy()
            );

            if (!in_array($parent, $configuration->getMetadataDriverImpl()->getAllClassNames())) {
                continue;
            }

            $configuration->getMetadataDriverImpl()->loadMetadataForClass($parent, $parentMetadata);
            if (!$parentMetadata->isMappedSuperclass) {
                continue;
            }

            // map relations
            foreach ($parentMetadata->getAssociationMappings() as $key => $value) {
                if ($this->hasRelation($value['type'])) {
                    $metadata->associationMappings[$key] = $value;
                }
            }
        }
    }

    private function unsetAssociationMappings(ClassMetadataInfo $metadata)
    {
        foreach ($metadata->getAssociationMappings() as $key => $value) {
            if ($this->hasRelation($value['type'])) {
                unset($metadata->associationMappings[$key]);
            }
        }
    }

    private function hasRelation($type)
    {
        return in_array(
            $type,
            array(
                ClassMetadataInfo::MANY_TO_MANY,
                ClassMetadataInfo::ONE_TO_MANY,
                ClassMetadataInfo::ONE_TO_ONE,
            ),
            true
        );
    }
}
