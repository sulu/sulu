<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

/**
 * Raised when no snippet was found.
 */
class SnippetNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $uuid;

    public function __construct($uuid)
    {
        parent::__construct(sprintf('Snippet with uuid "%s" not found.', $uuid));

        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }
}
