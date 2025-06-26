<?php

declare(strict_types=1);

namespace Sulu\Bundle\AdminBundle\SmartContent;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaSmartContentProvider implements SmartContentProviderInterface
{
    /**
     * @var EntityRepository<MediaInterface>
     */
    private EntityRepository $entityRepository;

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
        $this->entityRepository = $entityManager->getRepository(MediaInterface::class);
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
        $webspace = $this->webspaceManager->findWebspaceByKey($filters['webspaceKey'] ?? null);
        $user = $this->security && $webspace && $webspace->hasWebsiteSecurity() ? $this->security->getUser() : null;
        $permission = $webspace && $webspace->hasWebsiteSecurity() && $this->permissions
            ? $this->permissions[PermissionTypes::VIEW]
            : null;

        $alias = 'media';
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->select(\sprintf('COUNT(DISTINCT %s.id)', $alias));
        $this->enhanceQueryBuilder(
            $queryBuilder,
            $filters,
            [],
            $filters['locale'],
            $this->getOptions($params),
            $user,
            MediaInterface::class,
            $alias,
            $permission,
        );

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($filters['webspaceKey'] ?? null);
        $user = $this->security && $webspace && $webspace->hasWebsiteSecurity() ? $this->security->getUser() : null;
        $permission = $webspace && $webspace->hasWebsiteSecurity() && $this->permissions
            ? $this->permissions[PermissionTypes::VIEW]
            : null;

        $page = $filters['page'] ?? 1;
        $pageSize = $filters['pageSize'] ?? null; // TODO do we need a limit ?
        $limit = $filters['limit'] ?? null;
        $locale = $filters['locale'];

        $alias = 'media';
        $queryBuilder = $this->createQueryBuilder($alias);
        $this->enhanceQueryBuilder(
            $queryBuilder,
            $filters,
            $sortBys,
            $locale,
            $this->getOptions($params),
            $user,
            MediaInterface::class,
            $alias,
            $permission,
        );

        $queryBuilder->select($alias . '.id as id');
        $queryBuilder->addSelect('fileVersionMeta.title as title');
        $queryBuilder->distinct();

        $queryBuilder
            ->leftJoin(
                'fileVersion.meta',
                'fileVersionMeta',
                Join::WITH,
                'fileVersionMeta.locale = :locale',
            )
            ->setParameter('locale', $locale);

        foreach ($queryBuilder->getDQLPart('orderBy') ?? [] as $orderBy) {
            foreach ($orderBy->getParts() as $order) {
                [$column] = \explode(' ', $order);
                $queryBuilder->addSelect($column);
            }
        }

