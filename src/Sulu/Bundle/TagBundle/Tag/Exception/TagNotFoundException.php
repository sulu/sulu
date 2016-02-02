<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag\Exception;

use Exception;

/**
 * This Exception is thrown when a Tag is not found.
 */
class TagNotFoundException extends Exception
{
    /**
     * The id of the tag, which was not found.
     *
     * @var int
     */
    protected $id;

    /**
     * @param int $id The id of the entity, which was not found
     */
    public function __construct($id)
    {
        $this->id = $id;
        $message = 'The tag with the id "' . $id . '" was not found.';
        parent::__construct($message, 0);
    }

    /**
     * Returns the id of the tag, which was not found.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
