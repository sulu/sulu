<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Orm;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;

/**
 * Provides basic functionality for contact and account providers.
 */
abstract class BaseDataProvider implements DataProviderInterface
{
    /**
     * @var DataProviderRepositoryInterface
     */
    private $repository;

    /**
     * @var ProviderConfigurationInterface
     */
    protected $configuration;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(DataProviderRepositoryInterface $repository, SerializerInterface $serializer)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;
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
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        return;
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
        list($result, $hasNextPage) = $this->resolveFilters($filters, $options['locale'], $limit, $page, $pageSize);

        return new DataProviderResult($this->decorateDataItems($result), $hasNextPage);
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
        list($result, $hasNextPage) = $this->resolveFilters($filters, $options['locale'], $limit, $page, $pageSize);

        return new DataProviderResult($this->decorateResourceItems($result, $options['locale']), $hasNextPage);
    }

    /**
     * Resolves filters.
     */
    private function resolveFilters(
        array $filters,
        $locale,
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        $result = $this->repository->findByFilters($filters, $page, $pageSize, $limit, $locale);

        $hasNextPage = false;
        if ($pageSize !== null && count($result) > $pageSize) {
            $hasNextPage = true;
            $result = array_splice($result, 0, $pageSize);
        }

        return [$result, $hasNextPage];
    }

    /**
     * Initiate configuration.
     *
     * @return ProviderConfigurationInterface
     */
    protected function initConfiguration($tags, $categories, $limit, $presentAs, $paginated, $sorting)
    {
        $configuration = new ProviderConfiguration();
        $configuration->setTags($tags);
        $configuration->setCategories($categories);
        $configuration->setLimit($limit);
        $configuration->setPresentAs($presentAs);
        $configuration->setPaginated($paginated);
        $configuration->setSorting($sorting);

        return $configuration;
    }

    /**
     * Decorates result as resource item.
     *
     * @param array $data
     * @param string $locale
     *
     * @return ArrayAccessItem[]
     */
    protected function decorateResourceItems(array $data, $locale)
    {
        return array_map(
            function ($item) use ($locale) {
                $itemData = $this->serializer->serialize($item, 'array', $this->getSerializationContext());

                return new ArrayAccessItem($item->getId(), $itemData, $item);
            },
            $data
        );
    }

    /**
     * Creates serialization context. Can be used to add own groups.
     *
     * @return SerializationContext
     */
    protected function getSerializationContext()
    {
        return SerializationContext::create();
    }

    /**
     * Decorates result as data item.
     *
     * @param array $data
     *
     * @return array
     */
    abstract protected function decorateDataItems(array $data);
}
