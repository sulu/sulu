<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Exception;

/**
 * Basic exception of SmartContent-DataProvider.
 */
abstract class DataProviderException extends SmartContentException
{
    /**
     * @param string $alias
     */
    public function __construct(private $alias, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns alias of errored data provider.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}
