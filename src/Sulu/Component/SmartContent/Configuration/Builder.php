<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Configuration;

use Sulu\Component\Content\Compat\PropertyParameter;

/**
 * Builder with fluent interface for smart content configuration.
 */
class Builder implements BuilderInterface
{
    /**
     * Returns new builder instance.
     *
     * @return BuilderInterface
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @var ProviderConfiguration
     */
    private $configuration;

    public function __construct()
    {
        $this->configuration = new ProviderConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function enableTags($enable = true)
    {
        $this->configuration->setTags($enable);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableCategories($enable = true)
    {
        $this->configuration->setCategories($enable);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableLimit($enable = true)
    {
        $this->configuration->setLimit($enable);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enablePagination($enable = true)
    {
        $this->configuration->setPaginated($enable);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enablePresentAs($enable = true)
    {
        $this->configuration->setPresentAs($enable);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableDatasource($component, array $options = [])
    {
        $this->configuration->setDatasource(new ComponentConfiguration($component, $options));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableSorting(array $sorting)
    {
        $this->configuration->setSorting(
            array_map(
                function ($item) {
                    return new PropertyParameter($item['column'], $item['title'] ?: ucfirst($item['column']));
                },
                $sorting
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDeepLink($deepLink)
    {
        $this->configuration->setDeepLink($deepLink);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
