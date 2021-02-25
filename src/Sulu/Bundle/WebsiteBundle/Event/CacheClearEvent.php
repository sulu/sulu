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
     * @var string|null
     */
    private $webspaceKey;

    public function __construct(?string $webspaceKey = null)
    {
        $this->webspaceKey = $webspaceKey;
    }

    public function getWebspaceKey(): ?string
    {
        return $this->webspaceKey;
    }
}
