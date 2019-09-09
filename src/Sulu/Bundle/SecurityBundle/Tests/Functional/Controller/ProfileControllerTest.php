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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ProfileControllerTest extends SuluTestCase
{
    public function setUp(): void
    {
        $this->purgeDatabase();
    }

    public function testPatchSettings()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/security/profile/settings',
            ['setting-key' => 'setting-value']
        );

        $userSetting = $client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $client->getContainer()->get('security.token_storage')->getToken()->getUser(),
                'key' => 'setting-key',
            ]
        );

        $this->assertEquals('setting-key', $userSetting->getKey());
        $this->assertEquals('setting-value', json_decode($userSetting->getValue()));
    }

    public function testGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/security/profile');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals('test', $response->username);
        $this->assertEquals('', $response->email);
        $this->assertObjectNotHasAttribute('password', $response);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Max', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/security/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals('Hans', $response->firstName);
        $this->assertEquals('Mustermann', $response->lastName);
        $this->assertEquals('hansi', $response->username);
        $this->assertEquals('hans.mustermann@muster.at', $response->email);
        $this->assertEquals('de', $response->locale);
    }

    public function testPutEmailNotUnique()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/security/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'email' => '',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(409, $client->getResponse());
        $this->assertEquals(
            'The email "" is not unique!',
            $response->message
        );
    }

    public function testPutUsernameNotUnique()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/security/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => '',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(409, $client->getResponse());
        $this->assertEquals(
            'a username has to be unique!',
            $response->message
        );
    }

    public function testPutWithoutFirstName()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/security/profile',
            [
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );
        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\ContactBundle\Entity\Contact"-entity requires a "firstName"-argument',
            $response->message
        );
    }

    public function testPutWithoutLastName()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/security/profile',
            [
                'firstName' => 'Hans',
                'username' => 'hansi',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\ContactBundle\Entity\Contact"-entity requires a "lastName"-argument',
            $response->message
        );
    }

    public function testPutWithoutUsername()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/security/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'email' => 'hans.mustermann@muster.at',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\SecurityBundle\Entity\User"-entity requires a "username"-argument',
            $response->message
        );
    }

    public function testPutWithoutEmail()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/security/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'password' => 'testpassword',
                'locale' => 'de',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\SecurityBundle\Entity\User"-entity requires a "email"-argument',
            $response->message
        );
    }

    public function testPutWithoutLocale()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/security/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'password' => 'testpassword',
                'email' => 'hans.mustermann@muster.at',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(
            'The "Sulu\Bundle\SecurityBundle\Entity\User"-entity requires a "locale"-argument',
            $response->message
        );
    }

    public function testPutWithoutPassword()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/security/profile',
            [
                'firstName' => 'Hans',
                'lastName' => 'Mustermann',
                'username' => 'hansi',
                'email' => 'hans.mustermann@muster.at',
                'locale' => 'de',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());
    }

    public function testDeleteSettings()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/security/profile/settings',
            ['setting-key' => 'setting-value']
        );

        $userSetting = $client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $client->getContainer()->get('security.token_storage')->getToken()->getUser(),
                'key' => 'setting-key',
            ]
        );

        $this->assertEquals('setting-key', $userSetting->getKey());
        $this->assertEquals('setting-value', json_decode($userSetting->getValue()));

        $client->request(
            'DELETE',
            '/security/profile/settings',
            ['key' => 'setting-key']
        );

        $userSetting = $client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $client->getContainer()->get('security.token_storage')->getToken()->getUser(),
                'key' => 'setting-key',
            ]
        );

        $this->assertNull($userSetting);
    }
}
