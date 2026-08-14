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
use Webmozart\Assert\Assert;

/**
 * @internal no bc-promise given, we recommend building your own metadata subscriber instead of using this class.
 *
 * Doctrine subscriber used to manipulate metadata.
 */
class MetadataSubscriber
{
    /**
     * @var list<class-string>
     */
    private $classNames;

    /**
     * @param array<string, array<string, array{
     *     model?: class-string<object>,
     *     repository?: class-string<\Doctrine\ORM\EntityRepository<object>>
     * }>> $objects
     */
    public function __construct(protected array $objects)
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
                // The following code is littered with phpstan-ignore-next-line because of doctrine/orm 2/3 compat
                // doctrine/orm 3: changed to $value->type and $value->sourceEntity (AssociationMapping object)
                // doctrine/orm 2: changed to $value['type'] and $value['sourceEntity'] (array/ArrayAccess object)

                // @phpstan-ignore-next-line argument.type
                if ($this->hasRelation($value['type'])) {
                    $value['sourceEntity'] = $metadata->getName();
                    // @phpstan-ignore-next-line
                    if (\is_array($value) || $value instanceof \Doctrine\ORM\Mapping\AssociationMapping) {
                        // @phpstan-ignore-next-line
                        $metadata->associationMappings[$key] = $value;
                    }
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
            // can be changed to $value->type if min version is doctrine/orm 3+
            // @phpstan-ignore-next-line argument.type
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
     * @return list<class-string>
     */
    private function getAllClassNames(Configuration $configuration)
    {
        if (!$this->classNames) {
            $classNames = $configuration->getMetadataDriverImpl()?->getAllClassNames();
            Assert::isList($classNames, 'Expected getAllClassNames to return a list of class names.');
            $this->classNames = $classNames;
        }

        return $this->classNames;
    }
}
