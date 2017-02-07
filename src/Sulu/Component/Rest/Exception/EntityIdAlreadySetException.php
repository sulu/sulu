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
class EntityIdAlreadySetException extends RestException
{
    /**
     * The type of the entity, which was not found.
     *
     * @var string
     */
    protected $entity;

    /**
     * The id of the entity, which was not found.
     *
     * @var int
     */
    protected $id;

    /**
     * @param string $entity The type of the entity, which was not found
     * @param int    $id     The id of the entity, which was not found
     */
    public function __construct($entity, $id)
    {
        $this->entity = $entity;
        $this->id = $id;
        $message = 'The id-field of the  "' . $entity . '"-Entity already has an id with the value "' . $id . '" .';
        parent::__construct($message, 0);
    }

    /**
     * Returns the type of the entity, for which the id was already set.
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Returns the id of the entity, for which the id was already set.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
