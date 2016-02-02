<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource\Exception;

/**
 * Exception which is thrown when the value of a condition does not match the type.
  */
class ConditionTypeMismatchException extends FilterException
{
    /**
     * Id of the entity.
     *
     * @var int
     */
    private $id;

    /**
     * The value of the condition.
     *
     * @var string
     */
    private $value;

    /**
     * The type of the condition.
     *
     * @var int
     */
    private $type;

    /**
     * @param string $id
     * @param int $value
     * @param int $type
     */
    public function __construct($id, $value, $type)
    {
        $this->value = $value;
        $this->id = $id;
        $this->type = $type;
        parent::__construct(
            'The condition with id ' . $id . ' the value "' . $value . '" does not match the type ' . $type,
            0
        );
    }

    /**
     * Returns value of the condition.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the of the condition.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the id of the condition.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
