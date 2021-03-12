<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CacheClearEvent extends Event
{
    /**
     * @var string[]|null
     */
    private $tags;

    /**
     * @param string[]|null $tags
     */
    public function __construct(?array $tags = [])
    {
        $this->tags = $tags;
    }

    /**
     * @return string[]|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }
}
