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
 * This exception is thrown if a the given condition group is not
 * related with the given conditions.
 */
class ConditionGroupMismatchException extends FilterException
{
    /**
     * The id of the condition group which is not related to the condition(s).
     *
     * @var string
     */
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
        parent::__construct(
            'The condition group with the id "' . $this->id . '" is not related to the given conditions.',
            0
        );
    }

    /**
     * Returns the id of the condition group.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
