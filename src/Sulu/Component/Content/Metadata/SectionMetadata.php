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
    public $colSpan = null;

    /**
     * The number of grid columns the property should use in the admin interface.
     *
     * @var int
     */
    protected $size = null;

    public function getTitle($locale)
    {
        if (!array_key_exists($locale, $this->titles)) {
            return;
        }

        return $this->titles[$locale];
    }

    public function getColSpan()
    {
        @trigger_error(
            sprintf('Do not use getter "%s" from "%s"', 'getColSpan', __CLASS__),
            E_USER_DEPRECATED
        );

        return $this->colSpan;
    }

    public function getSize(): ?int
    {
        if ($this->size) {
            return $this->size;
        }

        if ($this->colSpan) {
            return $this->getColSpan();
        }

        return null;
    }

    public function setSize(int $size = null): self
    {
        $this->size = $size;

        return $this;
    }
}
