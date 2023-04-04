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
 * This exception should be thrown when a constraint violation for a enitity occures.
 */
class ConstraintViolationException extends RestException
{
    public const UNIQUE = 'unique';

    /**
     * @param string $field
     * @param string $entity
     * @param string $type
     * @param int $code
     */
    public function __construct(
        protected $field,
        protected $entity,
        protected $type,
        $code
    ) {
        parent::__construct(
            \sprintf('%s constraint for field "%s" of entity "%s" violated', \ucfirst($this->type), $this->field, $this->entity),
            $code
        );
    }

    /**
     * @return array<string, string|int>
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
