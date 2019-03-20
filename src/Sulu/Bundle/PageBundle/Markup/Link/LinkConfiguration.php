<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Markup\Link;

use JMS\Serializer\Annotation\Groups;

class LinkConfiguration
{
    /**
     * @var string
     * @Groups({"frontend"})
     */
    private $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * Returns title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
