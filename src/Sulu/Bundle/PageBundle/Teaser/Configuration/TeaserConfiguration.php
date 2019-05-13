<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Teaser\Configuration;

use JMS\Serializer\Annotation\Groups;

class TeaserConfiguration
{
    /**
     * @var string
     * @Groups({"frontend"})
     */
    private $title;

    /**
     * @var string
     * @Groups({"frontend"})
     */
    private $resourceKey;

    /**
     * @var string
     * @Groups({"frontend"})
     */
    private $listAdapter;

    /**
     * @var string[]
     * @Groups({"frontend"})
     */
    private $displayProperties;

    /**
     * @var string
     * @Groups({"frontend"})
     */
    private $overlayTitle;

    public function __construct(
        string $title,
        string $resourceKey,
        string $listAdapter,
        array $displayProperties,
        string $overlayTitle
    ) {
        $this->title = $title;
        $this->resourceKey = $resourceKey;
        $this->listAdapter = $listAdapter;
        $this->displayProperties = $displayProperties;
        $this->overlayTitle = $overlayTitle;
    }
}
