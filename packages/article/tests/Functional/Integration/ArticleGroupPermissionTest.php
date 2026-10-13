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
use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * A group article is secured by the context of its group alone, a default group article by the base context.
 */
#[CoversNothing]
class ArticleGroupPermissionTest extends SuluTestCase
{
    private const VIEW = 64;
    private const LIVE = 2;
    private const ALL = 255;
    private const ALL_BUT_LIVE = self::ALL - self::LIVE;

    private const BASE = ArticleAdmin::SECURITY_CONTEXT;
    private const GROUP = ArticleAdmin::SECURITY_CONTEXT . '_blog-group';

    protected function setUp(): void
    {
        $this->createAuthenticatedClient();
        self::purgeDatabase();
    }

    /**
     * @return iterable<string, array{string, string, array<string, mixed>|null}>
     */
    public static function provideRoutes(): iterable
    {
        yield 'cgetAction' => ['GET', '/admin/api/articles?locale=en', null];
        yield 'getAction' => ['GET', '/admin/api/articles/{id}?locale=en', null];
        yield 'getVersionsAction' => ['GET', '/admin/api/articles/{id}/versions?locale=en', null];
        yield 'postAction' => ['POST', '/admin/api/articles?locale=en', ['template' => 'blog', 'title' => 'New']];
        yield 'putAction' => ['PUT', '/admin/api/articles/{id}?locale=en', ['title' => 'Changed']];
        yield 'deleteAction' => ['DELETE', '/admin/api/articles/{id}?locale=en', null];
        yield 'postTriggerAction' => ['POST', '/admin/api/articles/{id}?locale=en&action=publish', null];
    }

    /**
     * @param array<string, mixed>|null $body
     */
    #[DataProvider('provideRoutes')]
    public function testEveryRouteIsForbiddenWithoutPermission(string $method, string $uri, ?array $body): void
    {
        $id = $this->createArticle('blog');
        $this->createUser('nobody', [self::BASE => 0, self::GROUP => 0]);

        $client = $this->request('nobody', $method, \str_replace('{id}', $id, $uri), $body);

        $this->assertHttpStatusCode(403, $client->getResponse());
    }

    public function testEveryRouteIsCoveredByTheRouteTest(): void
    {
        $actions = [];
        foreach (self::getContainer()->get('router')->getRouteCollection() as $route) {
            $controller = $route->getDefault('_controller');
            if (\is_string($controller) && \str_starts_with($controller, 'sulu_article.admin_article_controller::')) {
                $actions[] = \substr($controller, \strlen('sulu_article.admin_article_controller::'));
            }
        }
        \sort($actions);

        $covered = \array_keys(\iterator_to_array(self::provideRoutes()));
        \sort($covered);

        $this->assertSame($covered, $actions, 'A new route of the article controller needs its own permission check and a case in provideRoutes().');
    }

    public function testGroupOnlyRoleWorksOnAGroupArticle(): void
    {
        $this->createUser('blogger', [self::GROUP => self::ALL]);

        $client = $this->request('blogger', 'POST', '/admin/api/articles?locale=en', $this->data('blog', 'Blog'));
        $this->assertHttpStatusCode(201, $client->getResponse());
        $id = $this->getId($client);

        $client = $this->request('blogger', 'PUT', '/admin/api/articles/' . $id . '?locale=en', $this->data('blog', 'Changed'));
        $this->assertHttpStatusCode(200, $client->getResponse());

        foreach (['publish', 'unpublish'] as $action) {
            $client = $this->request('blogger', 'POST', '/admin/api/articles/' . $id . '?locale=en&action=' . $action);
            $this->assertStatusCode(200, $client, $action);
        }

        $client = $this->request('blogger', 'GET', '/admin/api/articles/' . $id . '?locale=en');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $client = $this->request('blogger', 'DELETE', '/admin/api/articles/' . $id . '?locale=en');
        $this->assertHttpStatusCode(204, $client->getResponse());
    }

