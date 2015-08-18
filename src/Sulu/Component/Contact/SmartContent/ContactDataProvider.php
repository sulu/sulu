<?php
/*
 * This file is part Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\SmartContent;

use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;

/**
 * Contact DataProvider for SmartContent.
 */
class ContactDataProvider implements DataProviderInterface
{
    /**
     * @var ProviderConfigurationInterface
     */
    private $configuration;

    /**
     * @var ContactRepository
     */
    private $repository;

    public function __construct(ContactRepository $repository)
    {
        $this->repository = $repository;
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
        $this->configuration->setCategories(false);
        $this->configuration->setLimit(true);
        $this->configuration->setPresentAs(true);
        $this->configuration->setPaginated(true);

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
        $result = $this->repository->findByFilters($filters, $page, $pageSize, $limit);

        $hasNextPage = false;
        if ($pageSize !== null && count($result) > $pageSize) {
            $hasNextPage = true;
            $result = array_splice($result, 0, $pageSize);
        }

        return new DataProviderResult($this->decorate($result), $hasNextPage);
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
        // TODO: Implement resolveResourceItems() method.
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        return;
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
                return new ContactDataItem($item);
            },
            $data
        );
    }
}
