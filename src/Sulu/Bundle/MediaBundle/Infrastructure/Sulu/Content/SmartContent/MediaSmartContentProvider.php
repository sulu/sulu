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
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-type MediaSmartContentFilters array{
 *       categories: int[],
 *       categoryOperator: 'AND'|'OR',
 *       websiteCategories: string[],
 *       websiteCategoryOperator: 'AND'|'OR',
 *       tags: string[],
 *       tagOperator: 'AND'|'OR',
 *       websiteTags: string[],
 *       websiteTagOperator: 'AND'|'OR',
 *       types: string[],
 *       typesOperator: 'OR',
 *       locale: string,
 *       dataSource: string|null,
 *       limit: int|null,
 *       page: int,
 *       maxPerPage: int|null,
 *       includeSubFolders: bool,
 *       excludeDuplicates: bool,
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
        private AccessControlQueryEnhancer $accessControlQueryEnhancer,
        private Security $security,
        private bool $hasAudienceTargeting = false,
        private ?array $permissions = null,
    ) {
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
        /** @var array{type: string, title: string}[] $types */
        $types = [];

        $repository = $this->entityManager->getRepository(MediaType::class);
        /** @var MediaType $mediaType */
        foreach ($repository->findAll() as $mediaType) {
            $types[] = [
                'type' => (string) $mediaType->getId(),
                'title' => $this->translator->trans('sulu_media.' . $mediaType->getName(), [], 'admin'),
            ];
        }

        return $types;
    }

    /**
     * @param MediaSmartContentFilters $filters
     * @param PropertyParameter[] $params
     */
    public function countBy(array $filters, array $params = []): int
    {
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

        $page = $filters['page'];
        $pageSize = $filters['maxPerPage'];
        $limit = $filters['limit'];
        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $page, $pageSize, $limit);

        /** @var array<array{id: string, title: string}> $queryResult */
        $queryResult = $queryBuilder->getQuery()->getArrayResult();

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
     * Enhances the query builder with filters, sorting, and access control.
     *
     * @param MediaSmartContentFilters $filters
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
        $user = $webspace && $webspace->hasWebsiteSecurity() ? $this->security->getUser() : null;
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

        if ($filters['mimetype'] ?? null) {
            $queryBuilder
                ->andWhere('fileVersion.mimeType = :mimeType')
                ->setParameter('mimeType', $filters['mimetype']);
        }
        if ($filters['type'] ?? null) {
            $queryBuilder
                ->innerJoin($alias . '.type', 'type')
                ->andWhere('type.name = :type')
                ->setParameter('type', $filters['type']);
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
                    ->where('collection.lft BETWEEN parentCollection.lft AND parentCollection.rgt')
                    ->setParameter('collectionId', $filters['dataSource']);
            }
        }

        $tagNames = $filters['tags'];
        if ([] !== $tagNames) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                'fileVersion.tags',
                'filterTagName',
                'name',
                'tagNames',
                $tagNames,
                $filters['tagOperator'],
            );
        }

        $types = $filters['types'];
        if ([] !== $types) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                $alias . '.type',
                'filterTypeId',
                'id',
                'typeId',
                $types,
            );
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
                $entityClass,
                $alias,
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
