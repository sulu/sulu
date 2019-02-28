<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
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

    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    public function __construct(
        ContentQueryExecutorInterface $contentQueryExecutor,
        ContentQueryBuilderInterface $snippetQueryBuilder,
        SuluNodeHelper $nodeHelper,
        LazyLoadingValueHolderFactory $proxyFactory,
        DocumentManagerInterface $documentManager,
        ReferenceStoreInterface $referenceStore
    ) {
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->snippetQueryBuilder = $snippetQueryBuilder;
        $this->nodeHelper = $nodeHelper;
        $this->proxyFactory = $proxyFactory;
        $this->documentManager = $documentManager;
        $this->referenceStore = $referenceStore;
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

        return new DataProviderResult($this->decorateDataItems($items, $options['locale']), $hasNextPage);
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

        return new DataProviderResult($this->decorateResourceItems($items, $options['locale']), $hasNextPage);
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
            function($item) use ($locale) {
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
            function($item) use ($locale) {
                $this->referenceStore->add($item['uuid']);

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
            function(
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
        $filters['dataSource'] = $this->nodeHelper->getBaseSnippetUuid(
            array_key_exists('type', $propertyParameter) ? $propertyParameter['type'] : null
        );
        $filters['includeSubFolders'] = true;

        $properties = array_key_exists('properties', $propertyParameter)
            ? $propertyParameter['properties']->getValue() : [];

        $excluded = [];

        if (array_key_exists('excluded', $filters) && is_array($filters['excluded'])) {
            $excluded = $filters['excluded'];
        }

        if (array_key_exists('exclude_duplicates', $propertyParameter)
            && $propertyParameter['exclude_duplicates']->getValue()
        ) {
            $excluded = array_merge($excluded, $this->referenceStore->getAll());
        }

        $this->snippetQueryBuilder->init(
            [
                'config' => $filters,
                'properties' => $properties,
                'excluded' => $excluded,
            ]
        );

        $loadLimit = $limit;
        $offset = null;

        if (null !== $pageSize) {
            $offset = ($page - 1) * $pageSize;

            $position = $pageSize * $page;
            if (null !== $limit && $position >= $limit) {
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
        if (null !== $pageSize) {
            $hasNextPage = (count($items) > $pageSize);
            $items = array_splice($items, 0, $pageSize);
        }

        return [$items, $hasNextPage];
    }
}
