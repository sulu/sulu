<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\EventSubscriber\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ReflectionService;

/**
 * @internal
 */
class MetadataSubscriber
{
    /**
     * @var array
     */
    private $classNames;

    /**
     * Constructor.
     *
     * @param array $objects
     */
    public function __construct(protected $objects)
    {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();

        $this->process($metadata);

        if (!$metadata->isMappedSuperclass) {
            $em = $event->getEntityManager();
            $this->setAssociationMappings(
                $metadata,
                $em->getConfiguration(),
                $em->getMetadataFactory()->getReflectionService()
            );
        } else {
            $this->unsetAssociationMappings($metadata);
        }
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function process(ClassMetadata $metadata): void
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
     * @param ClassMetadata<object> $metadata
     */
    private function setAssociationMappings(
        ClassMetadata $metadata,
        Configuration $configuration,
        ReflectionService $reflectionService
    ): void {
        if (!\class_exists($metadata->getName())) {
            return;
        }

        foreach (\class_parents($metadata->getName()) as $parent) {
            if (!\in_array($parent, $this->getAllClassNames($configuration))) {
                continue;
            }

            $parentMetadata = new ClassMetadata($parent, $configuration->getNamingStrategy());
            $parentMetadata->initializeReflection($reflectionService);

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
     * @param ClassMetadata<object> $metadata
     */
    private function unsetAssociationMappings(ClassMetadata $metadata): void
    {
        foreach ($metadata->getAssociationMappings() as $key => $value) {
            if ($this->hasRelation($value['type'])) {
                unset($metadata->associationMappings[$key]);
            }
        }
    }

    /**
     * @param int $type
     */
    private function hasRelation($type): bool
    {
        return \in_array(
            $type,
            [
                ClassMetadata::MANY_TO_MANY,
                ClassMetadata::ONE_TO_MANY,
                ClassMetadata::ONE_TO_ONE,
            ],
            true
        );
    }

    /**
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
