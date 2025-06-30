<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\SmartContent\Configuration;

use Sulu\Component\Content\Compat\PropertyParameter;

/**
 * Builder with fluent interface for smart content configuration.
 */
class Builder implements BuilderInterface
{
    public static function create(): BuilderInterface
    {
        return new self();
    }

    private ProviderConfiguration $configuration;

    public function __construct()
    {
        $this->configuration = new ProviderConfiguration();
    }

    public function enableTags(bool $enable = true): self
    {
        $this->configuration->setTags($enable);

        return $this;
    }

    public function enableTypes(array $types = []): self
    {
        $this->configuration->setTypes(
            \array_map(
                function($type) {
                    return new PropertyParameter($type['title'], $type['type']);
                },
                $types
            )
        );

        return $this;
    }

    public function enableCategories(bool $enable = true): self
    {
        $this->configuration->setCategories($enable);

        return $this;
    }

    public function enableLimit(bool $enable = true): self
    {
        $this->configuration->setLimit($enable);

        return $this;
    }

    public function enablePagination(bool $enable = true): self
    {
        $this->configuration->setPaginated($enable);

        return $this;
    }

    public function enablePresentAs(bool $enable = true): self
    {
        $this->configuration->setPresentAs($enable);

        return $this;
    }

    public function enableDatasource(string $resourceKey, string $listKey, string $listAdapter): self
    {
        $this->configuration->setDatasourceResourceKey($resourceKey);
        $this->configuration->setDatasourceListKey($listKey);
        $this->configuration->setDatasourceAdapter($listAdapter);

        return $this;
    }

    public function enableAudienceTargeting(bool $enable = true): self
    {
        $this->configuration->setAudienceTargeting($enable);

        return $this;
    }

    public function enableSorting(array $sorting): self
    {
        $this->configuration->setSorting(
            \array_map(
                function($item) {
                    return new PropertyParameter($item['column'], $item['title'] ?: \ucfirst($item['column']));
                },
                $sorting
            )
        );

        return $this;
    }

    public function enableView(string $view, array $resultToView): self
    {
        $this->configuration->setView($view);
        $this->configuration->setResultToView($resultToView);

        return $this;
    }

    public function getConfiguration(): ProviderConfigurationInterface
    {
        return $this->configuration;
    }
}
