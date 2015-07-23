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

/**
 * Provides configuration for smart-content.
 */
class ProviderConfiguration implements ProviderConfigurationInterface
{
    /**
     * @var ComponentConfigurationInterface
     */
    private $datasource;

    /**
     * @var boolean
     */
    private $tags;

    /**
     * @var CategoriesConfigurationInterface
     */
    private $categories;

    /**
     * @var KeyTitlePairInterface[]
     */
    private $sorting;

    /**
     * @var boolean
     */
    private $limit;

    /**
     * @var KeyTitlePairInterface
     */
    private $presentAs;

    /**
     * @var boolean
     */
    private $paginated;

    /**
     * {@inheritdoc}
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * @param ComponentConfigurationInterface $datasource
     */
    public function setDatasource($datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param boolean $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param CategoriesConfigurationInterface $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * @param KeyTitlePairInterface[] $sorting
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param boolean $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function getPresentAs()
    {
        return $this->presentAs;
    }

    /**
     * @param KeyTitlePairInterface $presentAs
     */
    public function setPresentAs($presentAs)
    {
        $this->presentAs = $presentAs;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginated()
    {
        return $this->paginated;
    }

    /**
     * @param boolean $paginated
     */
    public function setPaginated($paginated)
    {
        $this->paginated = $paginated;
    }
}
