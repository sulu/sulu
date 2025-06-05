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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\CacheLifetimeMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Sulu\Content\Infrastructure\Sulu\Preview\PreviewDimensionContentCollection;
use Sulu\Content\Tests\Application\ExampleTestBundle\Admin\ExampleAdmin;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\UserInterface\Controller\Website\ContentController;

class ContentObjectProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<FormMetadataProvider>
     */
    private ObjectProphecy $formMetadataProvider;

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
        $metadataProviderRegistry = new MetadataProviderRegistry();
        $this->formMetadataProvider = $this->prophesize(FormMetadataProvider::class);
        $metadataProviderRegistry->addMetadataProvider('form', $this->formMetadataProvider->reveal());

        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);

        $this->contentObjectProvider = new ContentObjectProvider(
            $metadataProviderRegistry,
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
        $exampleDimensionContent->setLocale($locale);
        $exampleDimensionContent->setTemplateKey('default');
        self::setPrivateProperty($example, 'id', $id);

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            Argument::type('array')
        )->willReturn($exampleDimensionContent)->shouldBeCalledTimes(1);

        $this->prepareTemplateMetadata(
            ContentController::class . '::indexAction',
            'pages/default',
        );

        $previewContext = new PreviewContext($id, $locale);
        $result = $this->contentObjectProvider->getDefaults($previewContext);

        $this->assertSame([
            'object' => $exampleDimensionContent,
            'view' => 'pages/default',
            '_controller' => ContentController::class . '::indexAction',
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

        $example = new Example();
        self::setPrivateProperty($example, 'id', $id);
        $exampleDimensionContent = new ExampleDimensionContent($example);
        $exampleDimensionContent->setLocale('de');
        $exampleDimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $exampleDimensionContent->setTemplateKey('default');
        $exampleDimensionContent->setShadowLocale('en');

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            [
                'locale' => 'de',
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]
        )->willReturn($exampleDimensionContent)->shouldBeCalledTimes(1);

        $shadowDimensionContent = new ExampleDimensionContent($example);
        $shadowDimensionContent->setLocale('en');
        $shadowDimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $shadowDimensionContent->setTemplateKey('default');

        $this->contentAggregator->aggregate(
            $entity->reveal(),
            [
                'locale' => 'en',
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]
        )->willReturn($shadowDimensionContent)->shouldBeCalledTimes(1);

        $this->prepareTemplateMetadata(
            ContentController::class . '::indexAction',
            'pages/default',
        );

        $previewContext = new PreviewContext($id, $locale);
        $result = $this->contentObjectProvider->getDefaults($previewContext);

        $this->assertSame([
            'object' => $shadowDimensionContent,
            'view' => 'pages/default',
            '_controller' => ContentController::class . '::indexAction',
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

    private function prepareTemplateMetadata(string $controller, string $view, ?string $cacheLifeTimeType = null, string $cacheLifeTimeValue = '0'): void
    {
        $typedMetadata = new TypedFormMetadata();
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $typedMetadata->addForm($formMetadata->getKey(), $formMetadata);

        $cacheLifeTimeMetadata = null;
        if (null !== $cacheLifeTimeType) {
            $cacheLifeTimeMetadata = new CacheLifetimeMetadata($cacheLifeTimeType, $cacheLifeTimeValue);
        }
        $templateMetadata = new TemplateMetadata($controller, $view, $cacheLifeTimeMetadata);
        $formMetadata->setTemplate($templateMetadata);

        $this->formMetadataProvider->getMetadata(Argument::cetera())
            ->willReturn($typedMetadata)
            ->shouldBeCalled();
    }
}
