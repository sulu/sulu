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
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Tests\Traits\CreatePageWithPermissionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class PageControllerPermissionsTest extends SuluTestCase
{
    use AssertSnapshotTrait;
    use CreatePageWithPermissionsTrait;

    /**
     * @var KernelBrowser
     */
    protected $client;

    protected function setUp(): void
    {
    }

    private function createHomepage(string $uuid, string $webspaceKey): PageInterface
    {
        $homepage = new Page($uuid);
        $homepage->setLft(0);
        $homepage->setRgt(1);
        $homepage->setDepth(0);
        $homepage->setWebspaceKey($webspaceKey);
        self::getEntityManager()->persist($homepage);
        self::getEntityManager()->flush();

        return $homepage;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createPage(
        string $parentId,
        array $data = [],
        string $webspaceKey = 'sulu-io',
    ): PageInterface {
        $data = \array_merge(
            [
                'title' => 'Test Page',
                'locale' => 'en',
                'url' => '/test-page-' . \uniqid(),
                'template' => 'default',
            ],
            $data,
        );
        $message = new CreatePageMessage($webspaceKey, $parentId, $data);

        /** @var CreatePageMessageHandler $messageHandler */
        $messageHandler = self::getContainer()->get('sulu_page.create_page_handler');
        $page = $messageHandler->__invoke($message);
        self::getEntityManager()->flush();

        return $page;
    }

    public function testCgetActionWithoutWebspacePermissionsReturnsEmpty(): void
    {
        self::purgeDatabase();

        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName('Limited Role');
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions(127);
        $permission->setContext('sulu.webspaces.sulu-io');
        $entityManager->persist($permission);

        $contact = new Contact();
        $contact->setFirstName('Limited');
        $contact->setLastName('User');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername('limiteduser');
        $user->setSalt('');
        $user->setLocale('en');
        $user->setEmail('limiteduser@test.com');
        $user->setContact($contact);

        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $hasher = $passwordHasherFactory->getPasswordHasher($user);
        $user->setPassword($hasher->hash('test'));

        $entityManager->persist($user);

        // Link user to role
        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale((string) \json_encode(['en']));
        $user->addUserRole($userRole);
        $entityManager->persist($userRole);

        $entityManager->flush();
        $entityManager->clear();

        self::ensureKernelShutdown();

        $homepage = $this->createHomepage('sulu-io-test-permissions-uuid', 'sulu-io');
        $this->createPage($homepage->getId(), [
            'title' => 'Sulu-io Page',
            'template' => 'default',
            'url' => '/sulu-io-test-no-permission',
        ]);
        $this->createPage($homepage->getId(), [
            'title' => 'Sulu-io Page 2',
            'template' => 'default',
            'url' => '/sulu-io-test-no-permission2',
        ]);
        $homepage2 = $this->createHomepage('blog-test-permissions-uuid', 'blog');
        $this->createPage(
            $homepage2->getId(),
            [
                'title' => 'Blog Page',
                'template' => 'default',
                'url' => '/blog-test-no-permission',
            ],
            'blog',
        );

        self::ensureKernelShutdown();

        $limitedClient = $this->createAuthenticatedClient(
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'PHP_AUTH_USER' => 'limiteduser',
                'PHP_AUTH_PW' => 'test',
            ],
        );

        $limitedClient->request(
            'GET',
            '/admin/api/pages?locale=en',
        );

        $response = $limitedClient->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded?: array{pages?: list<array<string, mixed>>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pagesSuluIo = $content['_embedded']['pages'] ?? [];

        $this->assertNotEmpty($pagesSuluIo, 'User with sulu-io permission should see sulu-io pages');
        $this->assertCount(1, $pagesSuluIo);

        $limitedClient->request(
            'GET',
            '/admin/api/pages?locale=en&webspace=blog',
        );

        $response = $limitedClient->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded?: array{pages?: list<array<string, mixed>>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pagesBlog = $content['_embedded']['pages'] ?? [];

        $this->assertEmpty($pagesBlog, 'User without blog permission should not see blog pages');
    }
}
