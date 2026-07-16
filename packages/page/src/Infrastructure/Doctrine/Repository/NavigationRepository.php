<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\LinkInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
final class NavigationRepository implements NavigationRepositoryInterface
{
    /**
     * @var NestedTreeRepository<PageInterface>
     */
    private NestedTreeRepository $entityRepository;

    /**
     * @var EntityRepository<PageDimensionContentInterface>
     */
    private EntityRepository $entityDimensionContentRepository;

    /**
     * @var class-string<PageDimensionContentInterface>
     */
    private string $pageDimensionContentClassName;

    /**
     * @param array<string, int> $permissions
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
        private ContentAggregatorInterface $contentAggregator,
        private ContentResolverInterface $contentResolver,
        private WebspaceManagerInterface $webspaceManager,
        private AccessControlQueryEnhancer $accessControlQueryEnhancer,
        private ?Security $security,
        private array $permissions,
        private bool $audienceTargetingEnabled = false,
    ) {
        $repository = $entityManager->getRepository(PageInterface::class);
        Assert::isInstanceOf($repository, NestedTreeRepository::class);

        $this->entityRepository = $repository;
        $this->entityDimensionContentRepository = $entityManager->getRepository(PageDimensionContentInterface::class);
        $this->pageDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
    }

    public function getNavigationTree(
        string $navigationContext,
        string $locale,
        string $webspaceKey,
        ?string $segmentKey,
        int $depth = 1,
        array $properties = []
    ): array {
        $pages = $this->findBy([
            'locale' => $locale,
            'navigationContexts' => [$navigationContext],
            'depth' => $depth,
            'webspaceKey' => $webspaceKey,
            'segmentKey' => $segmentKey,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        return $this->normalizePageTree($pages, $properties, $locale);
    }

    public function getNavigationFlat(
        string $navigationContext,
        string $locale,
        string $webspaceKey,
        ?string $segmentKey,
        int $depth = 1,
        array $properties = []
    ): array {
        $pages = $this->findBy([
            'locale' => $locale,
            'navigationContexts' => [$navigationContext],
            'depth' => $depth,
            'webspaceKey' => $webspaceKey,
            'segmentKey' => $segmentKey,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        return $this->resolveAndNormalizePages($pages, $locale, $properties);
    }

    public function getNavigationFlatByUuid(
        string $uuid,
        string $locale,
        string $webspaceKey,
        int $depth = 1,
        ?string $navigationContext = null,
        array $properties = []
    ): array {
        $filters = $this->buildChildrenFilters($uuid, $locale, $webspaceKey, $depth, $navigationContext);

        /** @var iterable<PageInterface> $pages */
        $pages = $this->createQueryBuilder($filters)->getQuery()->getResult();

