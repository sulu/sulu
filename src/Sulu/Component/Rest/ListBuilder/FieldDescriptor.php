<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Expose;
use Sulu\Component\Rest\ListBuilder\Metadata\AbstractPropertyMetadata;

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
     * Defines the visibility of the field.
     *
     * @var string
     * @Expose
     */
    private $visibility;

    /**
     * Defines the searchability of the field.
     *
     * @var string
     * @Expose
     */
    private $searchability;

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
     * @var AbstractPropertyMetadata
     * @Exclude
     */
    private $metadata;

    public function __construct(
        string $name,
        string $translation = null,
        string $visibility = FieldDescriptorInterface::VISIBILITY_YES,
        string $searchability = FieldDescriptorInterface::SEARCHABILITY_NEVER,
        string $type = '',
        bool $sortable = true
    ) {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->searchability = $searchability;
        $this->sortable = $sortable;
        $this->type = $type;
        $this->translation = null == $translation ? $name : $translation;
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
     *
     * @Serializer\VirtualProperty()
     */
    public function getDisabled()
    {
        return in_array(
            $this->visibility,
            [FieldDescriptorInterface::VISIBILITY_NO, FieldDescriptorInterface::VISIBILITY_NEVER]
        );
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
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchability()
    {
        return $this->searchability;
    }

    /**
     * {@inheritdoc}
     *
     * @Serializer\VirtualProperty()
     */
    public function getDefault()
    {
        return in_array(
            $this->visibility,
            [FieldDescriptorInterface::VISIBILITY_ALWAYS, FieldDescriptorInterface::VISIBILITY_NEVER]
        );
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
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Sets metadata for property.
     *
     * @param AbstractPropertyMetadata $metadata
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
