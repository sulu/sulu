<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace\Dumper;

use Sulu\Component\Workspace\WorkspaceCollection;

class PhpWorkspaceCollectionDumper extends WorkspaceCollectionDumper
{
    /**
     * @var WorkspaceCollection
     */
    private $workspaceCollection;

    public function __construct(WorkspaceCollection $workspaceCollection)
    {
        $this->workspaceCollection = $workspaceCollection;
    }

    /**
     * Creates a new class with the data from the given collection
     * @param array $options
     * @return string
     */
    public function dump($options = array())
    {
        return $this->render(
            'WorkspaceCollectionClass.php.twig',
            array(
                'cache_class' => $options['cache_class'],
                'base_class' => $options['base_class'],
                'workspaces' => $this->workspaceCollection->toArray()
            )
        );
    }
}
