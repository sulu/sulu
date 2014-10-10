<?php

namespace Sulu\Component\Content\Structure;

use Sulu\Component\Content\Structure;
use Sulu\Component\Content\Metadata;
use Sulu\Component\Content\StructureInterface;

abstract class Snippet extends Structure
{

    /**
     * state of node
     * @var int
     */
    private $nodeState;

    function __construct()
    {
        // default state is test
        $this->nodeState = StructureInterface::STATE_TEST;
    }

    /**
     * @param int $state
     * @return int
     */
    public function setNodeState($state)
    {
        $this->nodeState = $state;
    }

    /**
     * returns state of node
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
                'nodeState' => $this->getNodeState()
            )
        );

        return $result;
    }
}
