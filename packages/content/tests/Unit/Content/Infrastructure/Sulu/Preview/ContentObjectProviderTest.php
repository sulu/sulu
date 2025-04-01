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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Preview;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Sulu\Content\Infrastructure\Sulu\Preview\PreviewDimensionContentCollection;
use Sulu\Content\Infrastructure\Sulu\Structure\ContentStructureBridge;
use Sulu\Content\Infrastructure\Sulu\Structure\ContentStructureBridgeFactory;
use Sulu\Content\Tests\Application\ExampleTestBundle\Admin\ExampleAdmin;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\UserInterface\Controller\Website\ContentController;

class ContentObjectProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<ContentStructureBridgeFactory>
     */
    private $contentStructureBridgeFactory;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private $contentAggregator;

    /**
     * @var ObjectProphecy<ContentDataMapperInterface>
     */
    private $contentDataMapper;

    /**
     * @var ContentObjectProvider<ExampleDimensionContent, Example>
     */
    private $contentObjectProvider;

    protected function setUp(): void
    {
        $this->contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);

        $contentStructureBridge = $this->prophesize(ContentStructureBridge::class);
        $contentStructureBridge->getController()->willReturn(ContentController::class . '::indexAction');
        $contentStructureBridge->getView()->willReturn('pages/default');
        $this->contentStructureBridgeFactory->getBridge(Argument::cetera())
            ->willReturn($contentStructureBridge);

        $this->contentObjectProvider = new ContentObjectProvider(
            $this->contentStructureBridgeFactory->reveal(),
            $this->entityManager->reveal(),
            $this->contentAggregator->reveal(),
            $this->contentDataMapper->reveal(),
            Example::class,
            ExampleAdmin::SECURITY_CONTEXT
        );
    }

    public function testGetDefaults(int $id = 1, string $locale = 'de'): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal())->shouldBeCalledTimes(1);

        $queryBuilder->select(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->from(Argument::type('string'), Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->where(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->setParameter(Argument::type('string'), Argument::any())
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $query = $this->prophesize(AbstractQuery::class);

        $queryBuilder->getQuery()->willReturn($query->reveal())->shouldBeCalledTimes(1);

        $entity = $this->prophesize(ContentRichEntityInterface::class);

        $query->getSingleResult()->willReturn($entity->reveal())->shouldBeCalledTimes(1);

        $example = new Example();
        $exampleDimensionContent = new ExampleDimensionContent($example);
        self::setPrivateProperty($example, 'id', $id);

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            Argument::type('array')
        )->willReturn($exampleDimensionContent)->shouldBeCalledTimes(1);

        $previewContext = new PreviewContext($id, $locale);
        $result = $this->contentObjectProvider->getDefaults($previewContext);

        $this->assertSame([
            'object' => $exampleDimensionContent,
            '_controller' => ContentController::class . '::indexAction',
            'view' => 'pages/default',
        ], $result);
    }

    public function testGetDefaultsWithShadow(int $id = 1, string $locale = 'de'): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal())->shouldBeCalledTimes(1);

        $queryBuilder->select(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->from(Argument::type('string'), Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->where(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->setParameter(Argument::type('string'), Argument::any())
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $query = $this->prophesize(AbstractQuery::class);

        $queryBuilder->getQuery()->willReturn($query->reveal())->shouldBeCalledTimes(1);

        $entity = $this->prophesize(ContentRichEntityInterface::class);

        $query->getSingleResult()->willReturn($entity->reveal())->shouldBeCalledTimes(1);

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ShadowInterface::class);
        $dimensionContent->willImplement(TemplateInterface::class);
        $dimensionContent->getShadowLocale()->willReturn('en')->shouldBeCalledTimes(2);

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            [
                'locale' => 'de',
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]
        )->willReturn($dimensionContent->reveal())->shouldBeCalledTimes(1);

        $dimensionContent = $this->prophesize(DimensionContentInterface::class)
            ->willImplement(RoutableInterface::class)
            ->willImplement(TemplateInterface::class);
        $dimensionContent->getLocale()->willReturn('en');
        $this->contentAggregator->aggregate(
            $entity->reveal(),
            [
                'locale' => 'en',
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]
        )->willReturn($dimensionContent->reveal())->shouldBeCalledTimes(1);

        $previewContext = new PreviewContext($id, $locale);
        $result = $this->contentObjectProvider->getDefaults($previewContext);

        $this->assertSame([
            'object' => $dimensionContent->reveal(),
            '_controller' => ContentController::class . '::indexAction',
            'view' => 'pages/default',
        ], $result);
    }

    public function testGetDefaultsNonExisting(int $id = 1, string $locale = 'de'): void
    {
        $this->entityManager->createQueryBuilder()->willThrow(NoResultException::class)->shouldBeCalledTimes(1);

        $previewContext = new PreviewContext($id, $locale);
        $result = $this->contentObjectProvider->getDefaults($previewContext);

        $this->assertSame([], $result);
    }

    public function testGetDefaultsContentNotFound(int $id = 1, string $locale = 'de'): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal())->shouldBeCalledTimes(1);

        $queryBuilder->select(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->from(Argument::type('string'), Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->where(Argument::type('string'))
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $queryBuilder->setParameter(Argument::type('string'), Argument::any())
            ->willReturn($queryBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $query = $this->prophesize(AbstractQuery::class);

        $queryBuilder->getQuery()->willReturn($query->reveal())->shouldBeCalledTimes(1);

        $entity = $this->prophesize(ContentRichEntityInterface::class);

        $query->getSingleResult()->willReturn($entity->reveal())->shouldBeCalledTimes(1);

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            Argument::type('array')
        )->willThrow(ContentNotFoundException::class)->shouldBeCalledTimes(1);

        $previewContext = new PreviewContext($id, $locale);
        $result = $this->contentObjectProvider->getDefaults($previewContext);

        $this->assertSame([], $result);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function testUpdateValues(
        string $locale = 'de',
        array $data = [
            'title' => 'Title',
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoKeywords' => 'Seo Keywords',
            'seoCanonicalUrl' => 'Seo Canonical Url',
            'seoNoIndex' => true,
            'seoNoFollow' => true,
            'seoHideInSitemap' => true,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['foo', 'bar'],
            'excerptCategories' => [1, 2],
            'excerptImage' => ['id' => 3],
            'excerptIcon' => ['id' => 4],
        ]
    ): void {
        $example = new Example();
        $exampleDimensionContent = new ExampleDimensionContent($example);

        $previewContext = new PreviewContext(1, $locale);
        $defaults = [
            'object' => $exampleDimensionContent,
            '_controller' => ContentController::class . '::indexAction',
            'view' => 'pages/default',
        ];
        $this->contentObjectProvider->updateValues($previewContext, $defaults, $data);

        $this->contentDataMapper->map(
            Argument::that(
                function(PreviewDimensionContentCollection $dimensionContentCollection) use ($exampleDimensionContent) {
                    return $exampleDimensionContent === $dimensionContentCollection->getDimensionContent([]);
                }
            ),
            ['locale' => 'de', 'stage' => 'draft'],
            $data
        )->shouldBeCalledTimes(1);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function testUpdateContext(string $locale = 'de', array $context = ['template' => 'overview']): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $previewContext = new PreviewContext(1, $locale);
        $defaults = [
            'object' => $dimensionContent,
            '_controller' => ContentController::class . '::indexAction',
            'view' => 'pages/default',
        ];
        $this->contentObjectProvider->updateContext($previewContext, $defaults, $context);

        $this->assertSame($context['template'], $dimensionContent->getTemplateKey());
    }

    public function testGetSecurityContext(): void
    {
        $previewContext = new PreviewContext('123-123-123', 'e ');

        $this->assertSame(
            ExampleAdmin::SECURITY_CONTEXT,
            $this->contentObjectProvider->getSecurityContext($previewContext)
        );
    }
}
