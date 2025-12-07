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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentMetadataInspector;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspector;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentMetadataInspectorTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createContentMetadataInspectorTestInstance(
        EntityManagerInterface $entityManager
    ): ContentMetadataInspectorInterface {
        return new ContentMetadataInspector(
            $entityManager
        );
    }

    public function testGetDimensionContentClass(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->getAssociationMapping('dimensionContents')
            ->willReturn(OneToManyAssociationMapping::fromMappingArray([
                'fieldName' => 'dimensionContents',
                'sourceEntity' => Example::class,
                'targetEntity' => ExampleDimensionContent::class,
                'isOwningSide' => true,
            ]));

        $entityManager->getClassMetadata(Example::class)->willReturn($classMetadata->reveal());

        $contentMetadataInspector = $this->createContentMetadataInspectorTestInstance(
            $entityManager->reveal()
        );

        $dimensionContentClass = $contentMetadataInspector->getDimensionContentClass(Example::class);

        $this->assertSame(ExampleDimensionContent::class, $dimensionContentClass);
    }

    public function testGetDimensionContentPropertyName(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->getAssociationMapping('dimensionContents')
            ->willReturn(OneToManyAssociationMapping::fromMappingArray([
                'fieldName' => 'dimensionContents',
                'sourceEntity' => Example::class,
                'targetEntity' => ExampleDimensionContent::class,
                'mappedBy' => 'example',
                'isOwningSide' => true,
            ]));

        $entityManager->getClassMetadata(Example::class)->willReturn($classMetadata->reveal());

        $contentMetadataInspector = $this->createContentMetadataInspectorTestInstance(
            $entityManager->reveal()
        );

        $dimensionContentClass = $contentMetadataInspector->getDimensionContentPropertyName(Example::class);

        $this->assertSame('example', $dimensionContentClass);
    }
}
