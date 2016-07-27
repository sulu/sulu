<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash\Tests;

use Sulu\Component\Content\Document\Behavior\LocalizedAuditableBehavior;
use Sulu\Component\Hash\AuditableHasher;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

class AuditableHasherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuditableHasher
     */
    private $hasher;

    public function setUp()
    {
        $this->hasher = new AuditableHasher();
    }

    public function testHashWrongObject()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->hasher->hash(new \stdClass());
    }

    public function testHashSameObject()
    {
        /** @var AuditableInterface $object */
        $object = $this->prophesize(AuditableInterface::class);
        /** @var UserInterface $user */
        $user = $this->prophesize(UserInterface::class);
        $user->getId()->willReturn(1);
        $object->getChanger()->willReturn($user->reveal());
        $object->getChanged()->willReturn(new \DateTime('2016-02-05'));

        $this->assertSame($this->hasher->hash($object->reveal()), $this->hasher->hash($object->reveal()));
    }

    public function provideDifferentObjects()
    {
        return [
            [1, 2, new \DateTime('2016-02-05'), new \DateTime('2016-02-04')],
            [1, 1, new \DateTime('2016-02-05'), new \DateTime('2016-02-04')],
            [1, 2, new \DateTime('2016-02-05'), new \DateTime('2016-02-05')],
        ];
    }

    /**
     * @dataProvider provideDifferentObjects
     */
    public function testHashDifferentObject($changer1, $changer2, $changed1, $changed2)
    {
        /** @var AuditableInterface $object1 */
        $object1 = $this->prophesize(AuditableInterface::class);
        /** @var UserInterface $user1 */
        $user1 = $this->prophesize(UserInterface::class);
        $user1->getId()->willReturn($changer1);
        $object1->getChanger()->willReturn($user1->reveal());
        $object1->getChanged()->willReturn($changed1);

        /** @var AuditableInterface $object2 */
        $object2 = $this->prophesize(AuditableInterface::class);
        /** @var UserInterface $user2 */
        $user2 = $this->prophesize(UserInterface::class);
        $user2->getId()->willReturn($changer2);
        $object2->getChanger()->willReturn($user2->reveal());
        $object2->getChanged()->willReturn($changed2);

        $this->assertNotSame($this->hasher->hash($object1->reveal()), $this->hasher->hash($object2->reveal()));
    }

    public function testHashWithoutChanger()
    {
        /** @var AuditableInterface $object */
        $object = $this->prophesize(AuditableInterface::class);
        $object->getChanger()->willReturn(null);
        $object->getChanged()->willReturn(new \DateTime('2016-02-05'));

        $this->assertInternalType('string', $this->hasher->hash($object->reveal()));
    }

    public function testHashWithoutDate()
    {
        $user = $this->prophesize(UserInterface::class);
        $user->getId()->willReturn(1);

        $object = $this->prophesize(AuditableInterface::class);
        $object->getChanger()->willReturn($user);
        $object->getChanged()->willReturn(null);

        $this->assertInternalType('string', $this->hasher->hash($object->reveal()));
    }

    public function testHashAuditableBehavior()
    {
        $object = $this->prophesize(LocalizedAuditableBehavior::class);
        $object->getChanger()->willReturn(1);
        $object->getChanged()->willReturn(new \DateTime('2016-02-09'));

        $this->assertInternalType('string', $this->hasher->hash($object->reveal()));
    }

    public function testHashAuditableBehaviorWithoutDate()
    {
        $object = $this->prophesize(LocalizedAuditableBehavior::class);
        $object->getChanger()->willReturn(1);
        $object->getChanged()->willReturn(null);

        $this->assertInternalType('string', $this->hasher->hash($object->reveal()));
    }
}
