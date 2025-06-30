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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\SmartContent;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaSmartContentProvider implements SmartContentProviderInterface
{
    /**
     * @param mixed[]|null $permissions
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
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
     * @return array<int, array<string, string>>
     */
    protected function getTypes(): array
    {
        $types = [];

        if (!$this->entityManager) {
            return $types;
        }

        $repository = $this->entityManager->getRepository(MediaType::class);
        /** @var MediaType $mediaType */
        foreach ($repository->findAll() as $mediaType) {
            $title = $this->translator ? $this->translator->trans('sulu_media.' . $mediaType->getName(), [], 'admin') : $mediaType->getName();
            $types[] = ['type' => $mediaType->getId(), 'title' => $title];
        }

        return $types;
    }

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
            $this->getOptions($params),
            MediaInterface::class,
            $alias,
        );

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        $page = $filters['page'] ?? 1;
        $pageSize = $filters['pageSize'] ?? null; // TODO do we need a limit ?
        $limit = $filters['limit'] ?? null;
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
            $this->getOptions($params),
            MediaInterface::class,
            $alias,
        );

        if (null !== $pageSize && $pageSize > 0) {
            $pageOffset = ($page - 1) * $pageSize;
            $restLimit = $limit - $pageOffset;

            $queryBuilder->setMaxResults($restLimit);
            $queryBuilder->setFirstResult($pageOffset);
        } elseif (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        $result = $queryBuilder->getQuery()->getArrayResult();

        return \array_map(
            function(array $item) {
                // TODO image
                return [
                    'id' => $item['id'],
                    'title' => $item['title'],
                ];
            },
            $result,
        );
    }

    /**
     * Resolves filter and returns id array for second query.
     *
     * @param array $filters array of filters: tags, tagOperator
     * @param mixed[] $options
     * @param class-string $entityClass
     */
    private function enhanceQueryBuilder(
        QueryBuilder $queryBuilder,
        array $filters,
        array $sortBys,
        string $locale,
        array $options = [],
        ?string $entityClass = null,
        ?string $entityAlias = null,
    ): void {
        $alias = 'media';

        $webspace = $this->webspaceManager->findWebspaceByKey($filters['webspaceKey'] ?? null);
        $user = $this->security && $webspace && $webspace->hasWebsiteSecurity() ? $this->security->getUser() : null;
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
            ->leftJoin(
                'fileVersion.meta',
                'fileVersionMeta',
                Join::WITH,
                'fileVersionMeta.locale = :locale',
            )
            ->setParameter('locale', $locale);

        foreach ($sortBys as $sortBy => $sortMethod) {
            if (!\is_string($sortBy) || !\is_string($sortMethod)) {
                continue;
            }
            $queryBuilder->orderBy($sortBy, $sortMethod);
            $queryBuilder->addSelect($sortBy);
        }

        if (\array_key_exists('mimetype', $options)) {
            $queryBuilder
                ->andWhere('fileVersion.mimeType = :mimeType')
                ->setParameter('mimeType', $options['mimetype']);
        }
        if (\array_key_exists('type', $options)) {
            $queryBuilder
                ->innerJoin($alias . '.type', 'type')
                ->andWhere('type.name = :type')
                ->setParameter('type', $options['type']);
        }

        if (($filters['dataSource'] ?? null) && '' !== $filters['dataSource']) {
            $includeSubFolders = ($filters['includeSubFolders'] ?? null) === 'true' || ($filters['includeSubFolders'] ?? null) === true;
            if (!$includeSubFolders) {
                $queryBuilder->andWhere('collection.id = :collectionId');
                $queryBuilder->setParameter('collectionId', $filters['dataSource']);
            } else {
                $queryBuilder
                    ->innerJoin(
                        //                    $this->collectionEntityName,
                        CollectionInterface::class,// TODO should this be dynamic?
                        'parentCollection',
                        Join::WITH,
                        'parentCollection.id = :collectionId',
                    )
                    ->where('collection.lft BETWEEN parentCollection.lft AND parentCollection.rgt')
                    ->setParameter('collectionId', $filters['dataSource']);
            }
        }

        if (($filters['tagNames'] ?? null) && [] !== $filters['tagNames']) {
            $this->addJoinFilter(
                $queryBuilder,
                'fileVersion.tags',
                'filterTagName',
                'name',
                'tagNames',
                $filters['tagNames'],
                $filters['tagOperator'],
            );
        }

        if (($filters['types'] ?? null) && [] !== $filters['types']) {
            $this->addJoinFilter(
                $queryBuilder,
                $alias . '.type',
                'filterTypeId',
                'id',
                'typeId',
                $filters['types'],
            );
        }

        if (($filters['categoryIds'] ?? null) && [] !== $filters['categoryIds']) {
            $this->addJoinFilter(
                $queryBuilder,
                'fileVersion.categories',
                'filterCategoryId',
                'id',
                'categoryIds',
                $filters['categoryIds'],
                $filters['categoryOperator'],
            );
        }

        if (($filters['targetGroupId'] ?? null) && '' !== $filters['targetGroupId']) {
            $this->addJoinFilter(
                $queryBuilder,
                'fileVersion.targetGroups',
                'filterTargetGroupId',
                'id',
                'targetGroupIds',
                [$filters['targetGroupId']],
                'AND',
            );
        }

        if ($this->accessControlQueryEnhancer && $entityClass && $entityAlias && $permission) {
            $this->accessControlQueryEnhancer->enhance(
                $queryBuilder,
                $user,
                $permission,
                $entityClass,
                $entityAlias,
            );
        }
    }

    protected function getOptions(
        array $propertyParameter,
        array $options = [],
    ) {
        $request = $this->requestStack->getCurrentRequest();

        $queryOptions = [];

        if (\array_key_exists('mimetype_parameter', $propertyParameter)) {
            $queryOptions['mimetype'] = $request->get($propertyParameter['mimetype_parameter']->getValue());
        }
        if (\array_key_exists('type_parameter', $propertyParameter)) {
            $queryOptions['type'] = $request->get($propertyParameter['type_parameter']->getValue());
        }

        return \array_merge($options, \array_filter($queryOptions));
    }

    /**
     * @param int[]|string[] $parameters
     * @param 'AND'|'OR' $operator
     */
    private function addJoinFilter(
        QueryBuilder $queryBuilder,
        string $join,
        string $targetAlias,
        string $targetField,
        string $filterKey,
        array $parameters,
        string $operator = 'OR',
    ): void {
        if ('OR' === $operator) {
            $queryBuilder->leftJoin(
                $join,
                $targetAlias,
            );

            $queryBuilder->andWhere($targetAlias . '.' . $targetField . ' IN (:' . $filterKey . ')')
                ->setParameter($filterKey, $parameters);
        } elseif ('AND' === $operator) {
            foreach (\array_values($parameters) as $key => $parameter) {
                $queryBuilder->leftJoin(
                    $join,
                    $targetAlias . $key,
                );

                $queryBuilder->andWhere($targetAlias . $key . '.' . $targetField . ' = :' . $filterKey . $key)
                    ->setParameter($filterKey . $key, $parameter);
            }
        } else {
            throw new \InvalidArgumentException(
                \sprintf('The operator "%s" is not supported for this filter.', $operator),
            );
        }
    }

    public function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select($alias)
            ->addSelect('collection')
            ->from(MediaInterface::class, $alias, $indexBy)
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
