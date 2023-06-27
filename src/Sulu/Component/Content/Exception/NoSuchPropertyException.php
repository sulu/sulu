<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

class NoSuchPropertyException extends \Exception
{
    /**
     * @param string $propertyName
     * @param string $message
     */
    public function __construct(
        private $propertyName,
        $message = ''
    ) {
        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }
}
