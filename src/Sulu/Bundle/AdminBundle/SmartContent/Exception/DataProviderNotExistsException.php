<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\SmartContent\Exception;

/**
 * Indicates a not existing DataProvider.
 */
class DataProviderNotExistsException extends \Exception
{
    public function __construct(string $alias)
    {
        parent::__construct(\sprintf('DataProvider with alias "%s" not exists.', $alias));
    }
}