        if (null !== $pageSize && $pageSize > 0) {
            $pageOffset = ($page - 1) * $pageSize;
            $restLimit = $limit - $pageOffset;

            // if limitation is smaller than the page size then use the rest limit else use page size plus 1 to
            // determine has next page
            $maxResults = (null !== $limit && $pageSize > $restLimit ? $restLimit : ($pageSize + 1));

            if ($maxResults <= 0) {
                return [];
            }

            $queryBuilder->setMaxResults($maxResults);
            $queryBuilder->setFirstResult($pageOffset);
        } elseif (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * Resolves filter and returns id array for second query.
     *
     * @param array $filters array of filters: tags, tagOperator
     * @param mixed[] $options
     * @param class-string $entityClass
     *
     * @return int[]|string[]
     */
    private function enhanceQueryBuilder(
        QueryBuilder $queryBuilder,
        array $filters,
        array $sortBys,
        string $locale,
        array $options = [],
        ?UserInterface $user = null,
        ?string $entityClass = null,
        ?string $entityAlias = null,
        ?int $permission = null,
    ) {
        $alias = 'media';

        $tagRelation = $this->appendTagsRelation($queryBuilder, $alias);
        $categoryRelation = $this->appendCategoriesRelation($queryBuilder, $alias);

        foreach ($sortBys as $sortBy => $sortMethod) {
            if (!\is_string($sortBy) || !\is_string($sortMethod)) {
                continue;
            }
            $queryBuilder->orderBy($sortBy, $sortMethod);
            $queryBuilder->addSelect($sortBy);
        }

        $parameter = $this->append($queryBuilder, $alias, $locale, $options);

        if (isset($filters['dataSource'])) {
            $includeSubFolders = ($filters['includeSubFolders'] ?? null) === 'true' || ($filters['includeSubFolders'] ?? null) === true;
            $parameter = \array_merge(
                $parameter,
                $this->appendDatasource($filters['dataSource'], $includeSubFolders, $queryBuilder, $alias),
            );
        }

        if (isset($filters['tagNames']) && !empty($filters['tagNames'])) {
            $this->addJoinFilter(
                $queryBuilder,
                $tagRelation,
                'filterTagName',
                'name',
                'tagNames',
                $filters['tagNames'],
                $filters['tagOperator'],
            );
        }

        if (isset($filters['types']) && !empty($filters['types'])) {
            $typeRelation = $alias . '.type';
            $this->addJoinFilter(
                $queryBuilder,
                $typeRelation,
                'filterTypeId',
                'id',
                'typeId',
                $filters['types'],
            );
        }

        if (isset($filters['categoryIds']) && !empty($filters['categoryIds'])) {
            $this->addJoinFilter(
                $queryBuilder,
                $categoryRelation,
                'filterCategoryId',
                'id',
                'categoryIds',
                $filters['categoryIds'],
                $filters['categoryOperator'],
            );
        }

        if (isset($filters['targetGroupId']) && $filters['targetGroupId']) {
            $targetGroupRelation = $this->appendTargetGroupRelation($queryBuilder, $alias);
            $parameter = \array_merge(
                $parameter,
                $this->appendRelation(
                    $queryBuilder,
                    $targetGroupRelation,
                    [$filters['targetGroupId']],
                    'and',
                    'targetGroupId',
                ),
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

        foreach ($parameter as $name => $value) {
            $queryBuilder->setParameter($name, $value);
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
     * Append additional condition to query builder for "findByFilters" function.
     *
     * @param string $alias
     * @param string $locale
     * @param mixed[] $options
     *
     * @return array<string, int|string|int[]|string[]> parameters for query
     */
    protected function append(QueryBuilder $queryBuilder, $alias, $locale, $options = [])
    {
        $parameter = [];

        if (\array_key_exists('mimetype', $options)) {
            $queryBuilder
                ->andWhere('fileVersion.mimeType = :mimeType');

            $parameter['mimeType'] = $options['mimetype'];
        }
        if (\array_key_exists('type', $options)) {
            $queryBuilder
                ->innerJoin($alias . '.type', 'type')
                ->andWhere('type.name = :type');

            $parameter['type'] = $options['type'];
        }

        /** @var array<string, string> */
        return $parameter;
    }

    /**
     * Extension point to append relations to tag relation if it is not direct linked.
     */
    protected function appendTagsRelation(QueryBuilder $queryBuilder, string $alias): string
    {
        // TODO ????
        $queryBuilder
            ->innerJoin($alias . '.files', 'file')
            ->innerJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version');

        return 'fileVersion.tags';
    }

    protected function appendCategoriesRelation(QueryBuilder $queryBuilder, string $alias): string
    {
        return 'fileVersion.categories';
    }

    protected function appendTargetGroupRelation(QueryBuilder $queryBuilder, string $alias): string
    {
        return 'fileVersion.targetGroups';
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

    /**
     * @return array<string, int|string|int[]|string[]> parameters for query
     */
    protected function appendDatasource(int|string $datasource, bool $includeSubFolders, QueryBuilder $queryBuilder, string $alias): array
    {
        if (!$includeSubFolders) {
            $queryBuilder->andWhere('collection.id = :collectionId');
        } else {
            $queryBuilder
                ->innerJoin(
                    //                    $this->collectionEntityName,
                    CollectionInterface::class,// TODO should this be dynamic?
                    'parentCollection',
                    Join::WITH,
                    'parentCollection.id = :collectionId',
                )
                ->where('collection.lft BETWEEN parentCollection.lft AND parentCollection.rgt');
        }

        return ['collectionId' => $datasource];
    }

    /**
     * Append tags to query builder with given operator.
     *
     * @param int[] $values
     * @param string $operator "and" or "or"
     *
     * @return array<string, int|string|int[]|string[]> parameter for the query
     */
    private function appendRelation(QueryBuilder $queryBuilder, string $relation, array $values, string $operator, string $alias): array
    {
        return match ($operator) {
            'or' => $this->appendRelationOr($queryBuilder, $relation, $values, $alias),
            'and' => $this->appendRelationAnd($queryBuilder, $relation, $values, $alias),
            default => [],
        };
    }

    /**
     * Append tags to query builder with "or" operator.
     *
     * @param int[] $values
     *
     * @return array<string, int|string|int[]|string[]> parameter for the query
     */
    private function appendRelationOr(QueryBuilder $queryBuilder, string $relation, array $values, string $alias): array
    {
        $queryBuilder->leftJoin($relation, $alias)
            ->andWhere($alias . '.id IN (:' . $alias . ')');

        return [$alias => $values];
    }

    /**
     * Append tags to query builder with "and" operator.
     *
     * @param int[] $values
     *
     * @return array<string, int|string|int[]|string[]> parameter for the query
     */
    private function appendRelationAnd(QueryBuilder $queryBuilder, string $relation, array $values, string $alias): array
    {
        $parameter = [];
        $expr = $queryBuilder->expr()->andX();

        $length = \count($values);
        for ($i = 0; $i < $length; ++$i) {
            $queryBuilder->leftJoin($relation, $alias . $i);

            $expr->add($queryBuilder->expr()->eq($alias . $i . '.id', ':' . $alias . $i));

            $parameter[$alias . $i] = $values[$i];
        }
        $queryBuilder->andWhere($expr);

        return $parameter;
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
