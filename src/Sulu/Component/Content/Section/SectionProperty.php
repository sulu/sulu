<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Section;

use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyInterface;

/**
 * defines a section for properties
 * @package Sulu\Component\Content
 */
class SectionProperty extends Property implements SectionPropertyInterface
{
    /**
     * properties managed by this block
     * @var PropertyInterface[]
     */
    private $childProperties = array();

    /**
     * @param string $name
     * @param array $metadata
     * @param string $col
     */
    public function __construct($name, $metadata, $col)
    {
        parent::__construct($name, $metadata, 'section', false, false, 1, 1, array(), array(), $col);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildProperties()
    {
        return $this->childProperties;
    }

    /**
     * {@inheritdoc}
     */
    public function addChild(PropertyInterface $property)
    {
        $this->childProperties[] = $property;
    }
}
