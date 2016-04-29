<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Generator;

/**
 * This exception indicates that a token cannot be evaluated.
 */
class CannotEvaluateTokenException extends \Exception
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var mixed
     */
    private $entity;

    /**
     * @param string $token
     * @param mixed $entity
     * @param \Exception $previous
     */
    public function __construct($token, $entity, \Exception $previous)
    {
        parent::__construct(
            sprintf('Cannot evaluate toeken "%s" for entity with type "%s"', $token, get_class($entity)),
            0,
            $previous
        );

        $this->token = $token;
        $this->entity = $entity;
    }

    /**
     * Get token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get entity.
     *
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
