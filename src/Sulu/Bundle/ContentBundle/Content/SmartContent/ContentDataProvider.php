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
     * @var ProviderConfigurationInterface
     */
    private $configuration;

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
    public function resolveFilters(array $filters, array $propertyParameter, $limit = null, $page = 1, $pageSize = null)
    {
        // TODO: Implement resolveFilters() method.
    }
}
