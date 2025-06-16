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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Route;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
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
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolver;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Infrastructure\Sulu\Route\ContentRouteDefaultsProvider;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webmozart\Assert\Assert;

class ContentRouteDefaultsProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private ObjectProphecy $entityManager;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private ObjectProphecy $contentAggregator;

    /**
     * @var ObjectProphecy<FormMetadataProvider>
     */
    private ObjectProphecy $formMetadataProvider;

    private CacheLifetimeResolver $cacheLifetimeResolver;

    private ContentRouteDefaultsProvider $contentRouteDefaultsProvider;

    protected function setup(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->cacheLifetimeResolver = new CacheLifetimeResolver();
        $metadataProviderRegistry = new MetadataProviderRegistry();
        $this->formMetadataProvider = $this->prophesize(FormMetadataProvider::class);
        $metadataProviderRegistry->addMetadataProvider('form', $this->formMetadataProvider->reveal());

        $this->contentRouteDefaultsProvider = new ContentRouteDefaultsProvider(
            $this->entityManager->reveal(),
            $this->contentAggregator->reveal(),
            $metadataProviderRegistry,
            $this->cacheLifetimeResolver,
        );
    }

    public function testGetDefaultsReturnNoneTemplate(): void
    {
        $resolvedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $resolvedDimensionContent->getLocale()->willReturn('en');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Expected to get "%s" from ContentResolver but "%s" given.',
            TemplateInterface::class,
            \get_class($resolvedDimensionContent->reveal())
        ));

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $this->contentAggregator->aggregate(
            $contentRichEntity->reveal(),
            ['locale' => 'en', 'stage' => 'live']
        )->willReturn($resolvedDimensionContent->reveal());

        $route = new Route(
            Example::RESOURCE_KEY,
            '123-123-123',
            'en',
            '/example',
        );

        $this->contentRouteDefaultsProvider->getDefaults($route);
    }

    public function testGetDefaultsReturnNoneTemplateFromPreview(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->getLocale()->willReturn('en');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Expected to get "%s" from ContentResolver but "%s" given.',
            TemplateInterface::class,
            \get_class($dimensionContent->reveal())
        ));

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $this->contentAggregator->aggregate(
            $contentRichEntity->reveal(),
            ['locale' => 'en', 'stage' => 'live']
        )->willReturn($dimensionContent->reveal());

        $route = new Route(
            Example::RESOURCE_KEY,
            '123-123-123',
            'en',
            '/example',
        );

        $this->contentRouteDefaultsProvider->getDefaults($route);
    }

    public function testGetDefaults(): void
    {
        $contentRichEntity = new Example();
        $resolvedDimensionContent = new ExampleDimensionContent($contentRichEntity);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $this->contentAggregator->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent);

        $this->prepareTemplateMetadata(
            'App\Controller\TestController:testAction',
            'default',
            CacheLifetimeResolverInterface::TYPE_SECONDS,
            '3600',
        );

        $route = new Route(
            Example::RESOURCE_KEY,
            '123-123-123',
            'en',
            '/example',
        );

        $result = $this->contentRouteDefaultsProvider->getDefaults($route);

        $this->assertSame([
            'object' => $resolvedDimensionContent,
            'view' => 'default',
            '_controller' => 'App\Controller\TestController:testAction',
            '_cacheLifetime' => 3600,
        ], $result);
    }

    public function testGetDefaultsNotPublishedInLocale(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $contentRichEntity = new Example();
        $resolvedDimensionContent = new ExampleDimensionContent($contentRichEntity);
        $resolvedDimensionContent->setLocale(null);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $this->contentAggregator->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent);

        $route = new Route(
            Example::RESOURCE_KEY,
            '123-123-123',
            'en',
            '/example',
        );

        $this->contentRouteDefaultsProvider->getDefaults($route);
    }

    public function testGetDefaultsNotPublished(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $contentRichEntity = new Example();

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $this->contentAggregator->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->will(function(array $arguments) {
                $entity = $arguments[0] ?? null;
                $attributes = $arguments[1] ?? null;

                Assert::isInstanceOf($entity, ContentRichEntityInterface::class);
                Assert::isArray($attributes);

                throw new ContentNotFoundException($entity, $attributes);
            });

        $route = new Route(
            Example::RESOURCE_KEY,
            '123-123-123',
            'en',
            '/example',
        );
        $this->contentRouteDefaultsProvider->getDefaults($route);
    }

    public function testGetDefaultsMetadataNotFound(): void
    {
        $this->expectException(\RuntimeException::class);

        $contentRichEntity = new Example();
        $resolvedDimensionContent = new ExampleDimensionContent($contentRichEntity);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey('default');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $this->contentAggregator->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent);

        $this->formMetadataProvider->getMetadata(Argument::cetera())
            ->willReturn(new TypedFormMetadata())
            ->shouldBeCalled();

        $route = new Route(
            Example::RESOURCE_KEY,
            '123-123-123',
            'en',
            '/example',
        );

        $this->contentRouteDefaultsProvider->getDefaults($route);
    }

    public function testGetDefaultsNoTemplate(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $contentRichEntity = new Example();
        $resolvedDimensionContent = new ExampleDimensionContent($contentRichEntity);
        $resolvedDimensionContent->setLocale('en');
        $resolvedDimensionContent->setTemplateKey(null);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity);

        $this->contentAggregator->aggregate($contentRichEntity, ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($resolvedDimensionContent);

        $route = new Route(
            Example::RESOURCE_KEY,
            '123-123-123',
            'en',
            '/example',
        );

        $this->contentRouteDefaultsProvider->getDefaults($route);
    }

    private function prepareTemplateMetadata(string $controller, string $view, ?string $cacheLifeTimeType, string $cacheLifeTimeValue): void
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