    public function testGroupOnlyRoleCannotWorkOnADefaultGroupArticle(): void
    {
        $id = $this->createArticle('article');
        $this->createUser('blogger', [self::GROUP => self::ALL]);

        foreach ([
            ['GET', '/admin/api/articles/' . $id . '?locale=en', null],
            ['PUT', '/admin/api/articles/' . $id . '?locale=en', $this->data('article', 'Changed')],
            ['POST', '/admin/api/articles/' . $id . '?locale=en&action=publish', null],
            ['POST', '/admin/api/articles?locale=en', $this->data('article', 'New')],
        ] as [$method, $uri, $body]) {
            $client = $this->request('blogger', $method, $uri, $body);
            $this->assertStatusCode(403, $client, $method . ' ' . $uri);
        }
    }

    public function testPublishNeedsLiveOnTheGroupContext(): void
    {
        $id = $this->createArticle('blog');
        $this->createUser('editor', [self::BASE => self::ALL, self::GROUP => self::ALL_BUT_LIVE]);

        $client = $this->request('editor', 'POST', '/admin/api/articles/' . $id . '?locale=en&action=publish');

        $this->assertHttpStatusCode(403, $client->getResponse());
        /** @var array{message?: string} $content */
        $content = \json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertStringContainsString(\sprintf('Publishing "%s"', self::GROUP), (string) ($content['message'] ?? ''));
    }

    public function testPublishOfADefaultGroupArticleNeedsTheBaseContext(): void
    {
        $id = $this->createArticle('article');
        $this->createUser('editor', [self::BASE => self::ALL, self::GROUP => self::ALL_BUT_LIVE]);

        $client = $this->request('editor', 'POST', '/admin/api/articles/' . $id . '?locale=en&action=publish');

        $this->assertHttpStatusCode(200, $client->getResponse());
    }

