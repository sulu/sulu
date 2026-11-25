<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\SmartContent;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentQueryEnhancer;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancerInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\DeduplicationStore\DeduplicationStoreInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-type MediaSmartContentFilters array{
 *       categories: int[],
 *       categoryOperator: 'AND'|'OR',
 *       websiteCategories: string[],
 *       websiteCategoryOperator: 'AND'|'OR',
 *       tags: int[],
 *       tagOperator: 'AND'|'OR',
 *       websiteTags: string[],
 *       websiteTagOperator: 'AND'|'OR',
 *       types: string[],
 *       typesOperator: 'OR',
 *       locale: string,
 *       dataSource: string|null,
 *       limit: int|null,
 *       offset: int,
 *       includeSubFolders: bool,
 *       excludeDuplicates: bool,
 *       excluded?: (int|string)[],
 *       audienceTargeting?: bool,
 *       targetGroupId?: int,
 *       segmentKey?: string,
 *       webspaceKey?: string,
 *       mimetype?: string|null,
 *       type?: string|null,
 *  }
 * @phpstan-type MediaSmartContentCountFilters array{
 *       categories: int[],
 *       categoryOperator: 'AND'|'OR',
 *       websiteCategories: string[],
 *       websiteCategoryOperator: 'AND'|'OR',
 *       tags: int[],
 *       tagOperator: 'AND'|'OR',
 *       websiteTags: string[],
 *       websiteTagOperator: 'AND'|'OR',
 *       types: string[],
 *       typesOperator: 'OR',
 *       locale: string,
 *       dataSource: string|null,
 *       limit: int|null,
 *       includeSubFolders: bool,
 *       excludeDuplicates: bool,
 *       excluded?: (int|string)[],
 *       audienceTargeting?: bool,
 *       targetGroupId?: int,
 *       segmentKey?: string,
 *       webspaceKey?: string,
 *       mimetype?: string|null,
 *       type?: string|null,
 *  }
 */
