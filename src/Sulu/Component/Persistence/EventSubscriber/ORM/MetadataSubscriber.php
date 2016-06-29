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
     * @var array
     */
    private $classNames;

    /**
     * Constructor.
     *
     * @param array $objects
     */
    public function __construct($objects)
    {
        $this->objects = $objects;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    /**
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

    /**
     * @param ClassMetadataInfo $metadata
     */
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

    /**
     * @param ClassMetadataInfo $metadata
     * @param Configuration     $configuration
     */
    private function setAssociationMappings(ClassMetadataInfo $metadata, Configuration $configuration)
    {
        if (!class_exists($metadata->getName())) {
            return;
        }

        foreach (class_parents($metadata->getName()) as $parent) {
            $parentMetadata = new ClassMetadata(
                $parent,
                $configuration->getNamingStrategy()
            );

            if (!in_array($parent, $this->getAllClassNames($configuration))) {
                continue;
            }

            $configuration->getMetadataDriverImpl()->loadMetadataForClass($parent, $parentMetadata);
            if (!$parentMetadata->isMappedSuperclass) {
                continue;
            }

            // map relations
            foreach ($parentMetadata->getAssociationMappings() as $key => $value) {
                if ($this->hasRelation($value['type'])) {
                    $value['sourceEntity'] = $metadata->getName();
                    $metadata->associationMappings[$key] = $value;
                }
            }
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     */
    private function unsetAssociationMappings(ClassMetadataInfo $metadata)
    {
        foreach ($metadata->getAssociationMappings() as $key => $value) {
            if ($this->hasRelation($value['type'])) {
                unset($metadata->associationMappings[$key]);
            }
        }
    }

    /**
     * @param $type
     *
     * @return bool
     */
    private function hasRelation($type)
    {
        return in_array(
            $type,
            [
                ClassMetadataInfo::MANY_TO_MANY,
                ClassMetadataInfo::ONE_TO_MANY,
                ClassMetadataInfo::ONE_TO_ONE,
            ],
            true
        );
    }

    /**
     * @param Configuration $configuration
     *
     * @return array
     */
    private function getAllClassNames(Configuration $configuration)
    {
        if (!$this->classNames) {
            $this->classNames = $configuration->getMetadataDriverImpl()->getAllClassNames();
        }

        return $this->classNames;
    }
}
