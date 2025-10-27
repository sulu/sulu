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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Doctrine;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\LinkInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Infrastructure\Doctrine\MetadataLoader;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class MetadataLoaderTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @param array<string, mixed> $bundles
     */
    protected function getMetadataLoader(array $bundles = []): MetadataLoader
    {
        return new MetadataLoader($bundles);
    }

    /**
     * @param string[] $interfaces
     * @param bool[] $fields
     * @param bool[] $manyToManyAssociations
     * @param bool[] $manyToOneAssociations
     */
    #[DataProvider('dataProvider')]
    public function testInvalidMetadata(array $interfaces, array $fields, array $manyToManyAssociations, array $manyToOneAssociations): void
    {
        $metadataLoader = $this->getMetadataLoader();
        $reflectionClass = $this->prophesize(\ReflectionClass::class);

        $reflectionClass->implementsInterface(DimensionContentInterface::class)->willReturn(\in_array(DimensionContentInterface::class, $interfaces, true));
        $reflectionClass->implementsInterface(SeoInterface::class)->willReturn(\in_array(SeoInterface::class, $interfaces, true));
        $reflectionClass->implementsInterface(ExcerptInterface::class)->willReturn(\in_array(ExcerptInterface::class, $interfaces, true));
        $reflectionClass->implementsInterface(TemplateInterface::class)->willReturn(\in_array(TemplateInterface::class, $interfaces, true));
        $reflectionClass->implementsInterface(WorkflowInterface::class)->willReturn(\in_array(WorkflowInterface::class, $interfaces, true));
        $reflectionClass->implementsInterface(WebspaceInterface::class)->willReturn(\in_array(WebspaceInterface::class, $interfaces, true));
        $reflectionClass->implementsInterface(AuthorInterface::class)->willReturn(\in_array(AuthorInterface::class, $interfaces, true));
        $reflectionClass->implementsInterface(ShadowInterface::class)->willReturn(\in_array(ShadowInterface::class, $interfaces, true));
        $reflectionClass->implementsInterface(RoutableInterface::class)->willReturn(\in_array(RoutableInterface::class, $interfaces, true));
        $reflectionClass->implementsInterface(LinkInterface::class)->willReturn(\in_array(LinkInterface::class, $interfaces, true));

        foreach ($interfaces as $interface) {
            $reflectionClass->implementsInterface($interface)->willReturn(true);
        }

        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->getReflectionClass()->willReturn($reflectionClass->reveal());
        $classMetadata->getTableName()->willReturn('test_example');
        $classMetadata->getIdentifierColumnNames()->willReturn(['id']);
        $classMetadata->getName()->willReturn(ExampleDimensionContent::class);

        foreach ($fields as $field => $exist) {
            $classMetadata->hasField($field)->willReturn($exist);
            $classMetadata->mapField(Argument::that(function(array $mapping) use ($field) {
                return $mapping['fieldName'] === $field;
            }))->shouldBeCalledTimes($exist ? 0 : 1);
        }

        foreach ($manyToManyAssociations as $association => $exist) {
            $classMetadata->hasAssociation($association)->willReturn($exist);
            $classMetadata->mapManyToMany(Argument::that(function(array $mapping) use ($association) {
                return $mapping['fieldName'] === $association;
            }))->shouldBeCalledTimes($exist ? 0 : 1);
        }

        foreach ($manyToOneAssociations as $association => $exist) {
            $classMetadata->hasAssociation($association)->willReturn($exist);
            $classMetadata->mapManyToOne(Argument::that(function(array $mapping) use ($association) {
                return $mapping['fieldName'] === $association;
            }))->shouldBeCalledTimes($exist ? 0 : 1);
        }

        $configuration = $this->prophesize(Configuration::class);
        $configuration->getNamingStrategy()->willReturn(new UnderscoreNamingStrategy());
        $entityManager = $this->prophesize(EntityManager::class);
        $entityManager->getConfiguration()->willReturn($configuration->reveal());

        if (\array_key_exists('excerptTags', $manyToManyAssociations) && !$manyToManyAssociations['excerptTags']) {
            $tagClassMetadata = $this->prophesize(ClassMetadata::class);
            $tagClassMetadata->getIdentifierColumnNames()->willReturn(['id'])->shouldBeCalled();
            $entityManager->getClassMetadata(TagInterface::class)->willReturn($tagClassMetadata->reveal());
        }

        if (\array_key_exists('excerptCategories', $manyToManyAssociations) && !$manyToManyAssociations['excerptCategories']) {
            $categoryClassMetadata = $this->prophesize(ClassMetadata::class);
            $categoryClassMetadata->getIdentifierColumnNames()->willReturn(['id'])->shouldBeCalled();
            $entityManager->getClassMetadata(CategoryInterface::class)->willReturn($categoryClassMetadata->reveal());
        }

        if (\array_key_exists('excerptAudienceTargetGroups', $manyToManyAssociations) && !$manyToManyAssociations['excerptAudienceTargetGroups']) {
            $targetGroupClassMetadata = $this->prophesize(ClassMetadata::class);
            $targetGroupClassMetadata->getIdentifierColumnNames()->willReturn(['id'])->shouldBeCalled();
            $entityManager->getClassMetadata(TargetGroupInterface::class)->willReturn($targetGroupClassMetadata->reveal());
        }

        $metadataLoader->loadClassMetadata(
            new LoadClassMetadataEventArgs($classMetadata->reveal(), $entityManager->reveal())
        );
    }

    /**
     * @return \Generator<mixed[]>
     */
    public static function dataProvider(): \Generator
    {
        yield [
            [
                DimensionContentInterface::class,
            ],
            [
                'locale' => false,
                'stage' => false,
                'ghostLocale' => false,
                'availableLocales' => false,
                'version' => false,
            ],
            [],
            [
            ],
        ];

        yield [
            [
                DimensionContentInterface::class,
            ],
            [
                'locale' => true,
                'stage' => true,
                'ghostLocale' => true,
                'availableLocales' => true,
                'version' => true,
            ],
            [],
            [
            ],
        ];

        yield [
            [
                ExcerptInterface::class,
            ],
            [
                'excerptTitle' => false,
                'excerptDescription' => false,
                'excerptMore' => false,
                'excerptSegment' => false,
                'excerptImageId' => false,
                'excerptIconId' => false,
            ],
            [
                'excerptTags' => false,
                'excerptCategories' => false,
            ],
            [],
        ];

        yield [
            [
                SeoInterface::class,
            ],
            [
                'seoTitle' => false,
                'seoDescription' => false,
                'seoKeywords' => false,
                'seoCanonicalUrl' => false,
                'seoNoIndex' => false,
                'seoNoFollow' => false,
                'seoHideInSitemap' => false,
            ],
            [],
            [],
        ];

        yield [
            [
                TemplateInterface::class,
            ],
            [
                'templateKey' => false,
                'templateData' => false,
            ],
            [],
            [],
        ];

        yield [
            [
                ExcerptInterface::class,
            ],
            [
                'excerptTitle' => true,
                'excerptDescription' => false,
                'excerptMore' => false,
                'excerptSegment' => false,
                'excerptImageId' => false,
                'excerptIconId' => false,
            ],
            [
                'excerptTags' => true,
                'excerptCategories' => false,
            ],
            [],
        ];

        yield [
            [
                WorkflowInterface::class,
            ],
            [
                'workflowPlace' => true,
                'workflowPublished' => true,
            ],
            [
            ],
            [],
        ];

        yield [
            [
                AuthorInterface::class,
            ],
            [
                'author' => true,
                'authored' => true,
                'lastModified' => null,
            ],
            [],
            [
                'author' => true,
            ],
        ];

        yield [
            [
                WebspaceInterface::class,
            ],
            [
                'mainWebspace' => true,
            ],
            [],
            [],
        ];

        yield [
            [
                ShadowInterface::class,
            ],
            [
                'shadowLocale' => false,
                'shadowLocales' => false,
            ],
            [],
            [],
        ];

        yield [
            [
                LinkInterface::class,
            ],
            [
                'linkProvider' => false,
                'linkData' => false,
            ],
            [],
            [],
        ];
    }

    public function testExcerptAudienceTargetGroupsWithoutBundle(): void
    {
        $metadataLoader = $this->getMetadataLoader([]); // No bundles
        $reflectionClass = $this->prophesize(\ReflectionClass::class);

        $reflectionClass->implementsInterface(ExcerptInterface::class)->willReturn(true);
        $reflectionClass->implementsInterface(Argument::any())->willReturn(false);

        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->getReflectionClass()->willReturn($reflectionClass->reveal());
        $classMetadata->getTableName()->willReturn('test_example');
        $classMetadata->getIdentifierColumnNames()->willReturn(['id']);
        $classMetadata->getName()->willReturn(ExampleDimensionContent::class);

        // All excerpt fields should be added
        $classMetadata->hasField('excerptTitle')->willReturn(false);
        $classMetadata->hasField('excerptDescription')->willReturn(false);
        $classMetadata->hasField('excerptMore')->willReturn(false);
        $classMetadata->hasField('excerptSegment')->willReturn(false);
        $classMetadata->hasField('excerptImageId')->willReturn(false);
        $classMetadata->hasField('excerptIconId')->willReturn(false);

        $classMetadata->mapField(Argument::any())->shouldBeCalledTimes(6);

        // Tags and categories should be added
        $classMetadata->hasAssociation('excerptTags')->willReturn(false);
        $classMetadata->hasAssociation('excerptCategories')->willReturn(false);
        $classMetadata->mapManyToMany(Argument::any())->shouldBeCalledTimes(2);

        // Target groups should NOT be added (bundle not available)
        $classMetadata->hasAssociation('excerptAudienceTargetGroups')->shouldNotBeCalled();

        $configuration = $this->prophesize(Configuration::class);
        $configuration->getNamingStrategy()->willReturn(new UnderscoreNamingStrategy());
        $entityManager = $this->prophesize(EntityManager::class);
        $entityManager->getConfiguration()->willReturn($configuration->reveal());

        // Set up tag metadata
        $tagClassMetadata = $this->prophesize(ClassMetadata::class);
        $tagClassMetadata->getIdentifierColumnNames()->willReturn(['id']);
        $entityManager->getClassMetadata(TagInterface::class)->willReturn($tagClassMetadata->reveal());

        // Set up category metadata
        $categoryClassMetadata = $this->prophesize(ClassMetadata::class);
        $categoryClassMetadata->getIdentifierColumnNames()->willReturn(['id']);
        $entityManager->getClassMetadata(CategoryInterface::class)->willReturn($categoryClassMetadata->reveal());

        $metadataLoader->loadClassMetadata(
            new LoadClassMetadataEventArgs($classMetadata->reveal(), $entityManager->reveal())
        );
    }

    public function testExcerptAudienceTargetGroupsWithBundle(): void
    {
        $metadataLoader = $this->getMetadataLoader(['SuluAudienceTargetingBundle' => true]);
        $reflectionClass = $this->prophesize(\ReflectionClass::class);

        $reflectionClass->implementsInterface(ExcerptInterface::class)->willReturn(true);
        $reflectionClass->implementsInterface(Argument::any())->willReturn(false);

        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->getReflectionClass()->willReturn($reflectionClass->reveal());
        $classMetadata->getTableName()->willReturn('test_example');
        $classMetadata->getIdentifierColumnNames()->willReturn(['id']);
        $classMetadata->getName()->willReturn(ExampleDimensionContent::class);

        // All excerpt fields should be added
        $classMetadata->hasField('excerptTitle')->willReturn(false);
        $classMetadata->hasField('excerptDescription')->willReturn(false);
        $classMetadata->hasField('excerptMore')->willReturn(false);
        $classMetadata->hasField('excerptSegment')->willReturn(false);
        $classMetadata->hasField('excerptImageId')->willReturn(false);
        $classMetadata->hasField('excerptIconId')->willReturn(false);

        $classMetadata->mapField(Argument::any())->shouldBeCalledTimes(6);

        // Tags, categories, and target groups should be added
        $classMetadata->hasAssociation('excerptTags')->willReturn(false);
        $classMetadata->hasAssociation('excerptCategories')->willReturn(false);
        $classMetadata->hasAssociation('excerptAudienceTargetGroups')->willReturn(false);
        $classMetadata->mapManyToMany(Argument::any())->shouldBeCalledTimes(3);

        $configuration = $this->prophesize(Configuration::class);
        $configuration->getNamingStrategy()->willReturn(new UnderscoreNamingStrategy());
        $entityManager = $this->prophesize(EntityManager::class);
        $entityManager->getConfiguration()->willReturn($configuration->reveal());

        // Set up tag metadata
        $tagClassMetadata = $this->prophesize(ClassMetadata::class);
        $tagClassMetadata->getIdentifierColumnNames()->willReturn(['id']);
        $entityManager->getClassMetadata(TagInterface::class)->willReturn($tagClassMetadata->reveal());

        // Set up category metadata
        $categoryClassMetadata = $this->prophesize(ClassMetadata::class);
        $categoryClassMetadata->getIdentifierColumnNames()->willReturn(['id']);
        $entityManager->getClassMetadata(CategoryInterface::class)->willReturn($categoryClassMetadata->reveal());

        // Set up target group metadata
        $targetGroupClassMetadata = $this->prophesize(ClassMetadata::class);
        $targetGroupClassMetadata->getIdentifierColumnNames()->willReturn(['id']);
        $entityManager->getClassMetadata(TargetGroupInterface::class)->willReturn($targetGroupClassMetadata->reveal());

        $metadataLoader->loadClassMetadata(
            new LoadClassMetadataEventArgs($classMetadata->reveal(), $entityManager->reveal())
        );
    }
}
