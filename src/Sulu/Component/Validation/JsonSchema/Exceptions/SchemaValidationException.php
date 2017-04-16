<?php

namespace Sulu\Component\Validation\JsonSchema\Exceptions;

use Exception;

/**
 * @package PoolAlpin\Bundle\BaseBundle\Exceptions
 */
class SchemaValidationException extends Exception
{
    /**
     * @param string $errors
     */
    public function __construct($errors)
    {
        parent::__construct(json_encode($errors));
    }
}
