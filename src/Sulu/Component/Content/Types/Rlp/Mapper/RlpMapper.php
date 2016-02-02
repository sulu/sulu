<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Mapper;

/**
 * base class for Resource Locator Path Mapper.
 */
abstract class RlpMapper implements RlpMapperInterface
{
    /**
     * name of mapper.
     *
     * @var string
     */
    private $name;

    /**
     * @param string $name name of mapper
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * returns name of mapper.
     *
     * @return string
     */
    public function getName()
    {
        $this->name;
    }
}
