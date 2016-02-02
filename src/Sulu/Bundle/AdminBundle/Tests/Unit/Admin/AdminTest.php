<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;

class AdminTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Admin
     */
    protected $admin;

    /**
     * @var Navigation
     */
    protected $navigation;

    public function setUp()
    {
        $this->navigation = new Navigation();
        $this->admin = $this->getMockForAbstractClass('Sulu\Bundle\AdminBundle\Admin\Admin');
        $this->admin->setNavigation($this->navigation);
    }

    public function testNavigation()
    {
        $this->assertSame($this->navigation, $this->admin->getNavigation());
    }
}
