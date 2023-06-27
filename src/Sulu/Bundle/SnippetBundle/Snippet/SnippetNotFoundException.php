<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $uuid
     */
    public function __construct(private $uuid)
    {
        parent::__construct(\sprintf('Snippet with uuid "%s" not found.', $this->uuid));
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }
}
