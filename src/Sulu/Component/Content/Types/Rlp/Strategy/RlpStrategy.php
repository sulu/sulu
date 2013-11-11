<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Strategy;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;

/**
 * base class for Resource Locator Path Strategy
 */
abstract class RlpStrategy implements RlpStrategyInterface
{

    /**
     * @var string name of strategy
     */
    protected $name;

    /**
     * @param string $name name of RLP Strategy
     * @param RlpMapperInterface $mapper
     */
    public function __construct($name, RlpMapperInterface $mapper)
    {
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

    /**
     * returns a clean string
     * @param string $dirty dirty string to cleanup
     * @return string clean string
     */
    protected function cleanup($dirty)
    {
        // TODO: Implement cleanup() method.
    }

    /**
     * save route in storage with reference on given contentNode
     * @param NodeInterface $contentNode
     * @param string $path to generate
     * @param string $portal key of portal
     */
    public function save(NodeInterface $contentNode, $path, $portal)
    {
        // TODO: Implement save() method.
    }

    /**
     * checks if path is valid
     * @param string $path path of route
     * @param string $portal key of portal
     * @return bool
     */
    public function isValid($path, $portal)
    {
        // TODO: Implement isValid() method.
    }
}
