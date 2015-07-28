<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\SmartContent;

use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\SmartContent\Configuration\CategoriesConfiguration;
use Sulu\Component\SmartContent\Configuration\ComponentConfiguration;
use Sulu\Component\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;

/**
 * DataProvider for content.
 */
class ContentDataProvider implements DataProviderInterface
{
    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    /**
     * @var ContentQueryExecutor
     */
    private $contentQueryExecutor;

    /**
     * @var ProviderConfigurationInterface
     */
    private $configuration;

    /**
     * @var bool
     */
    private $hasNextPage;

    public function __construct(
        ContentQueryBuilderInterface $contentQueryBuilder,
        ContentQueryExecutor $contentQueryExecutor
    ) {
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->contentQueryExecutor = $contentQueryExecutor;
    }


    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $propertyParameter)
    {
        if (!$this->configuration) {
            return $this->initConfiguration($propertyParameter);
        }

        return $this->configuration;
    }

    /**
     * Initiate configuration
     *
     * @param array $propertyParameter
     *
     * @return ProviderConfigurationInterface
     */
    private function initConfiguration(array $propertyParameter)
    {
        // TODO
        // * datasource configuration
        // * categories root
        // * sorting
        // * present as

        $this->configuration = new ProviderConfiguration();
        $this->configuration->setDatasource(new ComponentConfiguration('', array()));
        $this->configuration->setTags(true);
        $this->configuration->setCategories(new CategoriesConfiguration());
        $this->configuration->setSorting(array());
        $this->configuration->setLimit(true);
        $this->configuration->setPresentAs(array());
        $this->configuration->setPaginated(true);

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return [
            'properties' => new PropertyParameter('properties', [], 'collection'),
            'present_as' => new PropertyParameter('present_as', [], 'collection'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        $properties = array_key_exists('properties', $propertyParameter) ?
            $propertyParameter['properties']->getValue() : [];

        $this->contentQueryBuilder->init(
            [
                'ids' => [$datasource],
                'properties' => $properties,
            ]
        );

        $result = $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->contentQueryBuilder
        );

        $items = $this->decorate($result);

        return $items[0];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveFilters(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        if (!array_key_exists('dataSource', $filters) ||
            $filters['dataSource'] === '' ||
            ($limit !== null && $limit < 1)
        ) {
            return [];
        }

        $properties = array_key_exists('properties', $propertyParameter) ?
            $propertyParameter['properties']->getValue() : [];

        $this->contentQueryBuilder->init(
            [
                'config' => $filters,
                'properties' => $properties,
                'excluded' => $filters['excluded']
            ]
        );

        if ($pageSize !== null) {
            $result = $this->loadPaginated($options, $limit, $page, $pageSize);
            $this->hasNextPage = (sizeof($result) > $pageSize);

            return $this->decorate(array_splice($result, 0, $pageSize));
        } else {
            return $this->decorate($this->load($options, $limit));
        }
    }

    /**
     * Load paginated data.
     *
     * @param array $options
     * @param int $limit
     * @param int $page
     * @param int $pageSize
     *
     * @return array
     */
    private function loadPaginated(array $options, $limit, $page, $pageSize)
    {
        $pageSize = intval($pageSize);
        $offset = ($page - 1) * $pageSize;

        $position = $pageSize * $page;
        if ($limit !== null && $position >= $limit) {
            $pageSize = $limit - $offset;
            $loadLimit = $pageSize;
        } else {
            $loadLimit = $pageSize + 1;
        }

        return $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->contentQueryBuilder,
            true,
            -1,
            $loadLimit,
            $offset
        );
    }

    /**
     * Load data.
     *
     * @param array $options
     * @param int $limit
     *
     * @return array
     */
    private function load(array $options, $limit)
    {
        return $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->contentQueryBuilder,
            true,
            -1,
            $limit
        );
    }

    /**
     * Decorates result with item class.
     *
     * @param array $data
     *
     * @return array
     */
    private function decorate(array $data)
    {
        return array_map(
            function ($item) {
                return new ContentDataItem($item);
            },
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getHasNextPage()
    {
        $result = $this->hasNextPage;
        $this->hasNextPage = null;

        return $result;
    }
}
