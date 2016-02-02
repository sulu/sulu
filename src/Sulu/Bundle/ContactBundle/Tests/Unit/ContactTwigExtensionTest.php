<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use PHPUnit_Framework_TestCase;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Twig\ContactTwigExtension;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;

class ContactTwigExtensionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ContactTwigExtension
     */
    private $extension;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var UserRepository
     */
    private $userRepository;

    protected function setUp()
    {
        $this->cache = new ArrayCache();
        $this->userRepository = $this->getMock(
            'Sulu\Bundle\SecurityBundle\Entity\UserRepository',
            ['findUserById'],
            [],
            'UserRepositoryMock',
            false,
            false
        );

        $this->extension = new ContactTwigExtension($this->cache, $this->userRepository);
    }

    public function testResolveUserFunction()
    {
        $user1 = new User();
        $contact1 = new Contact();
        $contact1->setFirstName('Hikaru');
        $contact1->setLastName('Sulu');
        $user1->setContact($contact1);

        $user2 = new User();
        $contact2 = new Contact();
        $contact2->setFirstName('John');
        $contact2->setLastName('Cho');
        $user2->setContact($contact2);

        $this->userRepository
            ->expects($this->exactly(2))
            ->method('findUserById')
            ->will(
                $this->returnValueMap(
                    [
                        [1, $user1],
                        [2, $user2],
                    ]
                )
            );

        $contact = $this->extension->resolveUserFunction(1);
        $this->assertEquals('Hikaru Sulu', $contact->getFullName());

        $contact = $this->extension->resolveUserFunction(2);
        $this->assertEquals('John Cho', $contact->getFullName());
    }

    public function testResolveUserFunctionNonExisting()
    {
        $contact = $this->extension->resolveUserFunction(3);
        $this->assertNull($contact);
    }
}
