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

namespace Sulu\Content\Infrastructure\Doctrine;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\TaxonomyInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Webmozart\Assert\Assert;

/**
 * TODO add loadShadow functionality.
 *
 * @final
 */
class DimensionContentQueryEnhancer
{
    /**
     * Withs represents additional selects which can be load to join and select specific sub entities.
     * They are used by groups and fields.
     */
    public const SELECT_EXCERPT_TAGS = 'excerpt-tags';
    public const SELECT_EXCERPT_CATEGORIES = 'excerpt-categories';
    public const SELECT_EXCERPT_CATEGORIES_TRANSLATION = 'excerpt-categories-translation';
    public const SELECT_EXCERPT_AUDIENCE_TARGET_GROUPS = 'excerpt-audience-target-groups';
    public const SELECT_ROUTE = 'route';

    /**
     * Groups are used in controllers and represents serialization / resolver group,
     * this allows that no controller need to be overwritten when something additional should be
     * loaded at that endpoint.
     */
    public const GROUP_SELECT_CONTENT_ADMIN = 'content_admin';
    public const GROUP_SELECT_CONTENT_WEBSITE = 'content_website';

    /**
     * TODO it should be possible to extend fields and groups inside the SELECTS.
     */
    private const SELECTS = [
        // GROUPS
        self::GROUP_SELECT_CONTENT_ADMIN => [
            self::SELECT_EXCERPT_TAGS => true,
            self::SELECT_EXCERPT_CATEGORIES => true,
            self::SELECT_ROUTE => true,
        ],
        self::GROUP_SELECT_CONTENT_WEBSITE => [
            self::SELECT_EXCERPT_TAGS => true,
            self::SELECT_EXCERPT_CATEGORIES => true,
            self::SELECT_EXCERPT_CATEGORIES_TRANSLATION => true,
            self::SELECT_ROUTE => true,
        ],
    ];

