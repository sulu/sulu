<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle;

use PHPCR\Migrations\VersionInterface;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;

/**
 * Removes properties like 'i18n:-*'.
 */
class Version201512090753 implements VersionInterface
{
    /**
     * @return void
     */
    public function up(SessionInterface $session)
    {
        $root = $session->getRootNode();

        $this->upgradeNode($root);
    }

    /**
     * @return void
     */
    public function down(SessionInterface $session)
    {
    }

    /**
     * Removes non translated properties.
     */
    private function upgradeNode(NodeInterface $node)
    {
        foreach ($node->getProperties('i18n:-*') as $property) {
            $property->remove();
        }

        foreach ($node->getNodes() as $childNode) {
            $this->upgradeNode($childNode);
        }
    }
}