    #[DataProvider('provideLiveActions')]
    public function testLiveActionsNeedLiveOnTheGroupContext(string $action): void
    {
        $id = $this->createArticle('blog', true);
        $this->createUser('editor', [self::BASE => self::ALL, self::GROUP => self::ALL_BUT_LIVE]);

        $client = $this->request('editor', 'POST', '/admin/api/articles/' . $id . '?locale=en&action=' . $action);

        $this->assertHttpStatusCode(403, $client->getResponse());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideLiveActions(): iterable
    {
        yield 'unpublish' => ['unpublish'];
        yield 'remove_draft' => ['remove_draft'];
    }

    public function testMovingAGroupArticleOutOfItsGroupAndPublishingNeedsLiveOnBothGroups(): void
    {
        $id = $this->createArticle('blog');
        $this->createUser('editor', [self::BASE => self::ALL, self::GROUP => self::ALL_BUT_LIVE]);

        $client = $this->request('editor', 'PUT', '/admin/api/articles/' . $id . '?locale=en&action=publish', $this->data('article', 'Moved'));

        $this->assertHttpStatusCode(403, $client->getResponse());
        $article = $this->getArticle($id);
        $this->assertSame('blog', $article['template']);
        $this->assertFalse($article['publishedState']);
    }

    public function testMovingADefaultArticleIntoAGroupAndPublishingNeedsLiveOnBothGroups(): void
    {
        $id = $this->createArticle('article');
        $this->createUser('editor', [self::BASE => self::ALL_BUT_LIVE, self::GROUP => self::ALL]);

        $client = $this->request('editor', 'PUT', '/admin/api/articles/' . $id . '?locale=en&action=publish', $this->data('blog', 'Moved'));

        $this->assertHttpStatusCode(403, $client->getResponse());
        $article = $this->getArticle($id);
        $this->assertSame('article', $article['template']);
        $this->assertFalse($article['publishedState']);
    }

    public function testMovingAnArticleNeedsEditOnBothGroups(): void
    {
        $id = $this->createArticle('blog');
        $this->createUser('blogger', [self::GROUP => self::ALL]);

        $client = $this->request('blogger', 'PUT', '/admin/api/articles/' . $id . '?locale=en', $this->data('article', 'Moved'));

        $this->assertHttpStatusCode(403, $client->getResponse());
        $this->assertSame('blog', $this->getArticle($id)['template']);
    }

    public function testListContainsTheViewableGroupsOnly(): void
    {
        $this->createArticle('blog', false, 'Blog article');
        $this->createArticle('article', false, 'Default article');
        $this->createUser('blogger', [self::GROUP => self::VIEW], ['en', 'de']);
        $this->createUser('writer', [self::BASE => self::VIEW], ['en', 'de']);
        $this->createUser('reader', [self::BASE => self::VIEW, self::GROUP => self::VIEW], ['en', 'de']);

        foreach (['en', 'de'] as $locale) {
            // in de the articles are ghosts of en
            $this->assertSame(['Blog article'], $this->listTitles('blogger', $locale), $locale);
            $this->assertSame(['Default article'], $this->listTitles('writer', $locale), $locale);
            $this->assertSame(['Blog article', 'Default article'], $this->listTitles('reader', $locale), $locale);
        }
    }

    public function testANewLocaleWithoutTemplateNeedsThePermissionOfTheArticleGroup(): void
    {
        $id = $this->createArticle('blog');
        $this->createUser('writer', [self::BASE => self::ALL], ['en', 'de']);

        $client = $this->request('writer', 'PUT', '/admin/api/articles/' . $id . '?locale=de', ['title' => 'Artikel']);

        $this->assertStatusCode(403, $client, 'the article is in the blog group in en');
    }

    public function testCopyLocaleNeedsEditPermissionOfTheDestinationLocale(): void
    {
        $id = $this->createArticle('blog');
        $this->createUser('blogger', [self::GROUP => self::ALL], ['en']);

        $client = $this->request('blogger', 'POST', '/admin/api/articles/' . $id . '?locale=en&action=copy_locale&src=en&dest=de');

        $this->assertStatusCode(403, $client, 'no permission in de');
    }

    #[DataProvider('provideCopyActions')]
    public function testCopyNeedsThePermissionOfTheCopiedLocales(string $method, string $query): void
    {
        $id = $this->createArticle('blog');
        $client = $this->request(null, 'PUT', '/admin/api/articles/' . $id . '?locale=de', $this->data('article', 'Artikel'));
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->createUser('writer', [self::BASE => self::ALL], ['en', 'de', 'fr']);

        $client = $this->request('writer', $method, '/admin/api/articles/' . $id . '?locale=de&' . $query, 'PUT' === $method ? $this->data('article', 'Artikel') : null);

        $this->assertStatusCode(403, $client, 'en is in the blog group');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideCopyActions(): iterable
    {
        foreach (['POST', 'PUT'] as $method) {
            yield $method . ' copy_locale' => [$method, 'action=copy_locale&src=en&dest=fr'];
            yield $method . ' copy' => [$method, 'action=copy'];
        }
    }

    #[DataProvider('provideMethods')]
    public function testRestoringAVersionOfAnotherGroupNeedsEditOnThatGroup(string $method): void
    {
        $id = $this->createArticle('blog', true);
        $version = $this->latestVersion($id);
        $client = $this->request(null, 'PUT', '/admin/api/articles/' . $id . '?locale=en', $this->data('article', 'Moved'));
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->createUser('writer', [self::BASE => self::ALL]);

        $client = $this->request('writer', $method, '/admin/api/articles/' . $id . '?locale=en&action=restore&version=' . $version, 'PUT' === $method ? $this->data('article', 'Moved') : null);

        $this->assertStatusCode(403, $client, 'the version is in the blog group');
        $this->assertSame('article', $this->getArticle($id)['template']);
    }

    #[DataProvider('provideMethods')]
    public function testRestoringAVersionOfAnotherGroupWorksWithEditOnBothGroups(string $method): void
    {
        $id = $this->createArticle('blog', true);
        $version = $this->latestVersion($id);
        $client = $this->request(null, 'PUT', '/admin/api/articles/' . $id . '?locale=en', $this->data('article', 'Moved'));
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->createUser('editor', [self::BASE => self::ALL, self::GROUP => self::ALL]);

        $client = $this->request('editor', $method, '/admin/api/articles/' . $id . '?locale=en&action=restore&version=' . $version, 'PUT' === $method ? $this->data('article', 'Moved') : null);

        $this->assertStatusCode(200, $client, 'edit on both groups');
        $this->assertSame('blog', $this->getArticle($id)['template']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideMethods(): iterable
    {
        yield 'POST' => ['POST'];
        yield 'PUT' => ['PUT'];
    }

    public function testDeleteNeedsThePermissionOfEveryLocale(): void
    {
        $id = $this->createArticle('blog');
        $client = $this->request(null, 'PUT', '/admin/api/articles/' . $id . '?locale=de', $this->data('article', 'Artikel'));
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->createUser('blogger', [self::GROUP => self::ALL], ['en', 'de']);

        $client = $this->request('blogger', 'DELETE', '/admin/api/articles/' . $id . '?locale=en');
        $this->assertStatusCode(403, $client, 'all locales, one of them in the default group');

        $client = $this->request('blogger', 'DELETE', '/admin/api/articles/' . $id . '?locale=en&deleteLocale=true');
        $this->assertStatusCode(204, $client, 'only the locale in the group');
    }

    private function assertStatusCode(int $expected, KernelBrowser $client, string $message): void
    {
        $this->assertSame($expected, $client->getResponse()->getStatusCode(), $message);
    }

    /**
     * @return list<string>
     */
    private function listTitles(string $username, string $locale): array
    {
        $client = $this->request($username, 'GET', '/admin/api/articles?locale=' . $locale);
        $this->assertHttpStatusCode(200, $client->getResponse());

        /** @var array{_embedded: array{articles: array<array{title: string}>}} $content */
        $content = \json_decode((string) $client->getResponse()->getContent(), true);
        $titles = \array_column($content['_embedded']['articles'], 'title');
        \sort($titles);

        return $titles;
    }

    private function latestVersion(string $id): int
    {
        $client = $this->request(null, 'GET', '/admin/api/articles/' . $id . '/versions?page=1&locale=en&fields=title,version,changer,id');
        $this->assertHttpStatusCode(200, $client->getResponse());

        /** @var array{_embedded: array{articles_versions: array<int, array{version: int}>}} $content */
        $content = \json_decode((string) $client->getResponse()->getContent(), true);
        $version = $content['_embedded']['articles_versions'][0]['version'] ?? null;
        $this->assertNotEmpty($version, 'Publishing has to leave a version behind for the restore.');

        return $version;
    }

    /**
     * @return array<string, mixed>
     */
    private function getArticle(string $id): array
    {
        $client = $this->request(null, 'GET', '/admin/api/articles/' . $id . '?locale=en');

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $client->getResponse()->getContent(), true);

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    private function data(string $template, string $title): array
    {
        return [
            'template' => $template,
            'title' => $title,
            'url' => '/' . \strtolower(\str_replace(' ', '-', $title)),
            'mainWebspace' => 'sulu-io',
        ];
    }

    private function createArticle(string $template, bool $publish = false, string $title = 'Article'): string
    {
        $client = $this->request(null, 'POST', '/admin/api/articles?locale=en' . ($publish ? '&action=publish' : ''), $this->data($template, $title));
        $this->assertHttpStatusCode(201, $client->getResponse());

        return $this->getId($client);
    }

    private function getId(KernelBrowser $client): string
    {
        /** @var array{id: string} $content */
        $content = \json_decode((string) $client->getResponse()->getContent(), true);

        return $content['id'];
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function request(?string $username, string $method, string $uri, ?array $body = null): KernelBrowser
    {
        self::ensureKernelShutdown();

        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if (null !== $username) {
            $server += ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => 'test'];
        }

        $client = $this->createAuthenticatedClient([], $server);
        $client->request($method, $uri, [], [], [], null !== $body ? (string) \json_encode($body) : null);

        return $client;
    }

    /**
     * @param array<string, int> $permissionsByContext
     * @param list<string> $locales
     */
    private function createUser(string $username, array $permissionsByContext, array $locales = ['en']): void
    {
        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName($username);
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
        $contact->setFirstName($username);
        $contact->setLastName('User');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername($username);
        $user->setSalt('');
        $user->setLocale('en');
        $user->setEmail($username . '@test.com');
        $user->setContact($contact);

        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $user->setPassword($passwordHasherFactory->getPasswordHasher($user)->hash('test'));
        $entityManager->persist($user);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale((string) \json_encode($locales));
        $user->addUserRole($userRole);
        $entityManager->persist($userRole);

        $entityManager->flush();
    }
}
