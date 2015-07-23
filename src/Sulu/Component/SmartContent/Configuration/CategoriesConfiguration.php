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
 * Provides configuration for categories.
 */
class CategoriesConfiguration implements CategoriesConfigurationInterface
{
    /**
     * @var integer|string|null
     */
    private $root;

    public function __construct($root = null)
    {
        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param int|string|null $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }
}
