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
 * Basic exception of SmartContent-DataProvider.
 */
abstract class DataProviderException extends SmartContentException
{
    private $alias;

    /**
     * DataProviderException constructor.
     *
     * @param string $alias
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($alias, $message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->alias = $alias;
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
