<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ProfileControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
    }

    public function testPatchSettings()
    {
        $this->client->jsonRequest(
            'PATCH',
            '/api/profile/settings',
            ['setting-key' => 'setting-value']
        );

        $userSetting = $this->client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $this->client->getContainer()->get('security.token_storage')->getToken()->getUser(),
                'key' => 'setting-key',
            ]
        );

        $this->assertEquals('setting-key', $userSetting->getKey());
        $this->assertEquals('setting-value', \json_decode($userSetting->getValue()));
    }

    public function testGet()
    {
        $this->client->jsonRequest('GET', '/api/profile');

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('test', $response->username);
        $this->assertEquals('', $response->email);
        $this->assertObjectNotHasAttribute('password', $response);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Max', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
    }

    public function testPut()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('Hans', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('hansi', $response->username);
        $this->assertEquals('hans.mustermann@muster.at', $response->email);
        $this->assertEquals('de', $response->locale);
    }

    public function testPutInvalidField()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('Hans', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('hansi', $response->username);
        $this->assertEquals('hans.mustermann@muster.at', $response->email);
        $this->assertEquals('de', $response->locale);
    }

    public function testPutEmailNotUnique()
    {
        $existingContact = new Contact();
        $existingContact->setFirstName('Max');
        $existingContact->setLastName('Muster');

        $existingUser = new User();
        $existingUser->setUsername('existing-username');
        $existingUser->setEmail('existing@email.com');
        $existingUser->setPassword('securepassword');
        $existingUser->setSalt('salt');
        $existingUser->setLocale('de');
        $existingUser->setContact($existingContact);

        static::getEntityManager()->persist($existingContact);
        static::getEntityManager()->persist($existingUser);
        static::getEntityManager()->flush();

        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'email' => 'existing@email.com',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertEquals(1004, $response->code);
        $this->assertEquals('The email address "existing@email.com" is already assigned to another contact.', $response->detail);
    }

    public function testPutUsernameNotUnique()
    {
        $existingContact = new Contact();
        $existingContact->setFirstName('Max');
        $existingContact->setLastName('Muster');

        $existingUser = new User();
        $existingUser->setUsername('existing-username');
        $existingUser->setEmail('existing@email.com');
        $existingUser->setPassword('securepassword');
        $existingUser->setSalt('salt');
        $existingUser->setLocale('de');
        $existingUser->setContact($existingContact);

        static::getEntityManager()->persist($existingContact);
        static::getEntityManager()->persist($existingUser);
        static::getEntityManager()->flush();

        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'existing-username',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertEquals(
            'a username has to be unique!',
            $response->message
        );
    }

    public function testPutWithoutFirstName()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\ContactBundle\Entity\Contact"-entity requires a "firstName"-argument',
            $response->message
        );
    }

    public function testPutWithoutLastName()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'firstName' => 'Hans',
                'username' => 'hansi',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\ContactBundle\Entity\Contact"-entity requires a "lastName"-argument',
            $response->message
        );
    }

    public function testPutWithoutUsername()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\SecurityBundle\Entity\User"-entity requires a "username"-argument',
            $response->message
        );
    }

    public function testPutWithoutEmail()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\SecurityBundle\Entity\User"-entity requires a "email"-argument',
            $response->message
        );
    }

    public function testPutWithoutLocale()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'password' => 'testpassword',
                'email' => 'hans.mustermann@muster.at',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\SecurityBundle\Entity\User"-entity requires a "locale"-argument',
            $response->message
        );
    }

    public function testPutWithoutPassword()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'email' => 'hans.mustermann@muster.at',
                'locale' => 'de',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testDeleteSettings()
    {
        $this->client->jsonRequest(
            'PATCH',
            '/api/profile/settings',
            ['setting-key' => 'setting-value']
        );

        $userSetting = $this->client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $this->client->getContainer()->get('security.token_storage')->getToken()->getUser(),
                'key' => 'setting-key',
            ]
        );

        $this->assertEquals('setting-key', $userSetting->getKey());
        $this->assertEquals('setting-value', \json_decode($userSetting->getValue()));

        $this->client->jsonRequest(
            'DELETE',
            '/api/profile/settings',
            ['key' => 'setting-key']
        );

        $userSetting = $this->client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $this->client->getContainer()->get('security.token_storage')->getToken()->getUser(),
                'key' => 'setting-key',
            ]
        );

        $this->assertNull($userSetting);
    }
}
