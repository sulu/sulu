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
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\ContactBundle\Twig\ContactTwigExtension;

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
     * @var ContactRepository
     */
    private $contactRepository;

    protected function setUp()
    {
        $this->cache = new ArrayCache();
        $this->contactRepository = $this->prophesize(ContactRepository::class);

        $this->extension = new ContactTwigExtension($this->cache, $this->contactRepository->reveal());
    }

    public function testResolveContactFunction()
    {
        $contact1 = new Contact();
        $contact1->setFirstName('Hikaru');
        $contact1->setLastName('Sulu');

        $contact2 = new Contact();
        $contact2->setFirstName('John');
        $contact2->setLastName('Cho');

        $this->contactRepository->find(1)->willReturn($contact1);
        $this->contactRepository->find(2)->willReturn($contact2);

        $contact = $this->extension->resolveContactFunction(1);
        $this->assertEquals('Hikaru Sulu', $contact->getFullName());

        $contact = $this->extension->resolveContactFunction(2);
        $this->assertEquals('John Cho', $contact->getFullName());
    }

    public function testResolveContactFunctionNonExisting()
    {
        $contact = $this->extension->resolveContactFunction(3);
        $this->assertNull($contact);
    }
}
