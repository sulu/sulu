<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

/**
 * Indicates that the data provider can be identified by an alias.
 */
interface DataProviderAliasInterface
{
    /**
     * Returns alias of data-provider.
     * Will be used for example as reference-store alias.
     *
     * @return string
     */
    public function getAlias();
}
