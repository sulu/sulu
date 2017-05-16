<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Content\SmartContent\ContentDataItem;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\Builder;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\Util\SuluNodeHelper;

/**
 * DataProvider for snippets.
 */
class SnippetDataProvider implements DataProviderInterface
{
    /**
     * @var ProviderConfigurationInterface
     */
    private $configuration;

    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQueryExecutor;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $snippetQueryBuilder;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function __construct(
        ContentQueryExecutorInterface $contentQueryExecutor,
        ContentQueryBuilderInterface $snippetQueryBuilder,
        SuluNodeHelper $nodeHelper,
        LazyLoadingValueHolderFactory $proxyFactory,
        DocumentManagerInterface $documentManager
    ) {
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->snippetQueryBuilder = $snippetQueryBuilder;
        $this->nodeHelper = $nodeHelper;
        $this->proxyFactory = $proxyFactory;
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if (!$this->configuration) {
            $this->configuration = Builder::create()
                ->enableTags()
                ->enableCategories()
                ->enablePresentAs()
                ->enablePagination()
                ->enableLimit()
                ->enableAudienceTargeting()
                ->enableSorting(
                    [
                        ['column' => 'title', 'title' => 'smart-content.title'],
                        ['column' => 'created', 'title' => 'smart-content.created'],
                        ['column' => 'changed', 'title' => 'smart-content.changed'],
                    ]
                )
                ->setDeepLink('snippet/snippets/{locale}/edit:{id}/details')
                ->getConfiguration();
        }

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return [];
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
            array_map(function(ContentDataItem $item) {
                return $item->getId();
            }, $items)
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
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        return null;
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
            SnippetDocument::class,
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

    /**
     * @param array $filters
     * @param array $propertyParameter
     * @param array $options
     * @param int $limit
     * @param int $page
     * @param int $pageSize
     *
     * @return array
     */
    private function resolveFilters(array $filters, array $propertyParameter, array $options, $limit, $page, $pageSize)
    {
        $filters['dataSource'] = $this->nodeHelper->getBaseSnippetUuid();
        $filters['includeSubFolders'] = true;
        $filters['types'] = array_key_exists('types', $propertyParameter)
            ? explode(',', $propertyParameter['types']->getValue()) : [];

        $properties = array_key_exists('properties', $propertyParameter)
            ? $propertyParameter['properties']->getValue() : [];

        $this->snippetQueryBuilder->init(
            [
                'config' => $filters,
                'properties' => $properties,
                'excluded' => $filters['excluded'],
            ]
        );

        $loadLimit = $limit;
        $offset = null;

        if ($pageSize !== null) {
            $offset = ($page - 1) * $pageSize;

            $position = $pageSize * $page;
            if ($limit !== null && $position >= $limit) {
                $pageSize = $limit - $offset;
                $loadLimit = $pageSize;
            } else {
                $loadLimit = $pageSize + 1;
            }
        }

        $items = $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->snippetQueryBuilder,
            true,
            -1,
            $loadLimit,
            $offset
        );

        $hasNextPage = false;
        if ($pageSize !== null) {
            $hasNextPage = (count($items) > $pageSize);
            $items = array_splice($items, 0, $pageSize);
        }

        return [$items, $hasNextPage];
    }
}
