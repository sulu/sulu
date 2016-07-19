<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;

/**
 * Interface for DataProviders which will be usable in SmartContent component.
 */
interface DataProviderInterface
{
    /**
     * Returns configuration for smart-content.
     *
     * @return ProviderConfigurationInterface
     */
    public function getConfiguration();

    /**
     * Returns default parameter.
     *
     * @return PropertyParameter[]
     */
    public function getDefaultPropertyParameter();

    /**
     * Resolves given filters and returns filtered data items.
     *
     * @param array $filters Contains the filter configuration
     * @param PropertyParameter[] $propertyParameter Contains the parameter of resolved property
     * @param int|null $limit Indicates maximum size of result set
     * @param int $page Indicates page of result set
     * @param int|null $pageSize Indicates page-size of result set
     * @param array $options Options like webspace or locale
     *
     * @return DataProviderResult
     */
    public function resolveDataItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    );

    /**
     * Resolves given filters and returns filtered resource items with ArrayAccess.
     *
     * @param array $filters Contains the filter configuration
     * @param PropertyParameter[] $propertyParameter Contains the parameter of resolved property
     * @param int|null $limit Indicates maximum size of result set
     * @param int $page Indicates page of result set
     * @param int|null $pageSize Indicates page-size of result set
     * @param array $options Options like webspace or locale
     *
     * @return DataProviderResult
     */
    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    );

    /**
     * Resolves datasource and returns the data of it.
     *
     * @param mixed $datasource Identification of datasource
     * @param PropertyParameter[] $propertyParameter Contains the parameter of resolved property
     * @param array $options Options like webspace or locale
     *
     * @return DatasourceItemInterface
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options);
}
