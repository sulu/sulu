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

namespace Sulu\Snippet\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The admin submits every toolbar action as one request carrying the whole form plus an action, and
 * the controller persists before applying the action. These tests pin both halves of that: content
 * saves are refused while a review is open, and the transitions that end a review still get through.
 */
#[CoversNothing]
class SnippetInReviewTest extends SuluTestCase
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

    public function testDraftSaveIsRejectedWhileInReview(): void
    {
        $id = $this->createSnippetInReview();

        $this->put($id, ['action' => 'draft'], 'Edited While In Review');

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode(), (string) $response->getContent());

        /** @var array{detail?: string} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertStringContainsString('in review', (string) ($content['detail'] ?? ''));

        $this->client->request('GET', \sprintf('/admin/api/snippets/%s?locale=en', $id));

        /** @var array{title: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Snippet In Review', $content['title']);
    }

    public function testCancelReviewIsAllowedAndClearsTheLock(): void
    {
        $id = $this->createSnippetInReview();

        $this->trigger($id, 'cancel_review');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{workflowPlace: string, _locked: bool, activeWorkflowTransitionRequest: mixed, title: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('unpublished', $content['workflowPlace']);
        $this->assertFalse($content['_locked']);
        $this->assertNull($content['activeWorkflowTransitionRequest']);

        $this->assertSame('Snippet In Review', $content['title']);
    }

    public function testPublishWithoutReviewSucceedsWithLivePermission(): void
    {
        $id = $this->createSnippetInReview();

        $this->trigger($id, 'publish');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{workflowPlace: string, _locked: bool, activeWorkflowTransitionRequest: mixed} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('published', $content['workflowPlace']);
        $this->assertFalse($content['_locked']);
        $this->assertNull($content['activeWorkflowTransitionRequest']);

        $snippet = self::getContainer()->get(SnippetRepositoryInterface::class)->getOneBy(['uuid' => $id]);
        $liveContent = self::getContainer()->get(ContentManagerInterface::class)->resolve(
            $snippet,
            ['stage' => DimensionContentInterface::STAGE_LIVE, 'locale' => 'en'],
        );
        $this->assertSame('Snippet In Review', $liveContent->getTemplateData()['title'] ?? null);
    }

    public function testPublishWithoutReviewIsForbiddenWithoutLivePermission(): void
    {
        $id = $this->createSnippetInReview();
        $this->createUserWithoutLivePermission();

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
            'POST',
            \sprintf('/admin/api/snippets/%s?%s', $id, \http_build_query(['action' => 'publish', 'locale' => 'en'])),
        );

        $response = $limitedClient->getResponse();
        $this->assertHttpStatusCode(403, $response);

        /** @var array{message?: string} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertStringContainsString('"live" permission', (string) ($content['message'] ?? ''));
    }

    public function testCopyLocaleIntoReviewedLocaleIsRejected(): void
    {
        $id = $this->createSnippetInReview();

        $this->put($id, ['action' => 'draft', 'locale' => 'de'], 'Schnipsel In Review');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/snippets/%s?%s', $id, \http_build_query([
                'action' => 'copy_locale',
                'locale' => 'en',
                'src' => 'de',
                'dest' => 'en',
            ])),
        );

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode(), (string) $response->getContent());

        $this->client->request('GET', \sprintf('/admin/api/snippets/%s?locale=en', $id));

        /** @var array{title: string, _locked: bool, activeWorkflowTransitionRequest: mixed} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Snippet In Review', $content['title']);
        $this->assertTrue($content['_locked']);
        $this->assertNotNull($content['activeWorkflowTransitionRequest']);
    }

    public function testRestoreIntoReviewedLocaleIsRejected(): void
    {
        // A version only exists once the content was published, and a workflow only lets a bypass do that.
        $id = $this->createSnippetInReview();

        $this->trigger($id, 'publish');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->put($id, ['action' => 'draft'], 'Draft Awaiting Review');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('POST', \sprintf('/admin/api/snippets/%s?locale=en&action=request_for_review_draft', $id));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/snippets/%s?locale=en&action=restore&version=%s', $id, $this->latestVersion($id)),
        );

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode(), (string) $response->getContent());

        $this->client->request('GET', \sprintf('/admin/api/snippets/%s?locale=en', $id));

        /** @var array{title: string, _locked: bool, activeWorkflowTransitionRequest: mixed} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Draft Awaiting Review', $content['title']);
        $this->assertTrue($content['_locked']);
        $this->assertNotNull($content['activeWorkflowTransitionRequest']);
    }

    private function latestVersion(string $id): int
    {
        $this->client->request(
            'GET',
            \sprintf('/admin/api/snippets/%s/versions?page=1&locale=en&fields=title,version,changer,id', $id),
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{_embedded: array{snippets_versions: array<int, array{version: int}>}} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $version = $content['_embedded']['snippets_versions'][0]['version'] ?? null;
        $this->assertNotEmpty($version, 'Publishing has to leave a version behind for the restore.');

        return $version;
    }

    private function trigger(string $id, string $action): void
    {
        $this->client->request(
            'POST',
            \sprintf('/admin/api/snippets/%s?%s', $id, \http_build_query(['action' => $action, 'locale' => 'en'])),
        );
    }

    /**
     * @param array<string, string> $query
     */
    private function put(string $id, array $query, string $title): void
    {
        $this->client->request(
            'PUT',
            \sprintf('/admin/api/snippets/%s?%s', $id, \http_build_query($query + ['locale' => 'en'])),
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'review',
                'title' => $title,
            ]),
        );
    }

    private function createSnippet(): string
    {
        $this->client->request(
            'POST',
            '/admin/api/snippets?locale=en',
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'review',
                'title' => 'Snippet In Review',
            ]),
        );
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);

        return $content['id'];
    }

    private function createSnippetInReview(): string
    {
        $id = $this->createSnippet();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/snippets/%s?locale=en&action=request_for_review', $id),
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{workflowPlace: string, _locked: bool} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('review', $content['workflowPlace']);
        $this->assertTrue($content['_locked'], 'A workflow must be configured for snippets in this kernel.');

        return $id;
    }

    private function createUserWithoutLivePermission(): void
    {
        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName('Reviewer Without Live');
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions(253); // everything except `live`
        $permission->setContext(SnippetAdmin::SECURITY_CONTEXT);
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
