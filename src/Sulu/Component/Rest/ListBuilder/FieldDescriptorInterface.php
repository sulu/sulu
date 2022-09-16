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

use Sulu\Component\Rest\ListBuilder\Metadata\AbstractPropertyMetadata;

/**
 * Interface for all field-descriptors.
 */
interface FieldDescriptorInterface
{
    public const VISIBILITY_ALWAYS = 'always';

    public const VISIBILITY_NEVER = 'never';

    public const VISIBILITY_YES = 'yes';

    public const VISIBILITY_NO = 'no';

    public const SEARCHABILITY_NEVER = 'never';

    public const SEARCHABILITY_YES = 'yes';

    public const SEARCHABILITY_NO = 'no';

    public const WIDTH_AUTO = 'auto';

    public const WIDTH_SHRINK = 'shrink';

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
     * @return bool
     */
    public function getDefault();

    /**
     * @return bool
     */
    public function getSortable();

    /**
     * @return string
     */
    public function getVisibility();

    /**
     * @return string
     */
    public function getSearchability();

    public function getWidth(): string;

    /**
     * @return AbstractPropertyMetadata
     */
    public function getMetadata();

    /**
     * Compares current instance of FieldDescriptor with another instance.
     *
     * @return bool
     */
    public function compare(self $other);
}
