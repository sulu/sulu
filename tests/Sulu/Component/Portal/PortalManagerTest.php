<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

use Sulu\Component\Portal\PortalManager;

class PortalManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PortalManager
     */
    protected $portalManager;

    public function setUp()
    {
        $this->portalManager = new PortalManager(array(
            'cache_dir' => __DIR__ . '/../../../Resources/DataFixtures'
        ));
    }

    public function testFindByKey()
    {
        $this->portalManager->findByKey('test');
    }
}
