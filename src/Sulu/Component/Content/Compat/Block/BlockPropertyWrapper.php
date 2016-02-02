<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Block;

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyTag;

class BlockPropertyWrapper implements PropertyInterface
{
    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var BlockPropertyInterface
     */
    private $block;

    /**
     * @var int
     */
    private $index;

    /**
     * @param PropertyInterface $property
     * @param PropertyInterface $block
     * @param int               $index
     */
    public function __construct(PropertyInterface $property, PropertyInterface $block, $index = null)
    {
        $this->property = $property;
        $this->block = $block;
        $this->index = $index;
    }

    /**
     * returns name of template.
     *
     * @return string
     */
    public function getName()
    {
        return $this->block->getName() . '-' .
        $this->property->getName() .
        ($this->index !== null ? '#' . $this->index : '');
    }

    /**
     * returns mandatory.
     *
     * @return bool
     */
    public function isMandatory()
    {
        return $this->property->isMandatory();
    }

    /**
     * returns multilingual.
     *
     * @return bool
     */
    public function isMultilingual()
    {
        return $this->property->isMultilingual();
    }

    /**
     * return min occurs.
     *
     * @return int
     */
    public function getMinOccurs()
    {
        return $this->property->getMinOccurs();
    }

    /**
     * return max occurs.
     *
     * @return int
     */
    public function getMaxOccurs()
    {
        return $this->property->getMaxOccurs();
    }

    /**
     * returns name of content type.
     *
     * @return string
     */
    public function getContentTypeName()
    {
        return $this->property->getContentTypeName();
    }

    /**
     * parameter of property.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->property->getParams();
    }

    /**
     * sets the value from property.
     *
     * @param $value mixed
     */
    public function setValue($value)
    {
        $this->property->setValue($value);
    }

    /**
     * gets the value from property.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->property->getValue();
    }

    /**
     * @param \Sulu\Component\Content\Compat\Block\BlockPropertyInterface $block
     */
    public function setBlock($block)
    {
        $this->block = $block;
    }

    /**
     * @return \Sulu\Component\Content\Compat\Block\BlockPropertyInterface
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @param \Sulu\Component\Content\Compat\PropertyInterface $property
     */
    public function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * @return \Sulu\Component\Content\Compat\PropertyInterface
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * returns TRUE if property is a block.
     *
     * @return bool
     */
    public function getIsBlock()
    {
        return $this->property->getIsBlock();
    }

    /**
     * returns TRUE if property is multiple.
     *
     * @return bool
     */
    public function getIsMultiple()
    {
        return $this->property->getIsMultiple();
    }

    /**
     * returns field is mandatory.
     *
     * @return bool
     */
    public function getMandatory()
    {
        return $this->property->getMandatory();
    }

    /**
     * returns field is multilingual.
     *
     * @return bool
     */
    public function getMultilingual()
    {
        return $this->property->getMultilingual();
    }

    /**
     * returns tags defined in xml.
     *
     * @return PropertyTag[]
     */
    public function getTags()
    {
        return $this->property->getTags();
    }

    /**
     * returns tag with given name.
     *
     * @param string $tagName
     *
     * @return PropertyTag
     */
    public function getTag($tagName)
    {
        return $this->property->getTag($tagName);
    }

    /**
     * returns column span.
     *
     * @return string
     */
    public function getColspan()
    {
        return $this->property->getColspan();
    }

    /**
     * returns title of property.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getTitle($languageCode)
    {
        return $this->property->getTitle($languageCode);
    }

    /**
     * returns infoText of property.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getInfoText($languageCode)
    {
        return $this->property->getInfoText($languageCode);
    }

    /**
     * returns placeholder of property.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getPlaceholder($languageCode)
    {
        return $this->property->getPlaceholder($languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        return $this->property->toArray($depth);
    }

    /**
     * {@inheritdoc}
     */
    public function getStructure()
    {
        return $this->property->getStructure();
    }

    /**
     * {@inheritdoc}
     */
    public function setStructure($structure)
    {
        $this->property->setStructure($structure);
    }
}
