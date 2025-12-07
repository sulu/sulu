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

namespace Sulu\Content\Application\ContentMetadataInspector;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentMetadataInspector implements ContentMetadataInspectorInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param class-string<ContentRichEntityInterface<T>> $contentRichEntityClass
     *
     * @return class-string<T>
     */
    public function getDimensionContentClass(string $contentRichEntityClass): string
    {
        $classMetadata = $this->entityManager->getClassMetadata($contentRichEntityClass);
        $associationMapping = $classMetadata->getAssociationMapping('dimensionContents');

        \assert($associationMapping instanceof OneToManyAssociationMapping, 'Expected dimensionContents to be a OneToMany association.');

        return $associationMapping->targetEntity;
    }

    public function getDimensionContentPropertyName(string $contentRichEntityClass): string
    {
        $classMetadata = $this->entityManager->getClassMetadata($contentRichEntityClass);
        $associationMapping = $classMetadata->getAssociationMapping('dimensionContents');

        \assert($associationMapping instanceof OneToManyAssociationMapping, 'Expected dimensionContents to be a OneToMany association.');

        return $associationMapping->mappedBy;
    }
}