readonly class MediaSmartContentProvider implements SmartContentProviderInterface
{
    /**
     * @param mixed[]|null $permissions
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SmartContentQueryEnhancer $smartContentQueryEnhancer,
        private TranslatorInterface $translator,
        private WebspaceManagerInterface $webspaceManager,
        private AccessControlQueryEnhancerInterface $accessControlQueryEnhancer,
        private ?Security $security,
        private bool $hasAudienceTargeting = false,
        private ?array $permissions = null,
        private ?DeduplicationStoreInterface $deduplicationStore = null,
    ) {
        if (null === $this->deduplicationStore) {
            @trigger_deprecation('sulu/sulu', '3.0.12', \sprintf('Instantiating %s without the $deduplicationStore argument is deprecated.', static::class));
        }
    }

    public function getConfiguration(): ProviderConfigurationInterface
    {
        $builder = Builder::create()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableDatasource('collections', 'collections', 'column_list')
            ->enableSorting(
                [
                    ['column' => 'fileVersionMeta.title', 'title' => 'sulu_admin.title'],
                    ['column' => 'media.created', 'title' => 'sulu_admin.created'],
                    ['column' => 'media.changed', 'title' => 'sulu_admin.changed'],
                ],
            )
            ->enableTypes($this->getTypes())
            ->enableView(MediaAdmin::EDIT_FORM_VIEW, ['id' => 'id']);

        if ($this->hasAudienceTargeting) {
            $builder->enableAudienceTargeting();
        }

        return $builder->getConfiguration();
    }

    /**
     * @return array{type: string, title: string}[]
     */
    protected function getTypes(): array
    {
        /** @var array<string> $types */
        $types = $this->entityManager->createQueryBuilder()
            ->distinct()
            ->select('media.type as type')
            ->from(Media::class, 'media')
            ->getQuery()
            ->getSingleColumnResult()
        ;

        return \array_map(fn (string $type) => [
            'type' => $type,
            'title' => $this->translator->trans('sulu_media.' . $type, [], 'admin'),
        ], $types);
    }

    /**
     * @param MediaSmartContentCountFilters $filters
     * @param PropertyParameter[] $params
     */
    public function countBy(array $filters, array $params = []): int
    {
        if (!$this->hasDataSource($filters)) {
            return 0;
        }

        $alias = 'media';
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->select(\sprintf('COUNT(DISTINCT %s.id)', $alias));
        $this->enhanceQueryBuilder(
            $queryBuilder,
            $filters,
            [],
            $filters['locale'],
            $alias,
            MediaInterface::class,
        );

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param MediaSmartContentFilters $filters
     * @param array<string, string> $sortBys
     * @param PropertyParameter[] $params
     */
    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        if (!$this->hasDataSource($filters)) {
            return [];
        }

        $locale = $filters['locale'];

        $alias = 'media';
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->select($alias . '.id as id');
        $queryBuilder->addSelect('fileVersionMeta.title as title');
        $queryBuilder->distinct();

        $this->enhanceQueryBuilder(
            $queryBuilder,
            $filters,
            $sortBys,
            $locale,
            $alias,
            MediaInterface::class,
        );

        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $filters['offset'], $filters['limit']);

        /** @var array<array{id: string, title: string}> $queryResult */
        $queryResult = $queryBuilder->getQuery()->getArrayResult();

        $this->deduplicationStore?->add(MediaInterface::RESOURCE_KEY, ...\array_column($queryResult, 'id'));

        return \array_map(
            function(array $item) {
                // TODO image
                return [
                    'id' => $item['id'],
                    'title' => $item['title'],
                ];
            },
            $queryResult,
        );
    }

    /**
     * Without a selected collection no media is returned, like in Sulu 2.
     *
     * @param MediaSmartContentFilters|MediaSmartContentCountFilters $filters
     */
    private function hasDataSource(array $filters): bool
    {
        return null !== $filters['dataSource'] && '' !== $filters['dataSource'];
    }

    /**
     * Enhances the query builder with filters, sorting, and access control.
     *
     * @param MediaSmartContentFilters|MediaSmartContentCountFilters $filters
     * @param array<string, string> $sortBys
     * @param class-string|null $entityClass
     */
    private function enhanceQueryBuilder(
        QueryBuilder $queryBuilder,
        array $filters,
        array $sortBys,
        string $locale,
        string $alias,
        ?string $entityClass = null,
    ): void {
        $webspace = $this->webspaceManager->findWebspaceByKey($filters['webspaceKey'] ?? null);
        /** @var UserInterface|null $user */
        $user = $webspace && $webspace->hasWebsiteSecurity() ? $this->security?->getUser() : null;
        /** @var int|null $permission */
        $permission = $webspace && $webspace->hasWebsiteSecurity() && $this->permissions
            ? $this->permissions[PermissionTypes::VIEW]
            : null;

        $queryBuilder
            ->innerJoin($alias . '.files', 'file')
            ->innerJoin(
                'file.fileVersions',
                'fileVersion',
                Join::WITH,
                'fileVersion.version = file.version',
            );

        $queryBuilder
            ->innerJoin(
                'fileVersion.meta',
                'fileVersionMeta',
                Join::WITH,
                'fileVersionMeta.locale = :locale',
            )
            ->setParameter('locale', $locale);

        foreach ($sortBys as $sortBy => $sortMethod) {
            $queryBuilder->orderBy($sortBy, $sortMethod);
            $queryBuilder->addSelect($sortBy);
        }

        // the uuid of the rendered resource would be cast to an integer by MySQL
        $excluded = \array_filter($filters['excluded'] ?? [], static fn ($id) => \is_int($id) || \ctype_digit($id));
        if ($filters['excludeDuplicates']) {
            $excluded = [...$excluded, ...($this->deduplicationStore?->getIds(MediaInterface::RESOURCE_KEY) ?? [])];
        }
        if ([] !== $excluded) {
            $queryBuilder->andWhere($alias . '.id NOT IN (:excluded)')
                ->setParameter('excluded', $excluded);
        }

        if ($filters['mimetype'] ?? null) {
            $queryBuilder
                ->andWhere('fileVersion.mimeType = :mimeType')
                ->setParameter('mimeType', $filters['mimetype']);
        }

        if (($filters['dataSource'] ?? null) && '' !== $filters['dataSource']) {
            if (!$filters['includeSubFolders']) {
                $queryBuilder->andWhere('collection.id = :collectionId');
                $queryBuilder->setParameter('collectionId', $filters['dataSource']);
            } else {
                $queryBuilder
                    ->innerJoin(
                        CollectionInterface::class,// TODO should this be dynamic?
                        'parentCollection',
                        Join::WITH,
                        'parentCollection.id = :collectionId',
                    )
                    ->andWhere('collection.lft BETWEEN parentCollection.lft AND parentCollection.rgt')
                    ->setParameter('collectionId', $filters['dataSource']);
            }
        }

        $tagIds = $filters['tags'];
        if ([] !== $tagIds) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                'fileVersion.tags',
                'filterTagId',
                'id',
                'tagIds',
                $tagIds,
                $filters['tagOperator'],
            );
        }

        $types = $filters['types'];
        if ([] !== $types) {
            $queryBuilder
                ->andWhere($alias . '.type IN (:types)')
                ->setParameter('types', $filters['types']);
        }

        $categoryIds = $filters['categories'];
        if ([] !== $categoryIds) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                'fileVersion.categories',
                'filterCategoryId',
                'id',
                'categoryIds',
                $categoryIds,
                $filters['categoryOperator'],
            );
        }

        $websiteCategoryIds = $filters['websiteCategories'];
        if ([] !== $websiteCategoryIds) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                'fileVersion.categories',
                'websiteFilterCategoryId',
                'id',
                'websiteCategoryIds',
                $websiteCategoryIds,
                $filters['websiteCategoryOperator'],
            );
        }

        $websiteTagNames = $filters['websiteTags'];
        if ([] !== $websiteTagNames) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                'fileVersion.tags',
                'websiteFilterTagName',
                'name',
                'websiteTagNames',
                $websiteTagNames,
                $filters['websiteTagOperator'],
            );
        }

        if (($filters['audienceTargeting'] ?? null) && ($filters['targetGroupId'] ?? null)) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                'fileVersion.targetGroups',
                'filterTargetGroupId',
                'id',
                'targetGroupIds',
                [$filters['targetGroupId']],
                'AND',
            );
        }

        if ($entityClass && $alias && $permission) {
            $this->accessControlQueryEnhancer->enhance(
                $queryBuilder,
                $user,
                $permission,
                Collection::class,
                'collection',
            );
        }
    }

    public function createQueryBuilder(string $alias): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select($alias)
            ->addSelect('collection')
            ->from(MediaInterface::class, $alias)
            ->innerJoin($alias . '.collection', 'collection');
    }

    public function getType(): string
    {
        return MediaInterface::RESOURCE_KEY;
    }

    public function getResourceLoaderKey(): string
    {
        return MediaResourceLoader::RESOURCE_LOADER_KEY;
    }
}
