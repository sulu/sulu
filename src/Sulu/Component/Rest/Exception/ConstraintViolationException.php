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
 * This exception should be thrown when a constraint violation for a enitity occures.
 */
class ConstraintViolationException extends RestException
{
    const UNIQUE = 'unique';

    /**
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param string $field
     */
    public function __construct($field, $entity, $type, $code)
    {
        $this->field = $field;
        $this->entity = $entity;
        $this->type = $type;

        parent::__construct(
            sprintf('%s constraint for field "%s" of entity "%s" violated', ucfirst($type), $field, $entity),
            $code
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'type' => $this->type,
            'field' => $this->field,
            'entity' => $this->entity,
        ];
    }
}
