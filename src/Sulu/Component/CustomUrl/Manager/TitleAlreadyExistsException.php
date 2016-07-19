<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Component\Rest\Exception\RestException;

/**
 * Thrown when a title already exists.
 */
class TitleAlreadyExistsException extends RestException
{
    /**
     * @var string
     */
    private $title;

    public function __construct($title)
    {
        parent::__construct(sprintf('Title "%s" already in use', $title), 9001);

        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
