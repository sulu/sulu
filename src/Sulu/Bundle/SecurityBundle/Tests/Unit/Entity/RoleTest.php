<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Sulu\Bundle\SecurityBundle\Entity\Role;

class RoleTest extends \PHPUnit_Framework_TestCase
{
    private $role;

    public function setUp()
    {
        $this->role = new Role();
    }

    public function testBeforeSave()
    {
        $this->role->updateTimestamps();
        $this->assertNotNull($this->role->getCreated());
        $this->assertNotNull($this->role->getChanged());

        $created = new \DateTime('2013-01-01');
        $changed = new \DateTime('2013-04-01');
        $this->role->setCreated($created);
        $this->role->setChanged($changed);

        $this->role->updateTimestamps();

        $this->assertEquals($created, $this->role->getCreated());
        $this->assertGreaterThan($changed->format('c'), $this->role->getChanged()->format('c'));
    }
}
