<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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

    public function enableTags($enable = true)
    {
        $this->configuration->setTags($enable);

        return $this;
    }

    public function enableCategories($enable = true)
    {
        $this->configuration->setCategories($enable);

        return $this;
    }

    public function enableLimit($enable = true)
    {
        $this->configuration->setLimit($enable);

        return $this;
    }

    public function enablePagination($enable = true)
    {
        $this->configuration->setPaginated($enable);

        return $this;
    }

    public function enablePresentAs($enable = true)
    {
        $this->configuration->setPresentAs($enable);

        return $this;
    }

    public function enableDatasource($component, array $options = [])
    {
        $this->configuration->setDatasource(new ComponentConfiguration($component, $options));

        return $this;
    }

    public function enableAudienceTargeting($enable = true)
    {
        $this->configuration->setAudienceTargeting($enable);

        return $this;
    }

    public function enableSorting(array $sorting)
    {
        $this->configuration->setSorting(
            \array_map(
                function ($item) {
                    return new PropertyParameter($item['column'], $item['title'] ?: \ucfirst($item['column']));
                },
                $sorting
            )
        );

        return $this;
    }

    public function setDeepLink($deepLink)
    {
        $this->configuration->setDeepLink($deepLink);

        return $this;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }
}
