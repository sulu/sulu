<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Doctrine\Hydrator;

use Doctrine\ORM\Mapping\InverseSideMapping;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator;

/**
 * Works around Gedmo's TreeObjectHydrator crashing on ORM 3.x. When the tree entity is
 * a concrete subclass of a mapped-superclass, Gedmo iterates `getReflectionProperties()`
 * and reads `mappedBy` on owning-side associations (which throw `OutOfRangeException`
 * because the property only exists on inverse-side mappings).
 *
 * Upstream issue: https://github.com/doctrine-extensions/DoctrineExtensions/issues/2921
 * Upstream fix (open PR): https://github.com/doctrine-extensions/DoctrineExtensions/pull/3041
 *
 * @internal
 *
 * @phpstan-ignore class.extendsFinalByPhpDoc
 */
final class SafeTreeObjectHydrator extends TreeObjectHydrator
{
    protected function getChildrenField($entityClass): string
    {
        $meta = $this->getClassMetadata($entityClass);

        foreach ($meta->getReflectionProperties() as $property) {
            $name = $property->getName();

            if (!$meta->hasAssociation($name)) {
                continue;
            }

            $fieldName = $this->resolveChildrenField($meta->getAssociationMapping($name));

            if (null !== $fieldName) {
                return $fieldName;
            }
        }

        throw new InvalidMappingException(
            'The children property could not be found. It is identified through the `mappedBy` annotation to your parent property.'
        );
    }

    /**
     * @param mixed $mapping object on ORM 3.x, array on ORM 2.x
     */
    private function resolveChildrenField(mixed $mapping): ?string
    {
        if (\is_array($mapping)) {
            if (($mapping['isOwningSide'] ?? true) || ($mapping['mappedBy'] ?? null) !== $this->getParentField()) {
                return null;
            }

            return \is_string($mapping['fieldName'] ?? null) ? $mapping['fieldName'] : null;
        }

        if (!$mapping instanceof InverseSideMapping || $mapping->mappedBy !== $this->getParentField()) {
            return null;
        }

        return $mapping->fieldName;
    }
}
