<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

class ExtensionNotFoundException extends \Exception
{
    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var string
     */
    private $name;

    public function __construct(StructureInterface $structure, $name)
    {
        parent::__construct(sprintf('Extension "%s" not found in structure "%s"', $name, get_class($structure)));
        $this->structure = $structure;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return StructureInterface
     */
    public function getStructure()
    {
        return $this->structure;
    }
}
