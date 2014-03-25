<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Block;

use Sulu\Component\Content\PropertyInterface;

interface BlockPropertyInterface extends PropertyInterface
{
    /**
     * returns a list of properties managed by this block
     * @return Array of PropertyInterface
     */
    public function getSubProperties();

    /**
     * @param PropertyInterface $property
     */
    public function addSubProperty(PropertyInterface $property);
} 
