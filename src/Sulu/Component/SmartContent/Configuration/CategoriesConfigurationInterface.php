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
interface CategoriesConfigurationInterface
{
    /**
     * Returns root node (id or key) of category tree.
     *
     * @return int|string|null
     */
    public function getRoot();
}
