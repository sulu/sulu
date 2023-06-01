<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Exception\NodeNameAlreadyExistsException;

/**
 * Ensures that node names are unique.
 */
class NameResolver
{
    /**
     * @param string $name
     * @param bool $autoRename When set to false an exception is thrown, in case the passed name already exists
     *
     * @return string
     *
     * @throws NodeNameAlreadyExistsException
     */
    public function resolveName(NodeInterface $parentNode, $name, ?NodeInterface $forNode = null, $autoRename = true)
    {
        $index = 0;
        $baseName = $name;

        if ($this->hasNameConflict($parentNode, $name, $forNode) && !$autoRename) {
            throw new NodeNameAlreadyExistsException($name);
        }

        while ($this->hasNameConflict($parentNode, $name, $forNode)) {
            $name = $baseName . '-' . ++$index;
        }

        return $name;
    }

    /**
     * @param string $name
     * @param NodeInterface $forNode
     *
     * @return bool
     */
    private function hasNameConflict(NodeInterface $parentNode, $name, ?NodeInterface $forNode = null)
    {
        return $parentNode->hasNode($name) && (!$forNode || $parentNode->getNode($name) !== $forNode);
    }
}
