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

use Sulu\Component\Rest\ListBuilder\Metadata\PropertyMetadata;

/**
 * Interface for all field-descriptors.
 */
interface FieldDescriptorInterface
{
    /**
     * Returns the name of the field.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns whether the field is disabled or not.
     *
     * @return bool
     */
    public function getDisabled();

    /**
     * Returns the translation code of the field.
     *
     * @return string
     */
    public function getTranslation();

    /**
     * Returns the type of the field.
     *
     * @return string
     */
    public function getType();

    /**
     * Returns the width of the field.
     *
     * @return string
     */
    public function getWidth();

    /**
     * @return bool
     */
    public function getDefault();

    /**
     * @return bool
     */
    public function getSortable();

    /**
     * @return bool
     */
    public function getEditable();

    /**
     * @return string
     */
    public function getMinWidth();

    /**
     * @return string
     */
    public function getClass();

    /**
     * @return PropertyMetadata
     */
    public function getMetadata();

    /**
     * Compares current instance of FieldDescriptor with another instance.
     *
     * @param FieldDescriptorInterface $other
     *
     * @return bool
     */
    public function compare(FieldDescriptorInterface $other);
}
