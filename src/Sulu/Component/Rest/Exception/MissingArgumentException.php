<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

/**
 * This exception should be thrown when an Entity id already has been set.
 */
class MissingArgumentException extends RestException
{
    /**
     * The type of the entity, which was concerned.
     *
     * @var string
     */
    protected $entity;

    /**
     * The argument of the entity, which was not passed.
     *
     * @var string
     */
    protected $argument;

    /**
     * @param string $entity   The type of the entity, which was not found
     * @param string $argument The argument of the entity, which was not passed
     */
    public function __construct($entity, $argument)
    {
        $this->entity = $entity;
        $this->argument = $argument;
        $message = 'The "' . $entity . '"-entity requires a "' . $argument . '"-argument';
        parent::__construct($message, 0);
    }

    /**
     * Returns the argument of the entity, which was not passed.
     *
     * @return string
     */
    public function getArgument()
    {
        return $this->argument;
    }

    /**
     * Returns the type of the entity, which was concerned.
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
