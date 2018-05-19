<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Exception;

/**
 * This exception is thrown when the node name for the node does already exist.
 */
class NodeNameAlreadyExistsException extends DocumentManagerException
{
    /**
     * @var string
     */
    private $nodeName;

    /**
     * @param string $nodeName
     */
    public function __construct($nodeName)
    {
        parent::__construct(
            sprintf('The node name "%s" already exists, and therefore cannot be used to create a new node', $nodeName)
        );
        $this->nodeName = $nodeName;
    }

    /**
     * The name of the node, which was tried to save.
     *
     * @return string
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }
}
