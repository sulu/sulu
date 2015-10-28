<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\SmartContent;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ComponentConfiguration;
use Sulu\Component\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\DatasourceItem;

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
     * @var ContentQueryExecutorInterface
     */
    private $contentQueryExecutor;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ProviderConfigurationInterface
     */
    private $configuration;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    public function __construct(
        ContentQueryBuilderInterface $contentQueryBuilder,
        ContentQueryExecutorInterface $contentQueryExecutor,
        DocumentManagerInterface $documentManager,
        LazyLoadingValueHolderFactory $proxyFactory
    ) {
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->documentManager = $documentManager;
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if (!$this->configuration) {
            return $this->initConfiguration();
        }

        return $this->configuration;
    }

    /**
     * Initiate configuration.
     *
     * @return ProviderConfigurationInterface
     */
    private function initConfiguration()
    {
        $this->configuration = new ProviderConfiguration();
        $this->configuration->setTags(true);
        $this->configuration->setCategories(true);
        $this->configuration->setLimit(true);
        $this->configuration->setPresentAs(true);
        $this->configuration->setPaginated(true);

        $this->configuration->setDatasource(
            new ComponentConfiguration(
                'content-datasource@sulucontent',
                [
                    'url' => '/admin/api/nodes?{id=dataSource&}tree=true&webspace-node=true&webspace={webspace}&language={locale}',
                    'resultKey' => 'nodes',
                ]
            )
        );
        $this->configuration->setSorting(
            [
                new PropertyParameter('title', 'smart-content.title'),
                new PropertyParameter('published', 'smart-content.published'),
                new PropertyParameter('created', 'smart-content.created'),
                new PropertyParameter('changed', 'smart-content.changed'),
            ]
        );

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return [
            'properties' => new PropertyParameter('properties', [], 'collection'),
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
            $this->contentQueryBuilder,
            true,
            -1,
            1,
            0
        );

        if (count($result) === 0) {
            return;
        }

        return new DatasourceItem($result[0]['uuid'], $result[0]['title'], '/' . ltrim($result[0]['path'], '/'));
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDataItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        list($items, $hasNextPage) = $this->resolveFilters(
            $filters,
            $propertyParameter,
            $options,
            $limit,
            $page,
            $pageSize
        );
        $items = $this->decorateDataItems($items, $options['locale']);

        return new DataProviderResult(
            $items,
            $hasNextPage,
            array_map(
                function (ContentDataItem $item) {
                    return $item->getId();
                },
                $items
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        list($items, $hasNextPage) = $this->resolveFilters(
            $filters,
            $propertyParameter,
            $options,
            $limit,
            $page,
            $pageSize
        );
        $items = $this->decorateResourceItems($items, $options['locale']);

        return new DataProviderResult(
            $items,
            $hasNextPage,
            array_map(
                function (ArrayAccessItem $item) {
                    return $item->getId();
                },
                $items
            )
        );
    }

    /**
     * Resolves filters.
     */
    private function resolveFilters(
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
            return [[], false];
        }

        $properties = array_key_exists('properties', $propertyParameter) ?
            $propertyParameter['properties']->getValue() : [];

        $this->contentQueryBuilder->init(
            [
                'config' => $filters,
                'properties' => $properties,
                'excluded' => $filters['excluded'],
            ]
        );

        $hasNextPage = false;
        if ($pageSize !== null) {
            $result = $this->loadPaginated($options, $limit, $page, $pageSize);
            $hasNextPage = (count($result) > $pageSize);
            $items = array_splice($result, 0, $pageSize);
        } else {
            $items = $this->load($options, $limit);
        }

        return [$items, $hasNextPage];
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
     * @param string $locale
     *
     * @return ContentDataItem[]
     */
    private function decorateDataItems(array $data, $locale)
    {
        return array_map(
            function ($item) use ($locale) {
                return new ContentDataItem($item, $this->getResource($item['uuid'], $locale));
            },
            $data
        );
    }

    /**
     * Decorates result with item class.
     *
     * @param array $data
     * @param string $locale
     *
     * @return ArrayAccessItem[]
     */
    private function decorateResourceItems(array $data, $locale)
    {
        return array_map(
            function ($item) use ($locale) {
                return new ArrayAccessItem($item['uuid'], $item, $this->getResource($item['uuid'], $locale));
            },
            $data
        );
    }

    /**
     * Returns Proxy Document for uuid.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return object
     */
    private function getResource($uuid, $locale)
    {
        return $this->proxyFactory->createProxy(
            PageDocument::class,
            function (
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($uuid, $locale) {
                $initializer = null;
                $wrappedObject = $this->documentManager->find($uuid, $locale);

                return true;
            }
        );
    }
}
