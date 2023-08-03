<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Security;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AuthenticationHandlerTest extends SuluTestCase
{
    public function testLoginFail(): void
    {
        $client = $this->createClient([], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $client->request('POST', '/admin/login', [], [], [], '{"username": "not-existing-user", "password": "wrong"}');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(401, $response);
        $notExistUserContent = $response->getContent();

        $this->assertSame('{"message":"Invalid credentials."}', $notExistUserContent);
    }

    public function testLoginSuccess(): void
    {
        $client = $this->createClient([], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $testUser = $this->getTestUser();

        $client->request('POST', '/admin/login', [], [], [], '{"username": "' . $testUser->getUsername() . '", "password": "test"}');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $notExistUserContent = $response->getContent();

        $this->assertSame(
            '{"url":"\/admin\/","username":"test","completed":true,"twoFactorMethods":["trusted_devices"]}',
            $notExistUserContent
        );
    }

    public function testLoginFailExistUserHasSameMessageAsNotExist(): void
    {
        $client = $this->createClient([], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $testUser = $this->getTestUser();

        $client->request('POST', '/admin/login', [], [], [], '{"username": "not-existing-user", "password": "wrong"}');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(401, $response);
        $notExistUserContent = $response->getContent();

        $client->request('POST', '/admin/login', [], [], [], '{"username": "' . $testUser->getUsername() . '", "password": "wrong"}');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(401, $response);
        $existUserContent = $response->getContent();

        $this->assertSame($notExistUserContent, $existUserContent);
        $this->assertSame('{"message":"Invalid credentials."}', $notExistUserContent);
    }
}