    /**
     * TODO it should be possible to add custom filters for all contents here example when the
     *     excerpt tab and entity get extended with an additional field.
     *
     * @template T of DimensionContentInterface
     *
     * @param class-string<T> $dimensionContentClassName
     * @param array{
     *     locale?: string|null,
     *     stage?: string|null,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     loadGhost?: bool,
     *     excluded?: string[],
     *     includeSubFolders?: bool,
     *     audienceTargeting?: bool,
     *     targetGroupId?: int,
     *     segmentKey?: string,
     *     version?: int|null,
     * } $filters
     * @param array{
     *     title?: 'asc'|'desc',
     *     authored?: 'asc'|'desc',
     *     workflowPublished?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     *     changed?: 'asc'|'desc',
     * } $sortBys
     */
    public function addFilters(
        QueryBuilder $queryBuilder,
        string $contentRichEntityAlias,
        string $dimensionContentClassName,
        array $filters,
        array $sortBys,
    ): void {
        $effectiveAttributes = $dimensionContentClassName::getEffectiveDimensionAttributes($filters);

        // Use INNER JOIN when any filter adds a non-nullable WHERE on the joined table.
        // These conditions eliminate NULL rows, making LEFT JOIN semantically equivalent
        // to INNER JOIN — but MySQL can optimize INNER JOINs significantly better
        // (reorder tables, push conditions into index access).
        // Filters with "OR ... IS NULL" fallbacks (audienceTargeting, segmentKey) preserve
        // NULLs and are excluded from this check.
        $hasStrictFilters = $this->hasStrictDimensionContentFilters($dimensionContentClassName, $filters);

        $joinMethod = $hasStrictFilters ? 'innerJoin' : 'leftJoin';

        $queryBuilder->$joinMethod(
            $dimensionContentClassName,
            'filterDimensionContent',
            Join::WITH,
            'filterDimensionContent.' . $contentRichEntityAlias . ' = ' . $contentRichEntityAlias,
        );

        foreach ($effectiveAttributes as $key => $value) {
            if (null === $value) {
                $queryBuilder->andWhere('filterDimensionContent.' . $key . ' IS NULL');

                continue;
            }

            if ('locale' === $key && ($filters['loadGhost'] ?? false)) {
                // do not filter by locale when loadGhost is active
                continue;
            }

            $queryBuilder->andWhere('filterDimensionContent.' . $key . '= :' . $key)
                ->setParameter($key, $value);
        }

        if ($version = $filters['version'] ?? null) {
            $queryBuilder->andWhere('filterDimensionContent.version = :version')
                ->setParameter('version', $version);
        }

        if (\is_subclass_of($dimensionContentClassName, ExcerptInterface::class)) {
            $categoryIds = $filters['categoryIds'] ?? null;
            if ($categoryIds) {
                Assert::isArray($categoryIds); // @phpstan-ignore staticMethod.alreadyNarrowedType

                $this->addJoinFilter(
                    $queryBuilder,
                    'filterDimensionContent.excerptCategories',
                    'filterCategoryId',
                    'id',
                    'categoryIds',
                    $categoryIds,
                    $filters['categoryOperator'] ?? 'OR',
                );
            }

            $categoryKeys = $filters['categoryKeys'] ?? null;
            if ($categoryKeys) {
                Assert::isArray($categoryKeys); // @phpstan-ignore staticMethod.alreadyNarrowedType

                $this->addJoinFilter(
                    $queryBuilder,
                    'filterDimensionContent.excerptCategories',
                    'filterCategoryId',
                    'key',
                    'categoryKeys',
                    $categoryKeys,
                    $filters['categoryOperator'] ?? 'OR',
                );
            }

            $tagIds = $filters['tagIds'] ?? null;
            if ($tagIds) {
                Assert::isArray($tagIds); // @phpstan-ignore staticMethod.alreadyNarrowedType

                $this->addJoinFilter(
                    $queryBuilder,
                    'filterDimensionContent.excerptTags',
                    'filterTagId',
                    'id',
                    'tagIds',
                    $tagIds,
                    $filters['tagOperator'] ?? 'OR',
                );
            }

            $tagNames = $filters['tagNames'] ?? null;
            if ($tagNames) {
                Assert::isArray($tagNames); // @phpstan-ignore staticMethod.alreadyNarrowedType

                $this->addJoinFilter(
                    $queryBuilder,
                    'filterDimensionContent.excerptTags',
                    'filterTagName',
                    'name',
                    'tagNames',
                    $tagNames,
                    $filters['tagOperator'] ?? 'OR',
                );
            }
        }

        if (\is_subclass_of($dimensionContentClassName, TemplateInterface::class)) {
            $templateKeys = $filters['templateKeys'] ?? null;
            if ($templateKeys) {
                Assert::isArray($templateKeys); // @phpstan-ignore staticMethod.alreadyNarrowedType

                $queryBuilder->andWhere('filterDimensionContent.templateKey IN (:templateKeys)')
                    ->setParameter('templateKeys', $templateKeys);
            }
        }

        $excluded = $filters['excluded'] ?? null;
        if (\is_array($excluded) && [] !== $excluded) {
            $queryBuilder->andWhere($contentRichEntityAlias . '.uuid NOT IN (:excluded)')
                ->setParameter('excluded', $excluded);
        }

        if (($filters['audienceTargeting'] ?? false) && ($filters['targetGroupId'] ?? null)) {
            Assert::integerish($filters['targetGroupId']); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->leftJoin('filterDimensionContent.excerptAudienceTargetGroups', 'filterAudienceTargetGroup')
                ->andWhere('filterAudienceTargetGroup.id = :targetGroupId OR filterAudienceTargetGroup IS NULL')
                ->setParameter('targetGroupId', (int) $filters['targetGroupId']);
        }

        if ($filters['segmentKey'] ?? null) {
            $segmentKey = $filters['segmentKey'];
            Assert::string($segmentKey); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('filterDimensionContent.excerptSegment = :segmentKey OR filterDimensionContent.excerptSegment IS NULL')
                ->setParameter('segmentKey', $segmentKey);
        }

        // Sort by
        foreach ($sortBys as $field => $order) {
            if (\in_array($field, ['title', 'authored', 'workflowPublished'], true)) {
                $queryBuilder->addOrderBy('filterDimensionContent.' . $field, $order);
            } elseif (\in_array($field, ['created', 'changed'], true)) {
                $queryBuilder->addOrderBy($contentRichEntityAlias . '.' . $field, $order);
            }
        }
    }

