<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Exception;

/**
 * Indicates a not existing DataProvider.
 */
class DataProviderNotExistsException extends DataProviderException
{
    /**
     * @param string $alias
     */
    public function __construct($alias)
    {
        parent::__construct($alias, sprintf('DataProvider with alias "%s" not exists.', $alias));
    }
}
