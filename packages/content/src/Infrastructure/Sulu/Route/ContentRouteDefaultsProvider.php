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

namespace Sulu\Content\Infrastructure\Sulu\Route;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Sulu\Article\Domain\Model\Article;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Infrastructure\Sulu\Structure\ContentStructureBridgeFactory;
use Sulu\Content\Infrastructure\Sulu\Structure\StructureMetadataNotFoundException;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Page\Domain\Model\Page;
use Sulu\Route\Application\Routing\Matcher\RouteDefaultsProviderInterface;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal this class is experimental and does not have a stable API yet
 */
class ContentRouteDefaultsProvider implements RouteDefaultsProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ContentAggregatorInterface
     */
    protected $contentAggregator;

    /**
     * @var ContentStructureBridgeFactory
     */
    protected $contentStructureBridgeFactory;

    /**
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    public function __construct(
        EntityManagerInterface $entityManager,
        ContentAggregatorInterface $contentAggregator,
        ContentStructureBridgeFactory $contentStructureBridgeFactory,
        CacheLifetimeResolverInterface $cacheLifetimeResolver,
    ) {
        $this->entityManager = $entityManager;
        $this->contentAggregator = $contentAggregator;
        $this->contentStructureBridgeFactory = $contentStructureBridgeFactory;
        $this->cacheLifetimeResolver = $cacheLifetimeResolver;
    }

    public function getDefaults(Route $route): array
    {
        $id = $route->getResourceId();
        $locale = $route->getLocale();

        /** @var class-string<Page|Article|Example> $entityClass */
        $entityClass = match ($route->getResourceKey()) { // TODO we should have repository interface and every bundle its own RouteDefaultsProvider this is here to move things forward faster
            'pages' => Page::class,
            'articles' => Article::class,
            'examples' => Example::class,
            default => throw new \RuntimeException(\sprintf('Unknown resourceKey "%s"', $route->getResourceKey())),
        };

        /** @var DimensionContentInterface|null $entity */
        $entity = $this->loadEntity($entityClass, $id, $locale); // @phpstan-ignore-line

        if (null === $entity) {
            throw new NotFoundHttpException(\sprintf('No content found for id "%s" and locale "%s"', $id, $locale));
        }

        if (!$entity instanceof TemplateInterface) {
            throw new \RuntimeException(\sprintf('Expected to get "%s" from ContentResolver but "%s" given.', TemplateInterface::class, $entity::class));
        }

        if (!$entity->getLocale()) {
            throw new NotFoundHttpException(\sprintf('No content found for id "%s" and locale "%s"', $id, $locale));
        }

        try {
            $structureBridge = $this->contentStructureBridgeFactory->getBridge($entity, $id, $locale);
        } catch (StructureMetadataNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return [
            'object' => $entity,
            'view' => $structureBridge->getView(),
            '_controller' => $structureBridge->getController(),
            '_cacheLifetime' => $this->getCacheLifetime($structureBridge->getStructure()),
        ];
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param class-string<ContentRichEntityInterface<T>> $entityClass
     *
     * @return T|null
     */
    private function loadEntity(string $entityClass, string $id, string $locale): ?DimensionContentInterface
    {
        try {
            /** @var ContentRichEntityInterface<T> $contentRichEntity */
            $contentRichEntity = $this->entityManager->createQueryBuilder()
                ->select('entity')
                ->from($entityClass, 'entity')
                ->where('entity = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $exception) {
            return null;
        }

        try {
            // TODO:
            //      to support other dimension attributes here
            //      we should maybe get dimension Attributes from request attributes set by a request listener
            //      e.g. $request->attributes->get('_sulu_content_dimension_attributes');
            $resolvedDimensionContent = $this->contentAggregator->aggregate(
                $contentRichEntity,
                [
                    'locale' => $locale,
                    'stage' => DimensionContentInterface::STAGE_LIVE,
                ]
            );

            if (!$resolvedDimensionContent instanceof TemplateInterface) {
                throw new \RuntimeException(\sprintf('Expected to get "%s" from ContentResolver but "%s" given.', TemplateInterface::class, $resolvedDimensionContent::class));
            }

            return $resolvedDimensionContent;
        } catch (ContentNotFoundException $exception) {
            return null;
        }
    }

    private function getCacheLifetime(StructureMetadata $metadata): ?int
    {
        $cacheLifetime = $metadata->getCacheLifetime();
        if (!$cacheLifetime) {
            // TODO FIXME add test case for this
            return null; // @codeCoverageIgnore
        }

        if (!isset($cacheLifetime['type'])
            || !isset($cacheLifetime['value'])
            || !\is_string($cacheLifetime['type'])
            || !(\is_string($cacheLifetime['value']) || \is_int($cacheLifetime['value']))
            || !$this->cacheLifetimeResolver->supports($cacheLifetime['type'], $cacheLifetime['value'])
        ) {
            // TODO FIXME add test case for this
            throw new \InvalidArgumentException(\sprintf('Invalid cachelifetime in route default provider: %s', \var_export($cacheLifetime, true))); // @codeCoverageIgnore
        }

        return $this->cacheLifetimeResolver->resolve($cacheLifetime['type'], $cacheLifetime['value']);
    }
}
