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

use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyInterface;

class BlockProperty extends Property implements BlockPropertyInterface
{
    /**
     * properties managed by this block
     * @var PropertyInterface
     */
    private $subProperties = array();

    function __construct(
        $name,
        $mandatory = false,
        $multilingual = false,
        $maxOccurs = 1,
        $minOccurs = 1,
        $params = array()
    )
    {
        parent::__construct($name, 'block', $mandatory, $multilingual, $maxOccurs, $minOccurs, $params);
    }

    /**
     * returns a list of properties managed by this block
     * @return PropertyInterface
     */
    public function getSubProperties()
    {
        return $this->subProperties;
    }

    /**
     * @param PropertyInterface $property
     */
    public function addSubProperty(PropertyInterface $property)
    {
        $this->subProperties[] = $property;
    }
}
