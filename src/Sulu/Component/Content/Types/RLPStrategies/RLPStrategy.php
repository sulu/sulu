<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\RLPStrategies;

/**
 * base class for Resource Locator Path Strategy
 */
abstract class RLPStrategy implements RLPStrategyInterface {

    protected $name;

    /**
     * @param string $name name of RLP Strategy
     */
    public function __construct($name){
        $this->name = $name;
    }

    /**
     * returns name of RLP Strategy (e.g. whole tree)
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
