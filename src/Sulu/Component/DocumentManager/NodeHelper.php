<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\PathHelper;
use PHPCR\Util\UUIDHelper;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

/**
 * The NodeHelper takes a node and some additional arguments to execute certain actions based on the passed node,
 * especially on the session of the passed Node.
 */
class NodeHelper implements NodeHelperInterface
{
    /**
     * {@inheritdoc}
     */
    public function move(NodeInterface $node, $parentUuid, $destinationName = null)
    {
        if (!$destinationName) {
            $destinationName = $node->getName();
        }

        $session = $node->getSession();
        $parentPath = $this->normalizePath($session, $parentUuid);
        $session->move($node->getPath(), $parentPath . '/' . $destinationName);
    }

    /**
     * {@inheritdoc}
     */
    public function copy(NodeInterface $node, $parentUuid, $destinationName = null)
    {
        if (!$destinationName) {
            $destinationName = $node->getName();
        }

        $session = $node->getSession();
        $parentPath = $this->normalizePath($session, $parentUuid);
        $destinationPath = $parentPath . '/' . $destinationName;
        $session->getWorkspace()->copy($node->getPath(), $destinationPath);

        return $destinationPath;
    }

    /**
     * {@inheritdoc}
     */
    public function reorder(NodeInterface $node, $destinationUuid)
    {
        $session = $node->getSession();
        $parentNode = $node->getParent();

        if (!$destinationUuid) {
            $parentNode->orderBefore($node->getName(), null);

            return;
        }

        $siblingPath = $session->getNodeByIdentifier($destinationUuid)->getPath();

        if (PathHelper::getParentPath($siblingPath) !== $parentNode->getPath()) {
            throw new DocumentManagerException(
                sprintf(
                    'Cannot reorder documents which are not sibilings. Trying to reorder "%s" to "%s".',
                    $node->getPath(),
                    $siblingPath
                )
            );
        }

        $parentNode->orderBefore($node->getName(), PathHelper::getNodeName($siblingPath));
    }

    /**
     * Returns the path based on the given UUID.
     *
     * @param SessionInterface $session
     * @param string $identifier
     *
     * @return string
     */
    private function normalizePath(SessionInterface $session, $identifier)
    {
        if (!UUIDHelper::isUUID($identifier)) {
            return $identifier;
        }

        return $session->getNodeByIdentifier($identifier)->getPath();
    }
}
