<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

/**
 * This exception should be thrown when an argument is invalid!
 */
class InvalidArgumentException extends RestException
{
    /**
     * @param string $entity The type of the entity
     * @param string $argument The argument of the entity, which was invalid
     * @param null|string $customMessage
     */
    public function __construct(
        protected $entity,
        protected $argument,
        $customMessage = null
    ) {
        $message = 'The "' . $entity . '"-entity requires a valid "' . $argument . '"-argument. ';
        if (null != $customMessage) {
            $message .= $customMessage;
        }
        parent::__construct($message, 0);
    }

    /**
     * Returns the type of the entity, which was concerned.
     *
     * @return string
     */
    public function getArgument()
    {
        return $this->argument;
    }

    /**
     * Returns the argument of the entity, which was not passed.
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
