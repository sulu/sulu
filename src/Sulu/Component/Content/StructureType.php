<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;


class StructureType
{
    public static function getGhost($value)
    {
        return new StructureType('ghost', $value);
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    private function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * return name of type
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * return value of type
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * returns a array representation
     * @return array
     */
    public function toArray()
    {
        return array('name' => $this->getName(), 'value' => $this->getValue());
    }
}
