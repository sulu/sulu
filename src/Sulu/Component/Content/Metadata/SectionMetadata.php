<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

/**
 * Metadata for a section. A section is a UI component which
 * groups a bunch of properties.
 */
class SectionMetadata extends ItemMetadata
{
    /**
     * The number of grid columns the property should use in the admin interface.
     *
     * @var int
     */
    protected $colspan = 12;

    public function getTitle($locale)
    {
        if (!array_key_exists($locale, $this->titles)) {
            return;
        }

        return $this->titles[$locale];
    }

    public function getColspan(): int
    {
        return $this->colspan;
    }

    public function setColspan(int $colspan): self
    {
        $this->colspan = $colspan;

        return $this;
    }
}
