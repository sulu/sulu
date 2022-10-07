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

    public function testPatchSettings(): void
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

    public function testGet(): void
    {
        $this->client->jsonRequest('GET', '/api/profile');

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        unset($response['_hash']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertSame([
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'username' => 'test',
            'email' => 'test@example.localhost',
            'locale' => 'en',
        ], $response);
    }

    public function testPut(): void
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
                'twoFactor' => [
                    'method' => 'email',
                ],
            ]
        );

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        unset($response['_hash']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertSame([
            'firstName' => 'Hans',
            'lastName' => 'Mustermann',
            'username' => 'hansi',
            'email' => 'hans.mustermann@muster.at',
            'locale' => 'de',
            'twoFactor' => [
                'method' => 'email',
            ],
        ], $response);
    }

    public function testPutInvalidField(): void
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
                'twoFactor' => [
                    'method' => null,
                ],
            ]
        );

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        unset($response['_hash']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertSame([
            'firstName' => 'Hans',
            'lastName' => 'Mustermann',
            'username' => 'hansi',
            'email' => 'hans.mustermann@muster.at',
            'locale' => 'de',
            'twoFactor' => null,
        ], $response);
    }

    public function testPutEmailNotUnique(): void
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
                'twoFactor' => [
                    'method' => null,
                ],
            ]
        );

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        unset($response['errors']);
        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertSame([
            'code' => 1004,
            'message' => 'The email "existing@email.com" is not unique!',
            'detail' => 'The email address "existing@email.com" is already assigned to another contact.',
        ], $response);
    }

    public function testPutUsernameNotUnique(): void
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

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        unset($response['errors']);
        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertSame([
            'code' => 1001,
            'message' => 'a username has to be unique!',
            'detail' => 'The username "existing-username" is already assigned to another contact.',
        ], $response);
    }

    public function testPutWithoutFirstName(): void
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
                'twoFactor' => [
                    'method' => null,
                ],
            ]
        );

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        unset($response['errors']);
        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertSame([
            'code' => 0,
            'message' => 'The "Sulu\Bundle\ContactBundle\Entity\Contact"-entity requires a "firstName"-argument',
        ], $response);
    }

    public function testPutWithoutLastName(): void
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
                'twoFactor' => [
                    'method' => null,
                ],
            ]
        );

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        unset($response['errors']);
        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertSame([
            'code' => 0,
            'message' => 'The "Sulu\Bundle\ContactBundle\Entity\Contact"-entity requires a "lastName"-argument',
        ], $response);
    }

    public function testPutWithoutUsername(): void
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
                'twoFactor' => [
                    'method' => null,
                ],
            ]
        );

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        unset($response['errors']);
        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertSame([
            'code' => 0,
            'message' => 'The "Sulu\Bundle\SecurityBundle\Entity\User"-entity requires a "username"-argument',
        ], $response);
    }

    public function testPutWithoutEmail(): void
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
                'twoFactor' => [
                    'method' => null,
                ],
            ]
        );

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        unset($response['errors']);
        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertSame([
            'code' => 0,
            'message' => 'The "Sulu\Bundle\SecurityBundle\Entity\User"-entity requires a "email"-argument',
        ], $response);
    }

    public function testPutWithoutLocale(): void
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
                'twoFactor' => [
                    'method' => null,
                ],
            ]
        );

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        unset($response['errors']);
        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertSame([
            'code' => 0,
            'message' => 'The "Sulu\Bundle\SecurityBundle\Entity\User"-entity requires a "locale"-argument',
        ], $response);
    }

    public function testPutWithoutPassword(): void
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
                'twoFactor' => [
                    'method' => null,
                ],
            ]
        );

        /** @var array<string, mixed> $response */
        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        unset($response['_hash']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame([
            'firstName' => 'Hans',
            'lastName' => 'Mustermann',
            'username' => 'hansi',
            'email' => 'hans.mustermann@muster.at',
            'locale' => 'de',
            'twoFactor' => null,
        ], $response);
    }

    public function testDeleteSettings(): void
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
