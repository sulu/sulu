<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use PHPCR\SessionInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * This is in preview group because it causes Jackrabbit to lock-up
 * (this is not a bug here, but some bug in Jackrabbit).
 *
 * @group preview
 */
class PreviewControllerTest extends SuluTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var SchemaTool
     */
    protected static $tool;

    /**
     * @var SessionInterface
     */
    public $session;

    protected function setUp()
    {
        $this->purgeDatabase();
        $this->initOrm();
        $this->initPhpcr();
    }

    protected function initOrm()
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $this->em->persist($contact);

        $emailType = new EmailType();
        $emailType->setName('Private');
        $this->em->persist($emailType);
        $this->em->flush();

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        $this->em->persist($email);
        $this->em->flush();

        $role1 = new Role();
        $role1->setName('Role1');
        $role1->setSystem('Sulu');
        $this->em->persist($role1);

        $user = new User();
        $user->setUsername('admin');
        $user->setPassword('securepassword');
        $user->setSalt('salt');
        $user->setLocale('de');
        $user->setContact($contact);
        $this->em->persist($user);
        $this->em->flush();

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user);
        $userRole1->setLocale(json_encode(['de', 'en']));
        $this->em->persist($userRole1);
        $this->em->flush();

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($role1);
        $permission1->setContext('Context 1');
        $this->em->persist($permission1);
        $this->em->flush();
    }

    public function testRender()
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/test',
            'article' => 'Test',
        ];

        $client->request('POST', '/api/nodes?&webspace=sulu_io&language=de_at', $data);
        $response = json_decode($client->getResponse()->getContent());

        $client->request('GET', '/content/preview/' . $response->id . '/render?webspace=sulu_io&language=de_at');
        $response = $client->getResponse()->getContent();

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertTrue(strpos($response, '<h1 property="title">Hello Hikaru Sulu</h1>') > -1);
    }

    public function testRenderHtml5()
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => 'Testtitle',
            'template' => 'html5',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/test',
            'article' => 'Test',
        ];

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=de_at', $data);
        $response = json_decode($client->getResponse()->getContent());

        $client->request('GET', '/content/preview/' . $response->id . '/render?webspace=sulu_io&language=de_at');
        $response = $client->getResponse()->getContent();

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertTrue(strpos($response, '<h1>Hello Hikaru Sulu</h1>') > -1);
        $this->assertTrue(strpos($response, '<nav>') > -1);
        $this->assertTrue(strpos($response, '</nav>') > -1);
    }

    public function testRenderInvalidHtml()
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => 'Testtitle',
            'template' => 'invalidhtml',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/test',
            'article' => 'Test',
        ];

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=de_at', $data);
        $response = json_decode($client->getResponse()->getContent());

        $client->request('GET', '/content/preview/' . $response->id . '/render?webspace=sulu_io&language=de_at');
        $response = $client->getResponse()->getContent();

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertTrue(strpos($response, '<h1>Hello Hikaru Sulu</h1>') > -1);
        $this->assertTrue(preg_match('/^\<p\>This is a fabulous test case!\s*\<\/p\>/', $response) > -1);
        $this->assertTrue(strpos($response, '<nav>') > -1);
        $this->assertTrue(strpos($response, '</nav>') > -1);
    }
}
