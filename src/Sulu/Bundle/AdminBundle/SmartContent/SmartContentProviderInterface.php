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

namespace Sulu\Bundle\AdminBundle\SmartContent;

use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;

/**
 * @phpstan-type SmartContentBaseFilters array{
 *      categories: int[],
 *      categoryOperator: 'AND'|'OR',
 *      websiteCategories: string[],
 *      websiteCategoryOperator: 'AND'|'OR',
 *      tags: string[],
 *      tagOperator: 'AND'|'OR',
 *      websiteTags: string[],
 *      websiteTagOperator: 'AND'|'OR',
 *      types: string[],
 *      typesOperator: 'OR',
 *      templateKeys?: string[],
 *      locale: string,
 *      dataSource: string|null,
 *      limit: int|null,
 *      offset: int,
 *      includeSubFolders: bool,
 *      excludeDuplicates: bool,
 *      webspaceKey?: string|null,
 *  }
 * @phpstan-type SmartContentCountBaseFilters array{
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
 *       templateKeys?: string[],
 *       locale: string,
 *       dataSource: string|null,
 *       limit: int|null,
 *       includeSubFolders: bool,
 *       excludeDuplicates: bool,
 *   }
 */
interface SmartContentProviderInterface
{
    public function getConfiguration(): ProviderConfigurationInterface;

    /**
     * @param SmartContentCountBaseFilters $filters
     * @param array<string, mixed> $params
     */
    public function countBy(array $filters, array $params = []): int;

    /**
     * @param SmartContentBaseFilters $filters
     * @param array<string, string> $sortBys
     * @param array<string, mixed> $params
     *
     * @return array<array{id: string, title: string}>
     */
    public function findFlatBy(array $filters, array $sortBys, array $params = []): array;

    public function getType(): string;

    // TODO adjust ResourceLoaders to use ResourceKeys to get rid of this method and use the `getType` resource as the default resource loader key.
    public function getResourceLoaderKey(): string;
}
