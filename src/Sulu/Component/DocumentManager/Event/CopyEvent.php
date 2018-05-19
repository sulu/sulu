<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use PHPCR\NodeInterface;

class CopyEvent extends MoveEvent
{
    /**
     * @var NodeInterface
     */
    private $copiedNode;

    /**
     * @return string|null
     */
    public function getCopiedPath()
    {
        if (!$this->copiedNode) {
            return;
        }

        return $this->copiedNode->getPath();
    }

    /**
     * @return NodeInterface
     */
    public function getCopiedNode()
    {
        return $this->copiedNode;
    }

    /**
     * @param NodeInterface $copiedNode
     */
    public function setCopiedNode($copiedNode)
    {
        $this->copiedNode = $copiedNode;
    }
}
