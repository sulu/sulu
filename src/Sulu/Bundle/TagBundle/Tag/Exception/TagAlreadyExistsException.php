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
 * This Exception is thrown when a Tag already exists.
 */
class TagAlreadyExistsException extends Exception
{
    /**
     * The id of the tag, which was not found.
     *
     * @var int
     */
    protected $name;

    /**
     * @param string $name The name of the tag which already exists
     */
    public function __construct($name)
    {
        $this->name = $name;
        $message = 'The tag with the name "' . $this->name . '" already exists.';
        parent::__construct($message, 0);
    }

    /**
     * Returns the name of the tag, which already exists.
     *
     * @return int
     */
    public function getName()
    {
        return $this->name;
    }
}
