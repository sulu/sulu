<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Section;

use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * Defines a section for properties.
 */
interface SectionPropertyInterface extends PropertyInterface
{
    /**
     * returns a list of properties managed by this block.
     *
     * @return PropertyInterface[]
     */
    public function getChildProperties();

    /**
     * add a child to section.
     *
     * @param PropertyInterface $property
     */
    public function addChild(PropertyInterface $property);
}
