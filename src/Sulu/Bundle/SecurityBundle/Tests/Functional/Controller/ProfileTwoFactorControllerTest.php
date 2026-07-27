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

use OTPHP\TOTP;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ProfileTwoFactorControllerTest extends SuluTestCase
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

    public function testTotpSetup(): void
    {
        $this->client->jsonRequest('POST', '/api/profile/two-factor/setup', ['method' => 'totp']);

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        /** @var array{secret: non-empty-string, qrContent: string} $response */
        $response = \json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['secret']);
        $this->assertStringStartsWith('otpauth://totp/', $response['qrContent']);
        $this->assertStringContainsString($response['secret'], $response['qrContent']);

        // the method must not be activated before the code was confirmed
        $user = $this->refreshTestUser();
        $this->assertNotNull($user->getTwoFactor());
        $this->assertNull($user->getTwoFactor()->getMethod());
        $this->assertSame($response['secret'], $user->getTwoFactor()->getOptions()['totpSecret'] ?? null);
    }

    public function testTotpConfirm(): void
    {
        $this->client->jsonRequest('POST', '/api/profile/two-factor/setup', ['method' => 'totp']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        /** @var array{secret: non-empty-string} $setupResponse */
        $setupResponse = \json_decode((string) $this->client->getResponse()->getContent(), true);

        $code = TOTP::create($setupResponse['secret'])->now();

        $this->client->jsonRequest('POST', '/api/profile/two-factor/confirm', ['method' => 'totp', 'code' => $code]);

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $user = $this->refreshTestUser();
        $this->assertNotNull($user->getTwoFactor());
        $this->assertSame('totp', $user->getTwoFactor()->getMethod());
    }

    public function testTotpConfirmInvalidCode(): void
    {
        $this->client->jsonRequest('POST', '/api/profile/two-factor/setup', ['method' => 'totp']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest('POST', '/api/profile/two-factor/confirm', ['method' => 'totp', 'code' => 'invalid']);

        $this->assertHttpStatusCode(400, $this->client->getResponse());

        $user = $this->refreshTestUser();
        $this->assertNotNull($user->getTwoFactor());
        $this->assertNull($user->getTwoFactor()->getMethod());
    }

    public function testTotpConfirmWithoutSetup(): void
    {
        $this->client->jsonRequest('POST', '/api/profile/two-factor/confirm', ['method' => 'totp', 'code' => '123456']);

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testGoogleSetup(): void
    {
        $this->client->jsonRequest('POST', '/api/profile/two-factor/setup', ['method' => 'google']);

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        /** @var array{secret: non-empty-string, qrContent: string} $response */
        $response = \json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['secret']);
        $this->assertStringStartsWith('otpauth://totp/', $response['qrContent']);

        $user = $this->refreshTestUser();
        $this->assertNotNull($user->getTwoFactor());
        $this->assertNull($user->getTwoFactor()->getMethod());
        $this->assertSame(
            $response['secret'],
            $user->getTwoFactor()->getOptions()['googleAuthenticatorSecret'] ?? null,
        );
    }

    public function testSetupInvalidMethod(): void
    {
        $this->client->jsonRequest('POST', '/api/profile/two-factor/setup', ['method' => 'email']);

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testBackupCodes(): void
    {
        $user = $this->activateTwoFactor();
        $client = $this->createLoggedInClient($user);

        $client->jsonRequest('POST', '/api/profile/two-factor/backup-codes');

        $this->assertHttpStatusCode(200, $client->getResponse());
        /** @var array{backupCodes: string[]} $response */
        $response = \json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertCount(10, $response['backupCodes']);
        $this->assertNotContains('11111111', $response['backupCodes']);

        $user = $this->refreshTestUser();
        $this->assertNotNull($user->getTwoFactor());

        // the previous backup code was invalidated and the new hashed codes match the returned ones
        $this->assertFalse($user->isBackupCode('11111111'));
        $this->assertTrue($user->isBackupCode($response['backupCodes'][0]));
    }

    public function testBackupCodesWithEmailMethod(): void
    {
        /** @var User $user */
        $user = static::getTestUser();
        $twoFactor = new UserTwoFactor($user);
        $twoFactor->setMethod('email');
        $user->setTwoFactor($twoFactor);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($twoFactor);
        $entityManager->flush();

        $client = $this->createLoggedInClient($user);

        $client->jsonRequest('POST', '/api/profile/two-factor/backup-codes');

        $this->assertHttpStatusCode(200, $client->getResponse());
        /** @var array{backupCodes: string[]} $response */
        $response = \json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertCount(10, $response['backupCodes']);
    }

    public function testBackupCodesWithoutTwoFactor(): void
    {
        $this->client->jsonRequest('POST', '/api/profile/two-factor/backup-codes');

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testDelete(): void
    {
        $user = $this->activateTwoFactor();
        $client = $this->createLoggedInClient($user);

        $client->jsonRequest('DELETE', '/api/profile/two-factor');

        $this->assertHttpStatusCode(204, $client->getResponse());

        $user = $this->refreshTestUser();
        $this->assertNull($user->getTwoFactor());
    }

    public function testPutProfileTotpMethodWithoutSetup(): void
    {
        $this->client->jsonRequest('PUT', '/api/profile', [
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'username' => 'test',
            'email' => 'test@example.localhost',
            'locale' => 'en',
            'twoFactor' => ['method' => 'totp'],
        ]);

        $this->assertHttpStatusCode(400, $this->client->getResponse());

        $user = $this->refreshTestUser();
        $this->assertNull($user->getTwoFactor()?->getMethod());
    }

    public function testPutProfileTotpMethodAfterSetup(): void
    {
        $this->client->jsonRequest('POST', '/api/profile/two-factor/setup', ['method' => 'totp']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest('PUT', '/api/profile', [
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'username' => 'test',
            'email' => 'test@example.localhost',
            'locale' => 'en',
            'twoFactor' => ['method' => 'totp'],
        ]);

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $user = $this->refreshTestUser();
        $this->assertSame('totp', $user->getTwoFactor()?->getMethod());
    }

    /**
     * Activates the "totp" method for the test user directly in the database.
     */
    private function activateTwoFactor(): User
    {
        /** @var User $user */
        $user = static::getTestUser();
        $twoFactor = new UserTwoFactor($user);
        $twoFactor->setMethod('totp');
        $twoFactor->setOptions(['totpSecret' => 'SECRET', 'backupCodes' => ['11111111']]);
        $user->setTwoFactor($twoFactor);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($twoFactor);
        $entityManager->flush();

        return $user;
    }

    /**
     * Creates a client with a session based token, because the http basic authentication of the
     * test firewall would require the second factor for a user with active two factor method.
     */
    private function createLoggedInClient(User $user): KernelBrowser
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user, 'test');

        return $client;
    }

    private function refreshTestUser(): User
    {
        $entityManager = $this->getEntityManager();
        $entityManager->clear();

        /** @var User $user */
        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => 'test']);

        return $user;
    }
}
