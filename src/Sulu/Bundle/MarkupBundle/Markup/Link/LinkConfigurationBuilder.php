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

class LinkConfigurationBuilder
{
    private ?string $title = null;

    private ?string $resourceKey = null;

    private ?string $listAdapter = null;

    /**
     * @var string[]
     */
    private ?array $displayProperties = null;

    private ?string $overlayTitle = null;

    private ?string $emptyText = null;

    private ?string $icon = null;

    public static function create()
    {
        return new self();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setResourceKey(string $resourceKey): self
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function setListAdapter(string $listAdapter): self
    {
        $this->listAdapter = $listAdapter;

        return $this;
    }

    /**
     * @param string[] $displayProperties
     */
    public function setDisplayProperties(array $displayProperties): self
    {
        $this->displayProperties = $displayProperties;

        return $this;
    }

    public function setOverlayTitle(string $overlayTitle): self
    {
        $this->overlayTitle = $overlayTitle;

        return $this;
    }

    public function setEmptyText(string $emptyText): self
    {
        $this->emptyText = $emptyText;

        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getLinkConfiguration(): LinkConfiguration
    {
        return new LinkConfiguration(
            $this->title,
            $this->resourceKey,
            $this->listAdapter,
            $this->displayProperties,
            $this->overlayTitle,
            $this->emptyText,
            $this->icon
        );
    }
}
