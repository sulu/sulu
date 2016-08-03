<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Strategy;

/**
 * Interface for resource-locator strategy-manager.
 */
interface StrategyManagerInterface
{
    /**
     * Returns strategy by given name.
     *
     * @param string $name
     *
     * @return RlpStrategyInterface
     */
    public function getStrategy($name);

    /**
     * Returns strategy by given webspaceKey.
     *
     * @param string $webspaceKey
     *
     * @return RlpStrategyInterface
     */
    public function getStrategyByWebspaceKey($webspaceKey);
}
