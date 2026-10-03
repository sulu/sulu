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

namespace Sulu\Article\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * An article of a group other than the default one is secured by the context of that group, so a
 * role holding permissions on that context alone must be able to work on it.
 */
#[CoversNothing]
class ArticleGroupPermissionTest extends SuluTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();
    }

    public function testPublishIsAllowedWithLivePermissionOnTheGroupContext(): void
    {
        $id = $this->createArticle('blog');
        // Everything except `live` on the base context, which the request based check still asks for.
        $this->createUserWithPermissions([
            ArticleAdmin::SECURITY_CONTEXT => 253,
            ArticleAdmin::getArticleSecurityContext('blog-group') => 255,
        ]);

        $client = $this->publish($id);

        $this->assertHttpStatusCode(200, $client->getResponse());
    }

    public function testPublishIsForbiddenWithoutLivePermissionOnTheGroupContext(): void
    {
        $id = $this->createArticle('blog');
        $this->createUserWithPermissions([
            ArticleAdmin::SECURITY_CONTEXT => 255,
            ArticleAdmin::getArticleSecurityContext('blog-group') => 253,
        ]);

        $client = $this->publish($id);

        $this->assertHttpStatusCode(403, $client->getResponse());

        /** @var array{message?: string} $content */
        $content = \json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            \sprintf('Publishing "%s"', ArticleAdmin::getArticleSecurityContext('blog-group')),
            (string) ($content['message'] ?? ''),
        );
    }

    public function testPublishOfADefaultGroupArticleKeepsTheBaseContext(): void
    {
        $id = $this->createArticle('article');
        $this->createUserWithPermissions([
            ArticleAdmin::SECURITY_CONTEXT => 255,
            ArticleAdmin::getArticleSecurityContext('blog-group') => 253,
        ]);

        $client = $this->publish($id);

        $this->assertHttpStatusCode(200, $client->getResponse());
    }

    private function publish(string $id): KernelBrowser
    {
        $client = $this->createGroupUserClient();
        $client->request(
            'POST',
            \sprintf('/admin/api/articles/%s?%s', $id, \http_build_query(['action' => 'publish', 'locale' => 'en'])),
        );

        return $client;
    }

    private function createGroupUserClient(): KernelBrowser
    {
        self::ensureKernelShutdown();

        return $this->createAuthenticatedClient(
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'PHP_AUTH_USER' => 'groupuser',
                'PHP_AUTH_PW' => 'test',
            ],
        );
    }

    private function createArticle(string $template): string
    {
        $this->client->request(
            'POST',
            '/admin/api/articles?locale=en',
            [],
            [],
            [],
            (string) \json_encode([
                'template' => $template,
                'title' => 'Article',
                'url' => '/article-' . $template,
                'mainWebspace' => 'sulu-io',
            ]),
        );
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);

        return $content['id'];
    }

    /**
     * @param array<string, int> $permissionsByContext
     */
    private function createUserWithPermissions(array $permissionsByContext): void
    {
        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName('Blog Editor');
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        foreach ($permissionsByContext as $securityContext => $permissions) {
            $permission = new Permission();
            $permission->setRole($role);
            $permission->setPermissions($permissions);
            $permission->setContext($securityContext);
            $entityManager->persist($permission);
        }

        $contact = new Contact();
        $contact->setFirstName('Group');
        $contact->setLastName('User');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername('groupuser');
        $user->setSalt('');
        $user->setLocale('en');
        $user->setEmail('groupuser@test.com');
        $user->setContact($contact);

        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $user->setPassword($passwordHasherFactory->getPasswordHasher($user)->hash('test'));
        $entityManager->persist($user);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale((string) \json_encode(['en']));
        $user->addUserRole($userRole);
        $entityManager->persist($userRole);

        $entityManager->flush();
    }
}
