<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup\Link;

use JMS\Serializer\Annotation\Groups;

class LinkConfiguration
{
    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $title;

    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $resourceKey;

    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $listAdapter;

    /**
     * @var string[]
     */
    #[Groups(['frontend'])]
    private $displayProperties;

    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $overlayTitle;

    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $emptyText;

    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $icon;

    /**
     * @var string[]
     */
    #[Groups(['frontend'])]
    private $targets = [
        '_blank',
        '_self',
        '_parent',
        '_top',
    ];

    /**
     * @param array<string>|null $targets
     */
    public function __construct(
        string $title,
        string $resourceKey,
        string $listAdapter,
        array $displayProperties,
        string $overlayTitle,
        string $emptyText,
        string $icon,
        ?array $targets = null
    ) {
        $this->title = $title;
        $this->resourceKey = $resourceKey;
        $this->listAdapter = $listAdapter;
        $this->displayProperties = $displayProperties;
        $this->overlayTitle = $overlayTitle;
        $this->emptyText = $emptyText;
        $this->icon = $icon;
        $this->targets = $targets ?? $this->targets;
    }
}
