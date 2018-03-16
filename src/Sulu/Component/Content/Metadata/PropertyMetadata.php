<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

/**
 * Metadata for a property. Contains both UI and model metadata.
 */
class PropertyMetadata extends ItemMetadata
{
    /**
     * Type of this property (e.g. "text_line", "smart_content").
     *
     * @var string
     */
    protected $type;

    /**
     * Placeholders for property.
     *
     * @var array
     */
    protected $placeholders;

    /**
     * If the property should be available in different localizations.
     *
     * @var bool
     */
    protected $localized = false;

    /**
     * If the property is required.
     *
     * @var bool
     */
    protected $required = false;

    /**
     * The number of grid columns the property should use in the admin interface.
     *
     * @var int
     */
    protected $colSpan = null;

    /**
     * The number of grid columns the property should use in the admin interface.
     *
     * @var int
     */
    protected $size = null;

    /**
     * The number of grid columns the property should have space after.
     *
     * @var int
     */
    protected $spaceAfter = null;

    /**
     * The CSS class the property should use in the admin interface.
     *
     * @var string
     */
    protected $cssClass = null;

    /**
     * @var int
     */
    protected $minOccurs = null;

    /**
     * @var mixed
     */
    protected $maxOccurs = null;

    /**
     * @var StructureMetadata
     */
    protected $structure;

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass = null): self
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function getStructure(): StructureMetadata
    {
        return $this->structure;
    }

    public function setStructure(StructureMetadata $structure): self
    {
        $this->structure = $structure;

        return $this;
    }

    public function getMinOccurs(): ?int
    {
        return $this->minOccurs;
    }

    public function setMinOccurs(int $minOccurs = null): self
    {
        if ($minOccurs) {
            $this->minOccurs = $minOccurs;
        }

        return $this;
    }

    public function getMaxOccurs(): ?int
    {
        return $this->maxOccurs;
    }

    public function setMaxOccurs(int $maxOccurs = null): self
    {
        if ($maxOccurs) {
            $this->maxOccurs = $maxOccurs;
        }

        return $this;
    }

    /**
     * @deprecated - use getType
     */
    public function getContentTypeName()
    {
        return $this->type;
    }

    /**
     * @deprecated
     */
    public function getIsBlock()
    {
        return false;
    }

    public function getColSpan(): ?int
    {
        @trigger_error(
            sprintf('Do not use getter "%s" from "%s"', 'getColSpan', __CLASS__),
            E_USER_DEPRECATED
        );

        return $this->colSpan;
    }

    public function setColSpan(int $colSpan = null): self
    {
        @trigger_error(
            sprintf('Do not use setter "%s" from "%s"', 'getColSpan', __CLASS__),
            E_USER_DEPRECATED
        );

        $this->colSpan = $colSpan;

        return $this;
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

    public function getSpaceAfter(): ?int
    {
        return $this->spaceAfter;
    }

    public function setSpaceAfter(int $spaceAfter = null): self
    {
        $this->spaceAfter = $spaceAfter;

        return $this;
    }

    public function getPlaceholder($locale): string
    {
        return $this->placeholders[$locale];
    }

    public function setPlaceholders(array $placeholders): self
    {
        $this->placeholders = $placeholders;

        return $this;
    }

    public function getPlaceholders(): ?array
    {
        return $this->placeholders;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->minOccurs !== $this->maxOccurs;
    }

    public function isLocalized(): bool
    {
        return $this->localized;
    }

    public function setLocalized(bool $localized): self
    {
        $this->localized = $localized;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
