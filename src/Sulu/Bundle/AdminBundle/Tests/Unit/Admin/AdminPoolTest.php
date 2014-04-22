<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Symfony\Component\Console\Command\Command;

class AdminPoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdminPool
     */
    protected $adminPool;

    /**
     * @var Admin
     */
    protected $admin1;

    /**
     * @var Admin
     */
    protected $admin2;

    /**
     * @var Command
     */
    protected $command;

    public function setUp()
    {
        $this->adminPool = new AdminPool();
        $this->admin1 = $this->getMockForAbstractClass(
            'Sulu\Bundle\AdminBundle\Admin\Admin',
            array(),
            '',
            true,
            true,
            true,
            array('getCommands', 'getSecurityContexts')
        );
        $this->admin2 = $this->getMockForAbstractClass(
            'Sulu\Bundle\AdminBundle\Admin\Admin',
            array(),
            '',
            true,
            true,
            true,
            array('getCommands', 'getSecurityContexts')
        );
        $this->command = $this->getMock('Command');
        $this->admin1->expects($this->any())
            ->method('getCommands')
            ->will($this->returnValue(array(new $this->command)));
        $this->admin2->expects($this->any())
            ->method('getCommands')
            ->will($this->returnValue(array(new $this->command)));
        $this->admin1->expects($this->any())
            ->method('getSecurityContexts')
            ->will(
                $this->returnValue(
                    array(
                        'Sulu' => array(
                            'Assets' => array(
                                'assets.videos',
                                'assets.pictures',
                                'assets.documents'
                            )
                        )
                    )
                )
            );
        $this->admin2->expects($this->any())
            ->method('getSecurityContexts')
            ->will(
                $this->returnValue(
                    array(
                        'Sulu' => array(
                            'Portal' => array(
                                'portals.com',
                                'portals.de'
                            )
                        )
                    )
                )
            );
        $rootItem1 = new NavigationItem('Root');
        $rootItem1->addChild(new NavigationItem('Child1'));
        $this->admin1->setNavigation(new Navigation($rootItem1));
        $rootItem2 = new NavigationItem('Root');
        $rootItem2->addChild(new NavigationItem('Child2'));
        $this->admin2->setNavigation(new Navigation($rootItem2));
        $this->adminPool->addAdmin($this->admin1);
        $this->adminPool->addAdmin($this->admin2);
    }

    public function testAdmins()
    {
        $this->assertEquals(2, count($this->adminPool->getAdmins()));
        $this->assertSame($this->admin1, $this->adminPool->getAdmins()[0]);
        $this->assertSame($this->admin2, $this->adminPool->getAdmins()[1]);
    }

    public function testMergeNavigations()
    {
        $navigation = $this->adminPool->getNavigation();
        $this->assertEquals('Child1', $navigation->getRoot()->getChildren()[0]->getName());
        $this->assertEquals('Child2', $navigation->getRoot()->getChildren()[1]->getName());
    }

    public function testCommands()
    {
        $this->assertEquals($this->command, $this->adminPool->getCommands()[0]);
    }

    public function testSecurityContexts()
    {
        $contexts = $this->adminPool->getSecurityContexts();

        $this->assertEquals(
            array(
                'assets.videos',
                'assets.pictures',
                'assets.documents'
            ),
            $contexts['Sulu']['Assets']
        );
        $this->assertEquals(
            array(
                'portals.com',
                'portals.de'
            ),
            $contexts['Sulu']['Portal']
        );
    }
}
