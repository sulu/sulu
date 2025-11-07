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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Security;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[RunTestsInSeparateProcesses]
class PageSecurityListenerTest extends SuluTestCase
{
    use CreatePageTrait;

    private KernelBrowser $client;

    private Role $anonymousRole;

    protected function setUp(): void
    {
        $this->client = $this->createWebsiteClient(['environment' => 'test_with_security']);
        $this->purgeDatabase();

        $this->anonymousRole = $this->createAnonymousRoleWithWebspacePermissions();
    }

    public function testHomepageAccessibleWithoutPermissions(): void
    {
        self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Homepage',
                    'url' => '/',
                    'template' => 'homepage',
                ],
            ],
        ], 'sulu-test-secure');

        self::getEntityManager()->clear();

        $this->client->request('GET', 'http://sulu-secure.io/en');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testSecurePageDeniedForAnonymousUser(): void
    {
        $homepage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Homepage',
                    'url' => '/',
                    'template' => 'homepage',
                ],
            ],
        ], 'sulu-test-secure');
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Secure Area',
                    'url' => '/secure-area',
                    'template' => 'default',
                    'article' => '<p>Super secret content</p>',
                    'parentId' => $homepage->getId(),
                ],
            ],
        ], 'sulu-test-secure');

        $this->denyAccessToPage($page);
        self::getEntityManager()->clear();

        $this->client->request('GET', 'http://sulu-secure.io/en/secure-area');

        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    private function createAnonymousRoleWithWebspacePermissions(): Role
    {
        $role = new Role();
        $role->setName('Anonymous User Website');
        $role->setSystem('sulu-test-secure');
        $role->setAnonymous(true);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions(127);
        $permission->setContext('sulu.webspaces.sulu-test-secure');
        $role->addPermission($permission);

        self::getEntityManager()->persist($role);
        self::getEntityManager()->persist($permission);
        self::getEntityManager()->flush();

        return $role;
    }

    private function denyAccessToPage(Page $page): void
    {
        $accessControl = new AccessControl();
        $accessControl->setPermissions(0);
        $accessControl->setEntityId($page->getUuid());
        $accessControl->setEntityClass(Page::class);
        $accessControl->setRole($this->anonymousRole);

        self::getEntityManager()->persist($accessControl);
        self::getEntityManager()->flush();
    }
}