    /**
     * TODO it should be possible to add custom select for all contents here example when the
     *     excerpt tab and entity get extended with additional relation.
     *
     * @template T of DimensionContentInterface
     *
     * @param class-string<T> $dimensionContentClassName
     * @param mixed[] $dimensionAttributes
     * @param array{
     *     content_admin?: bool,
     *     content_website?: bool,
     *     with-excerpt-tags?: bool,
     *     with-excerpt-categories?: bool,
     *     with-excerpt-categories-translation?: bool,
     *     with-excerpt-audience-target-groups?: bool,
     *     with-excerpt-image?: bool,
     *     with-excerpt-image-translation?: bool,
     *     with-excerpt-icon?: bool,
     *     with-excerpt-icon-translation?: bool,
     * }|array<string, bool> $selects
     */
    public function addSelects(
        QueryBuilder $queryBuilder,
        string $dimensionContentClassName,
        array $dimensionAttributes,
        array $selects = [],
    ): void {
        foreach ($selects as $selectGroup => $value) {
            if (!$value) {
                continue;
            }

            if (isset(self::SELECTS[$selectGroup])) {
                $selects = \array_merge($selects, self::SELECTS[$selectGroup]);
            }
        }

        $effectiveAttributes = $dimensionContentClassName::getEffectiveDimensionAttributes($dimensionAttributes);
        $queryBuilder->addCriteria($this->getAttributesCriteria('dimensionContent', $effectiveAttributes));
        $queryBuilder->addSelect('dimensionContent');

        $locale = $dimensionAttributes['locale'] ?? null;

        if (\is_subclass_of($dimensionContentClassName, TaxonomyInterface::class)) {
            if ($selects[self::SELECT_EXCERPT_TAGS] ?? false) {
                $queryBuilder->leftJoin('dimensionContent.excerptTags', 'contentExcerptTag')
                    ->addSelect('contentExcerptTag');
            }

            if ($selects[self::SELECT_EXCERPT_CATEGORIES] ?? false) {
                $queryBuilder->leftJoin('dimensionContent.excerptCategories', 'contentExcerptCategory')
                    ->addSelect('contentExcerptCategory');
            }

            if (($selects[self::SELECT_EXCERPT_CATEGORIES_TRANSLATION] ?? false) && (null !== $locale)) {
                Assert::notFalse($selects[self::SELECT_EXCERPT_CATEGORIES] ?? false);

                $locales = (array) $locale;
                $queryBuilder
                    ->leftJoin(
                        'contentExcerptCategory.translations',
                        'contentExcerptCategoryTranslation',
                        Join::WITH,
                        'contentExcerptCategoryTranslation.locale = contentExcerptCategory.defaultLocale OR contentExcerptCategoryTranslation.locale IN (:locales)'
                    )
                    ->addSelect('contentExcerptCategoryTranslation')
                    ->setParameter('locales', $locales, ArrayParameterType::STRING);
            }

            if ($selects[self::SELECT_EXCERPT_AUDIENCE_TARGET_GROUPS] ?? false) {
                $queryBuilder->leftJoin('dimensionContent.excerptAudienceTargetGroups', 'contentExcerptAudienceTargetGroup')
                    ->addSelect('contentExcerptAudienceTargetGroup');
            }
        }

        if (\is_subclass_of($dimensionContentClassName, RoutableInterface::class)) {
            if ($selects[self::SELECT_ROUTE] ?? false) {
                $queryBuilder->leftJoin('dimensionContent.route', 'route')
                    ->addSelect('route');
            }
        }
    }

    /**
     * @param mixed[] $attributes
     */
    private function getAttributesCriteria(string $alias, array $attributes): Criteria
    {
        $criteria = Criteria::create();

        foreach ($attributes as $key => $value) {
            $fieldName = $alias . '.' . $key;

            $expr = $criteria->expr()->isNull($fieldName);
            if (null !== $value) {
                $valueExpr = $criteria->expr()->in($fieldName, (array) $value);
                $expr = $criteria->expr()->orX($expr, $valueExpr);
            }
            $criteria->andWhere($expr);
        }

        return $criteria;
    }

    /**
     * @see SmartContentQueryEnhancer::addJoinFilter
     *
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
     * Checks if any filter adds a non-nullable WHERE condition on the dimension content join.
     *
     * @template T of DimensionContentInterface
     *
     * @param class-string<T> $dimensionContentClassName
     * @param array<string, mixed> $filters
     */
    private function hasStrictDimensionContentFilters(string $dimensionContentClassName, array $filters): bool
    {
        if (\is_subclass_of($dimensionContentClassName, TemplateInterface::class)
            && !empty($filters['templateKeys'] ?? null)
        ) {
            return true;
        }

        if (\is_subclass_of($dimensionContentClassName, ExcerptInterface::class)) {
            if (!empty($filters['categoryIds'] ?? null)
                || !empty($filters['categoryKeys'] ?? null)
                || !empty($filters['tagIds'] ?? null)
                || !empty($filters['tagNames'] ?? null)
            ) {
                return true;
            }
        }

        // audienceTargeting and segmentKey use "OR ... IS NULL" fallbacks,
        // so they preserve NULLs and don't qualify as strict filters.

        return false;
    }
}
