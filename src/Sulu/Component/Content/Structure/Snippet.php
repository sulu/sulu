<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure;

use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;

/**
 * This structure represents some encapsulated content. For example
 * a Snippet might represent a hotel or a page footer.
 */
abstract class Snippet extends Structure
{
    /**
     * state of node.
     *
     * @var int
     */
    private $nodeState;

    public function __construct($key, $metaData)
    {
        parent::__construct($key, $metaData);

        // default state is test
        $this->nodeState = StructureInterface::STATE_TEST;
    }

    /**
     * @param int $state
     *
     * @return int
     */
    public function setNodeState($state)
    {
        $this->nodeState = $state;
    }

    /**
     * returns state of node.
     *
     * @return int
     */
    public function getNodeState()
    {
        return $this->nodeState;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($complete = true)
    {
        $result = array_merge(
            parent::toArray($complete),
            array(
                'nodeState' => $this->getNodeState(),
            )
        );

        return $result;
    }
}