        return $this->resolveAndNormalizePages($pages, $locale, $properties);
    }

    public function getNavigationTreeByUuid(
        string $uuid,
        string $locale,
        string $webspaceKey,
        int $depth = 1,
        ?string $navigationContext = null,
        array $properties = []
    ): array {
        $filters = $this->buildChildrenFilters($uuid, $locale, $webspaceKey, $depth, $navigationContext);
        $pages = $this->findBy($filters);

        return $this->normalizePageTree($pages, $properties, $locale, $uuid);
    }

    public function getBreadcrumb(
        string $uuid,
        string $locale,
        string $webspaceKey,
        array $properties = []
    ): array {
        /** @var PageInterface|null $page */
        $page = $this->createQueryBuilder([
            'uuid' => $uuid,
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ])->getQuery()->getOneOrNullResult();

        if (null === $page) {
            return [];
        }

        /** @var PageInterface[] $ancestors */
        $ancestors = $this->createQueryBuilder([
            'ancestorLft' => $page->getLft(),
            'ancestorRgt' => $page->getRgt(),
            'webspaceKey' => $webspaceKey,
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'skipAccessControl' => true,
        ])->getQuery()->getResult();

        /** @var PageInterface[] $pages */
        $pages = [...$ancestors, $page];

        return $this->resolveAndNormalizePages($pages, $locale, $properties);
    }

    /**
     * @return array{
     *     locale: string,
     *     webspaceKey: string,
     *     stage: string,
     *     childrenOf: string,
     *     childrenDepth: int,
     *     navigationContexts?: array<string>
     * }
     */
    private function buildChildrenFilters(
        string $uuid,
        string $locale,
        string $webspaceKey,
        int $depth,
        ?string $navigationContext
    ): array {
        $filters = [
            'locale' => $locale,
            'webspaceKey' => $webspaceKey,
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'childrenOf' => $uuid,
            'childrenDepth' => $depth,
        ];

        if (null !== $navigationContext) {
            $filters['navigationContexts'] = [$navigationContext];
        }

        return $filters;
    }

    /**
     * @param iterable<PageInterface> $pages
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>[]
     */
    private function resolveAndNormalizePages(
        iterable $pages,
        string $locale,
        array $properties
    ): array {
        $result = [];
        foreach ($pages as $page) {
            $normalizedContent = $this->resolvePageContent($page, $locale, $properties);
            if (null === $normalizedContent) {
                continue;
            }

            $result[] = $normalizedContent;
        }

        return $result;
    }

    /**
     * @param string[] $fields
     *
     * @return array<string, int|string>|null
     */
    private function fetchNestedSetValues(string $uuid, array $fields): ?array
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('page');
        $queryBuilder->select(...\array_map(fn ($f) => "page.{$f}", $fields))
            ->where('page.uuid = :uuid')
            ->setParameter('uuid', $uuid);

        /** @var array<string, int|string>|null $result */
        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * @param array{
     *      locale?: string|null,
     *      stage?: string|null,
     *      webspaceKey?: string,
     *      segmentKey?: string|null,
     *      page?: int,
     *      limit?: int,
     *      navigationContexts?: string[],
     *      depth?: int,
     *  } $filters
     *
     * @return \Generator<PageInterface>
     */
    private function findBy(array $filters = []): \Generator
    {
        $query = $this->createQueryBuilder($filters)->getQuery();

        /** @var PageInterface $page */
        foreach ($query->getResult() as $page) { // @phpstan-ignore-line foreach.nonIterable
            yield $page;
        }
    }

    /**
     * @param iterable<PageInterface> $pages
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>[]
     */
    private function normalizePageTree(
        iterable $pages,
        array $properties,
        string $locale,
        ?string $rootParentUuid = null,
    ): array {
        $pagesByUuid = [];
        $rootDepth = null;
        foreach ($pages as $page) {
            $pagesByUuid[$page->getUuid()] = $page;

            if (null === $rootParentUuid) {
                $depth = $page->getDepth();
                $rootDepth = null === $rootDepth ? $depth : \min($rootDepth, $depth);
            }
        }

        $rootPageUuids = [];
        $childPageUuidsByParent = [];

        foreach ($pagesByUuid as $uuid => $page) {
            $parentUuid = $page->getParent()?->getUuid();

            $isRoot = null !== $rootParentUuid
                ? $parentUuid === $rootParentUuid
                : $page->getDepth() === $rootDepth;

            if ($isRoot) {
                $rootPageUuids[] = $uuid;
            } elseif (null !== $parentUuid && \array_key_exists($parentUuid, $pagesByUuid)) {
                $childPageUuidsByParent[$parentUuid][] = $uuid;
            }
        }

        return $this->normalizePageTreeNodes(
            $rootPageUuids,
            $pagesByUuid,
            $childPageUuidsByParent,
            $properties,
            $locale,
        );
    }

    /**
     * @param string[] $pageUuids
     * @param array<string, PageInterface> $pagesByUuid
     * @param array<string, string[]> $childPageUuidsByParent
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>[]
     */
    private function normalizePageTreeNodes(
        array $pageUuids,
        array $pagesByUuid,
        array $childPageUuidsByParent,
        array $properties,
        string $locale,
    ): array {
        $result = [];
        foreach ($pageUuids as $pageUuid) {
            $page = $pagesByUuid[$pageUuid];
            $normalizedContent = $this->resolvePageContent($page, $locale, $properties);
            if (null === $normalizedContent) {
                continue;
            }

            $normalizedContent['children'] = $this->normalizePageTreeNodes(
                $childPageUuidsByParent[$pageUuid] ?? [],
                $pagesByUuid,
                $childPageUuidsByParent,
                $properties,
                $locale,
            );

            $result[] = $normalizedContent;
        }

        return $result;
    }

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>|null
     */
    private function resolvePageContent(PageInterface $page, string $locale, array $properties): ?array
    {
        $pageDimensionContent = $this->contentAggregator->aggregate($page, [
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        $urlKeys = \array_keys($properties, 'url', true);

        // prefix all properties with "nav." to only resolve navigation related content
        foreach ($properties as $key => $value) {
            unset($properties[$key]);
            $properties['nav.' . $key] = $value;
        }

        /** @var array{
         *     nav: array<string, mixed>,
         * } $resolvedContent
         */
        $resolvedContent = $this->contentResolver->resolve($pageDimensionContent, $properties);

        $result = $resolvedContent['nav'];

        if ($this->isUnresolvedLink($pageDimensionContent, $result, $urlKeys)) {
            return null;
        }

        $result['targetType'] = $result['targetType'] ?? PageLinkProvider::ALIAS;

        return $result;
    }

    /**
     * @param DimensionContentInterface<PageInterface> $dimensionContent
     * @param array<string, mixed> $result
     * @param array<int, string> $urlKeys
     */
    private function isUnresolvedLink(
        DimensionContentInterface $dimensionContent,
        array $result,
        array $urlKeys,
    ): bool {
        if (!$dimensionContent instanceof LinkInterface) {
            return false;
        }

        if (null === ($dimensionContent->getLinkData()['provider'] ?? null)) {
            return false;
        }

        if ([] === $urlKeys) {
            return false;
        }

        foreach ($urlKeys as $urlKey) {
            $url = $this->findResolvedValue($result, $urlKey);
            if (null !== $url && '' !== $url) {
                return false;
            }
        }

        return true;
    }

    /**
     * The content resolver nests dotted property names, so "link.url" is resolved
     * into $result['link']['url'].
     *
     * @param array<string, mixed> $result
     */
    private function findResolvedValue(array $result, string $key): mixed
    {
        $value = $result;

        foreach (\explode('.', $key) as $segment) {
            if (!\is_array($value) || !\array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @param array{
     *     locale?: string|null,
     *     stage?: string|null,
     *     webspaceKey?: string,
     *     page?: int,
     *     limit?: int,
     *     navigationContexts?: string[],
     *     depth?: int,
     *     uuid?: string,
     *     ancestorsOf?: string,
     *     ancestorLft?: int,
     *     ancestorRgt?: int,
     *     childrenOf?: string,
     *     childrenDepth?: int,
     *     skipAccessControl?: bool,
     * } $filters
     */
    private function createQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('page');

        $uuid = $filters['uuid'] ?? null;
        if (null !== $uuid) {
            Assert::string($uuid); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('page.uuid = :uuid')
                ->setParameter('uuid', $uuid);
        }

        $ancestorLft = $filters['ancestorLft'] ?? null;
        $ancestorRgt = $filters['ancestorRgt'] ?? null;
        $ancestorsOf = $filters['ancestorsOf'] ?? null;

        if (null !== $ancestorLft && null !== $ancestorRgt) {
            $queryBuilder
                ->andWhere('page.lft < :ancestorLft')
                ->andWhere('page.rgt > :ancestorRgt')
                ->setParameter('ancestorLft', $ancestorLft)
                ->setParameter('ancestorRgt', $ancestorRgt);
        } elseif (null !== $ancestorsOf) {
            Assert::string($ancestorsOf); // @phpstan-ignore staticMethod.alreadyNarrowedType

            $result = $this->fetchNestedSetValues($ancestorsOf, ['lft', 'rgt']);

            if (null !== $result) {
                $queryBuilder
                    ->andWhere('page.lft < :ancestorLft')
                    ->andWhere('page.rgt > :ancestorRgt')
                    ->setParameter('ancestorLft', $result['lft'])
                    ->setParameter('ancestorRgt', $result['rgt']);
            }
        }

        $childrenOf = $filters['childrenOf'] ?? null;
        if (null !== $childrenOf) {
            Assert::string($childrenOf); // @phpstan-ignore staticMethod.alreadyNarrowedType

            $result = $this->fetchNestedSetValues($childrenOf, ['lft', 'rgt', 'depth']);

            if (null !== $result) {
                $queryBuilder
                    ->andWhere('page.lft > :parentLft')
                    ->andWhere('page.rgt < :parentRgt')
                    ->setParameter('parentLft', $result['lft'])
                    ->setParameter('parentRgt', $result['rgt']);

                $childrenDepth = $filters['childrenDepth'] ?? null;
                if (null !== $childrenDepth) {
                    Assert::integer($childrenDepth); // @phpstan-ignore staticMethod.alreadyNarrowedType
                    Assert::integer($result['depth']);
                    $queryBuilder
                        ->andWhere('page.depth <= :maxDepth')
                        ->setParameter('maxDepth', $result['depth'] + $childrenDepth);
                }
            } else {
                // Parent UUID doesn't exist, make query return no results
                $queryBuilder->andWhere('1 = 0');
            }
        }

        $webspace = $filters['webspaceKey'] ?? null;
        if (null !== $webspace) {
            Assert::string($webspace); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')
                ->setParameter('webspaceKey', $webspace);
        }

        $depth = $filters['depth'] ?? null;
        if (null !== $depth) {
            Assert::integer($depth); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('page.depth <= :depth')
                ->setParameter('depth', $depth);
        }

        $limit = $filters['limit'] ?? null;
        if (null !== $limit) {
            Assert::integer($limit); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->setMaxResults($limit);
        }

        $page = $filters['page'] ?? null;
        if (null !== $page) {
            Assert::integer($page); // @phpstan-ignore staticMethod.alreadyNarrowedType
            Assert::notNull($limit);
            $offset = (int) ($limit * ($page - 1));
            $queryBuilder->setFirstResult($offset);
        }

        if (
            \array_key_exists('locale', $filters)       // should also work with locale = null
                && \array_key_exists('stage', $filters)
        ) {
            $this->dimensionContentQueryEnhancer->addFilters(
                $queryBuilder,
                'page',
                $this->pageDimensionContentClassName,
                $filters,
                []
            );

            $this->leftJoinDimensionContent($queryBuilder);

            $dimensionAttributes = [
                'locale' => $filters['locale'] ?? null,
                'stage' => $filters['stage'] ?? null,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ];

            $selects = [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true];
            if ($this->audienceTargetingEnabled) {
                $selects[DimensionContentQueryEnhancer::SELECT_EXCERPT_AUDIENCE_TARGET_GROUPS] = true;
            }

            $this->dimensionContentQueryEnhancer->addSelects(
                $queryBuilder,
                $this->pageDimensionContentClassName,
                $dimensionAttributes,
                $selects
            );

            $queryBuilder->leftJoin('dimensionContent.navigationContexts', 'navigationContext')
                ->addSelect('navigationContext');
        }

        $navigationContexts = $filters['navigationContexts'] ?? null;
        if (null !== $navigationContexts) {
            Assert::isArray($navigationContexts); // @phpstan-ignore staticMethod.alreadyNarrowedType
            if ([] !== $navigationContexts) {
                $queryBuilder->leftJoin('filterDimensionContent.navigationContexts', 'filterNavigationContext')
                    ->andWhere('filterNavigationContext.navigationContext IN (:navigationContexts)')
                    ->setParameter('navigationContexts', $navigationContexts);
            }
        }

        $queryBuilder->addOrderBy('page.lft', 'asc');

        $skipAccessControl = $filters['skipAccessControl'] ?? false;
        $webspaceKey = $filters['webspaceKey'] ?? null;
        if (null !== $webspaceKey && !$skipAccessControl) {
            $this->enhanceQueryBuilderWithAccessControl($queryBuilder, $webspaceKey, 'page');
        }

        return $queryBuilder;
    }

    private function enhanceQueryBuilderWithAccessControl(
        QueryBuilder $queryBuilder,
        string $webspaceKey,
        string $alias
    ): void {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $user = null;

        if ($webspace && $webspace->hasWebsiteSecurity()) {
            $user = $this->security?->getUser();

            if (!$user instanceof UserInterface) {
                $user = null;
            }
        }
        /** @var int|null $permission */
        $permission = $webspace && $webspace->hasWebsiteSecurity()
            ? $this->permissions[PermissionTypes::VIEW]
            : null;

        if ($permission) {
            $this->accessControlQueryEnhancer->enhance(
                $queryBuilder,
                $user,
                $permission,
                Page::class,
                $alias,
                'uuid'
            );
        }
    }

    private function leftJoinDimensionContent(QueryBuilder $queryBuilder): void
    {
        $hasJoin = false;
        /** @var array<string, Join[]> $joinParts */
        $joinParts = $queryBuilder->getDQLPart('join');

        foreach ($joinParts as $joins) {
            foreach ($joins as $join) {
                if ('page.dimensionContents' === $join->getJoin()) {
                    $hasJoin = true;
                    break 2;
                }
            }
        }

        if (!$hasJoin) {
            $queryBuilder->leftJoin('page.dimensionContents', 'dimensionContent');
        }
    }
}
