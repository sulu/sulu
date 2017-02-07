<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Expose;
use Sulu\Component\Rest\ListBuilder\Metadata\PropertyMetadata;

/**
 * Base class for all field-descriptor.
 */
class FieldDescriptor implements FieldDescriptorInterface
{
    /**
     * The name of the field in the database.
     *
     * @var string
     * @Expose
     */
    private $name;

    /**
     * The translation name.
     *
     * @var string
     * @Expose
     */
    private $translation;

    /**
     * Defines whether the field is disabled or not.
     *
     * @var bool
     * @Expose
     */
    private $disabled;

    /**
     * Defines whether the field is hideable or not.
     *
     * @var bool
     * @Expose
     */
    private $default;

    /**
     * Defines if this field is sortable.
     *
     * @var bool
     * @Expose
     */
    private $sortable;

    /**
     * The type of the field (only used for special fields like dates).
     *
     * @var string
     * @Expose
     */
    private $type;

    /**
     * The width of the field in a table.
     *
     * @var string
     * @Expose
     */
    private $width;

    /**
     * The minimal with of the field in the table.
     *
     * @var string
     * @Expose
     */
    private $minWidth;

    /**
     * Defines whether the field is editable in the table or not.
     *
     * @var bool
     * @Expose
     */
    private $editable;

    /**
     * The css class of the column.
     *
     * @var string
     * @Expose
     */
    private $class;

    /**
     * @var PropertyMetadata
     * @Exclude
     */
    private $metadata;

    public function __construct(
        $name,
        $translation = null,
        $disabled = false,
        $default = false,
        $type = '',
        $width = '',
        $minWidth = '',
        $sortable = true,
        $editable = false,
        $cssClass = ''
    ) {
        $this->name = $name;
        $this->disabled = $disabled;
        $this->default = $default;
        $this->sortable = $sortable;
        $this->type = $type;
        $this->width = $width;
        $this->minWidth = $minWidth;
        $this->editable = $editable;
        $this->translation = $translation == null ? $name : $translation;
        $this->class = $cssClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortable()
    {
        return $this->sortable;
    }

    /**
     * {@inheritdoc}
     */
    public function getEditable()
    {
        return $this->editable;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinWidth()
    {
        return $this->minWidth;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Sets metadata for property.
     *
     * @param PropertyMetadata $metadata
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function compare(FieldDescriptorInterface $other)
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->getName() === $other->getName()
            && $this->getType() === $other->getType();
    }
}
