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

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\BuilderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentQueryEnhancer;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Route\Domain\Model\Route;

readonly class PageTreeArticleSmartContentProvider extends ArticleSmartContentProvider
{
    public const PROVIDER_TYPE = 'articles_page_tree';

    public function __construct(
        DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
        SmartContentQueryEnhancer $smartContentQueryEnhancer,
        EntityManagerInterface $entityManager,
        GroupProviderInterface $groupProvider,
    ) {
        parent::__construct(
            $dimensionContentQueryEnhancer,
            $smartContentQueryEnhancer,
            $entityManager,
            $groupProvider,
        );
    }

    protected function getConfigurationBuilder(): BuilderInterface
    {
        return parent::getConfigurationBuilder()
            ->enableDatasource(
                PageInterface::RESOURCE_KEY,
                PageInterface::RESOURCE_KEY,
                'column_list'
            );
    }

    /**
     * @param array{
     *     websiteCategories: string[],
     *     websiteCategoryOperator: 'AND'|'OR',
     *     websiteTags: string[],
     *     websiteTagOperator: 'AND'|'OR',
     *     dataSource?: string|null,
     *     locale?: string|null,
     *     includeSubFolders?: bool,
     * } $filters
     */
    protected function addInternalFilters(QueryBuilder $queryBuilder, array $filters, string $alias): void
    {
        parent::addInternalFilters($queryBuilder, $filters, $alias);

        $dataSource = $filters['dataSource'] ?? null;
        if (null === $dataSource || '' === $dataSource) {
            return;
        }

        $locale = $filters['locale'] ?? null;
        $includeSubFolders = $filters['includeSubFolders'] ?? false;

        $queryBuilder->join(
            Route::class,
            'articleRoute',
            'WITH',
            'articleRoute.resourceKey = :articleResourceKey
             AND articleRoute.resourceId = ' . $alias . '.uuid
             AND articleRoute.locale = :routeLocale'
        );
        $queryBuilder->setParameter('articleResourceKey', ArticleInterface::RESOURCE_KEY);
        $queryBuilder->setParameter('routeLocale', $locale);

        $queryBuilder->join('articleRoute.parentRoute', 'parentRoute');

        if (!$includeSubFolders) {
            // Only match articles whose parent route points directly to the dataSource page
            $queryBuilder->andWhere('parentRoute.resourceId = :dataSource');
            $queryBuilder->setParameter('dataSource', $dataSource);
        } else {
            // Match articles whose parent route points to the dataSource page or any of its descendants
            // Using the nested set model (lft/rgt) of Page to efficiently query descendants
            $queryBuilder->join(
                PageInterface::class,
                'dataSourcePage',
                'WITH',
                'dataSourcePage.uuid = :dataSource'
            );
            $queryBuilder->join(
                PageInterface::class,
                'targetPage',
                'WITH',
                'targetPage.uuid = parentRoute.resourceId'
            );
            $queryBuilder->andWhere('targetPage.lft BETWEEN dataSourcePage.lft AND dataSourcePage.rgt');
            $queryBuilder->setParameter('dataSource', $dataSource);
        }
    }

    public function getType(): string
    {
        return self::PROVIDER_TYPE;
    }
}
