<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag\Exception;

use Exception;

/**
 * This Exception is thrown when a Tag already exists
 * @package Sulu\Bundle\TagBundle\Tag\Exception
 */
class TagAlreadyExistsException extends Exception
{
    /**
     * The id of the tag, which was not found
     * @var int
     */
    protected $name;

    /**
     * @param int $id The id of the entity, which was not found
     */
    public function __construct($name)
    {
        $this->name = $name;
        $message = 'The tag with the id "' . $this->name . '" was not found.';
        parent::__construct($message, 0);
    }

    /**
     * Returns the name of the tag, which already exists
     * @return int
     */
    public function getName()
    {
        return $this->name;
    }
} 
