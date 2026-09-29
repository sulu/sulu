<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Infrastructure\Sulu\Admin\WebspaceSettingAdmin;
use Sulu\Page\Tests\Traits\CreateWebspaceSettingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class WebspaceSettingControllerPermissionsTest extends SuluTestCase
{
    use CreateWebspaceSettingTrait;

    private const VIEW = 64;
    private const VIEW_EDIT = 80;
    private const VIEW_EDIT_LIVE = 82;

    public function testTheAdminConfigCarriesThePermissionsOfTheSettingsForTheTab(): void
    {
        self::purgeDatabase();
        $this->createUserWithPermissions('viewuser', [
            WebspaceSettingAdmin::getSecurityContext('sulu-io') => self::VIEW,
            'sulu.webspaces.sulu-io' => self::VIEW_EDIT_LIVE,
        ]);
        self::ensureKernelShutdown();

        $client = $this->createClientForUser('viewuser');
        $client->request('GET', '/admin/config');
        $this->assertHttpStatusCode(200, $client->getResponse());

        /** @var array{sulu_page: array{webspaces: array<string, array{settingsPermissions?: array<string, bool>, _permissions: array<string, bool>}>}} $config */
        $config = \json_decode((string) $client->getResponse()->getContent(), true);
        $webspaces = $config['sulu_page']['webspaces'];

        $this->assertTrue($webspaces['sulu-io']['settingsPermissions']['view'] ?? null);
        $this->assertFalse($webspaces['sulu-io']['settingsPermissions']['edit'] ?? null, 'the permissions of the pages do not leak into the settings');
        $this->assertTrue($webspaces['sulu-io']['_permissions']['edit']);
        $this->assertArrayNotHasKey('settingsPermissions', $webspaces['blog'], 'a webspace without settings form has no settings permissions');
    }

    public function testGetWithoutPermissionIsForbidden(): void
    {
        self::purgeDatabase();
        self::createWebspaceSetting('sulu-io', ['en' => ['draft' => ['companyName' => 'Sulu GmbH']]]);
        $this->createUserWithPermissions('otheruser', ['sulu.webspaces.sulu-io' => self::VIEW_EDIT_LIVE]);
        self::ensureKernelShutdown();

        $client = $this->createClientForUser('otheruser');
        $client->request('GET', '/admin/api/webspace-settings/sulu-io?locale=en');

        $this->assertHttpStatusCode(403, $client->getResponse());
    }

    public function testGetIsGuardedByTheWebspaceOfTheSettings(): void
    {
        self::purgeDatabase();
        self::createWebspaceSetting('sulu-io', ['en' => ['draft' => ['companyName' => 'Sulu GmbH']]]);
        $this->createUserWithPermissions('bloguser', [WebspaceSettingAdmin::getSecurityContext('blog') => self::VIEW_EDIT_LIVE]);
        self::ensureKernelShutdown();

        $client = $this->createClientForUser('bloguser');
        $client->request('GET', '/admin/api/webspace-settings/sulu-io?locale=en');

        $this->assertHttpStatusCode(403, $client->getResponse());
    }

    public function testPutWithoutEditPermissionIsForbidden(): void
    {
        self::purgeDatabase();
        $this->createUserWithPermissions('viewer', [WebspaceSettingAdmin::getSecurityContext('sulu-io') => 64]);
        self::ensureKernelShutdown();

        $client = $this->createClientForUser('viewer');
        $client->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en', [], [], [], (string) \json_encode(['companyName' => 'Sulu']));

        $this->assertHttpStatusCode(403, $client->getResponse());
    }

    public function testPutPublishWithoutLivePermissionIsForbidden(): void
    {
        self::purgeDatabase();
        $this->createUserWithPermissions('editor', [WebspaceSettingAdmin::getSecurityContext('sulu-io') => self::VIEW_EDIT]);
        self::ensureKernelShutdown();

        $client = $this->createClientForUser('editor');
        $client->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en&action=publish', [], [], [], (string) \json_encode(['companyName' => 'Sulu']));

        $this->assertHttpStatusCode(403, $client->getResponse());

        $client->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en', [], [], [], (string) \json_encode(['companyName' => 'Sulu']));

        $this->assertHttpStatusCode(200, $client->getResponse());
    }

    public function testUnpublishWithoutLivePermissionIsForbidden(): void
    {
        self::purgeDatabase();
        self::createWebspaceSetting('sulu-io', ['en' => ['live' => ['companyName' => 'Sulu GmbH']]]);
        $this->createUserWithPermissions('editor', [WebspaceSettingAdmin::getSecurityContext('sulu-io') => self::VIEW_EDIT]);
        self::ensureKernelShutdown();

        $client = $this->createClientForUser('editor');
        $client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=unpublish');

        $this->assertHttpStatusCode(403, $client->getResponse());
    }

    public function testPublishWithLivePermissionIsAllowed(): void
    {
        self::purgeDatabase();
        self::createWebspaceSetting('sulu-io', ['en' => ['draft' => ['companyName' => 'Sulu GmbH']]]);
        $this->createUserWithPermissions('publisher', [WebspaceSettingAdmin::getSecurityContext('sulu-io') => self::VIEW_EDIT_LIVE]);
        self::ensureKernelShutdown();

        $client = $this->createClientForUser('publisher');
        $client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=publish');

        $this->assertHttpStatusCode(200, $client->getResponse());
        /** @var array{workflowPlace: string} $content */
        $content = \json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('published', $content['workflowPlace']);
    }

    /**
     * @param array<string, int> $permissionsByContext
     */
    private function createUserWithPermissions(string $username, array $permissionsByContext): void
    {
        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName('Limited Role');
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        foreach ($permissionsByContext as $context => $permissions) {
            $permission = new Permission();
            $permission->setRole($role);
            $permission->setPermissions($permissions);
            $permission->setContext($context);
            $entityManager->persist($permission);
        }

        $contact = new Contact();
        $contact->setFirstName('Limited');
        $contact->setLastName('User');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername($username);
        $user->setSalt('');
        $user->setLocale('en');
        $user->setEmail($username . '@test.com');
        $user->setContact($contact);
        $user->setPassword(self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user)->hash('test'));
        $entityManager->persist($user);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale((string) \json_encode(['en']));
        $user->addUserRole($userRole);
        $entityManager->persist($userRole);

        $entityManager->flush();
        $entityManager->clear();
    }

    private function createClientForUser(string $username): KernelBrowser
    {
        return $this->createAuthenticatedClient(
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => 'test',
            ],
        );
    }
}
