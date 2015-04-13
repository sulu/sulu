<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationInterface;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Component\Content\Compat\Structure;

/**
 * Adds a permission tab to the form for editing pages
 */
class SuluSecurityNodesContentNavigation implements ContentNavigationInterface
{
    private $navigation = array();

    public function __construct()
    {
        $permissions = new ContentNavigationItem('Permissions');
        $permissions->setAction('permissions');
        $permissions->setDisplay(array('edit'));
        $permissions->setComponent('permission-tab@sulusecurity');
        $permissions->setComponentOptions(
            array(
                'display' => 'form',
                'type' => Structure::class
            )
        );
        $permissions->setGroups(array('content'));

        $this->navigation[] = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigationItems()
    {
        return $this->navigation;
    }
}
