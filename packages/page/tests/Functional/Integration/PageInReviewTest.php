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
use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Bundle\PreviewBundle\Preview\Provider\CachablePreviewDefaultsProviderInterface;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Exception\ContentInReviewException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * The admin submits every toolbar action as one request carrying the whole form plus an action, and
 * the controller persists before applying the action. These tests pin both halves of that: content
 * saves are refused while a review is open, and the transitions that end a review still get through.
 */
#[CoversNothing]
class PageInReviewTest extends SuluTestCase
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
        $id = $this->createPageInReview();

        $this->put($id, ['action' => 'draft'], 'Edited While In Review');

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode(), (string) $response->getContent());

        /** @var array{detail?: string} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertStringContainsString('in review', (string) ($content['detail'] ?? ''));
    }

    public function testCancelReviewIsAllowedAndClearsTheLock(): void
    {
        $id = $this->createPageInReview();

        $this->trigger($id, 'cancel_review');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{workflowPlace: string, _locked: bool, activeWorkflowTransitionRequest: mixed, title: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('unpublished', $content['workflowPlace']);
        $this->assertFalse($content['_locked']);
        $this->assertNull($content['activeWorkflowTransitionRequest']);

        $this->assertSame('Page In Review', $content['title']);
    }

    public function testDraftSaveIsAllowedAgainAfterCancel(): void
    {
        $id = $this->createPageInReview();

        $this->trigger($id, 'cancel_review');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->put($id, ['action' => 'draft'], 'Edited After Cancel');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{title: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Edited After Cancel', $content['title']);
    }

    public function testPublishWithoutReviewSucceedsWithLivePermission(): void
    {
        $id = $this->createPageInReview();

        $this->trigger($id, 'publish');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{workflowPlace: string, _locked: bool, activeWorkflowTransitionRequest: mixed} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('published', $content['workflowPlace']);
        $this->assertFalse($content['_locked']);
        $this->assertNull($content['activeWorkflowTransitionRequest']);

        $page = self::getContainer()->get(PageRepositoryInterface::class)->getOneBy(['uuid' => $id]);
        $liveContent = self::getContainer()->get(ContentManagerInterface::class)->resolve(
            $page,
            ['stage' => DimensionContentInterface::STAGE_LIVE, 'locale' => 'en'],
        );
        $this->assertSame('Page In Review', $liveContent->getTemplateData()['title'] ?? null);
    }

    public function testPublishWithoutReviewIsForbiddenWithoutLivePermission(): void
    {
        $id = $this->createPageInReview();
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
            \sprintf(
                '/admin/api/pages/%s?%s',
                $id,
                \http_build_query(['action' => 'publish', 'locale' => 'en', 'webspace' => 'sulu-io']),
            ),
        );

        $response = $limitedClient->getResponse();
        $this->assertHttpStatusCode(403, $response);

        /** @var array{message?: string} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertStringContainsString('"live" permission', (string) ($content['message'] ?? ''));
    }

    /**
     * Withdrawing is not a verdict on the request, it is how whoever is fixing the content clears
     * the way, so it takes the same permission as editing.
     */
    public function testCancelIsAllowedWithoutReviewPermission(): void
    {
        $id = $this->createPageInReview();
        $this->createUserWithoutReviewPermission();

        self::ensureKernelShutdown();

        $limitedClient = $this->createAuthenticatedClient(
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'PHP_AUTH_USER' => 'noreviewuser',
                'PHP_AUTH_PW' => 'test',
            ],
        );

        $limitedClient->request(
            'POST',
            \sprintf(
                '/admin/api/pages/%s?%s',
                $id,
                \http_build_query(['action' => 'cancel_review', 'locale' => 'en', 'webspace' => 'sulu-io']),
            ),
        );

        $this->assertHttpStatusCode(200, $limitedClient->getResponse());
    }

    /**
     * Rejecting also lifts the review lock, so without the review permission it must be refused —
     * otherwise any editor could reject their way past a review and then save over the content.
     */
    public function testRejectIsForbiddenWithoutReviewPermission(): void
    {
        $id = $this->createPageInReview();
        $this->createUserWithoutReviewPermission();

        self::ensureKernelShutdown();

        $limitedClient = $this->createAuthenticatedClient(
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'PHP_AUTH_USER' => 'noreviewuser',
                'PHP_AUTH_PW' => 'test',
            ],
        );

        $limitedClient->request(
            'POST',
            \sprintf(
                '/admin/api/pages/%s?%s',
                $id,
                \http_build_query(['action' => 'reject', 'locale' => 'en', 'webspace' => 'sulu-io']),
            ),
        );

        $this->assertHttpStatusCode(403, $limitedClient->getResponse());
    }

    /**
     * The content-level reject takes the page out of review, so the request has to end with it:
     * a request left open would keep the page locked with no transition able to free it.
     */
    public function testRejectClosesTheRequestAndFreesThePage(): void
    {
        $id = $this->createPageInReview();

        $this->trigger($id, 'reject');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{workflowPlace: string, _locked: bool, activeWorkflowTransitionRequest: mixed} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('unpublished', $content['workflowPlace']);
        $this->assertFalse($content['_locked']);
        $this->assertNull($content['activeWorkflowTransitionRequest']);

        // The rejected page has to be editable again, which is the whole point of closing the request.
        $this->put($id, ['action' => 'draft'], 'Reworked After Reject');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{title: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Reworked After Reject', $content['title']);
    }

    public function testCopyLocaleIntoReviewedLocaleIsRejected(): void
    {
        $id = $this->createPageInReview();

        $this->put($id, ['action' => 'draft', 'locale' => 'de'], 'Seite In Review');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages/%s?%s', $id, \http_build_query([
                'action' => 'copy_locale',
                'locale' => 'en',
                'webspace' => 'sulu-io',
                'src' => 'de',
                'dest' => 'en',
            ])),
        );

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode(), (string) $response->getContent());

        $this->client->request('GET', \sprintf('/admin/api/pages/%s?locale=en&webspace=sulu-io', $id));

        /** @var array{title: string, _locked: bool, activeWorkflowTransitionRequest: mixed} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Page In Review', $content['title']);
        $this->assertTrue($content['_locked']);
        $this->assertNotNull($content['activeWorkflowTransitionRequest']);
    }

    public function testRestoreIntoReviewedLocaleIsRejected(): void
    {
        // A version only exists once the page was published, and a workflow only lets a bypass do that.
        $id = $this->createPageInReview();

        $this->trigger($id, 'publish');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->put($id, ['action' => 'draft'], 'Draft Awaiting Review');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('POST', \sprintf('/admin/api/pages/%s?locale=en&action=request_for_review_draft', $id));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages/%s?locale=en&action=restore&version=%s', $id, $this->latestVersion($id)),
        );

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode(), (string) $response->getContent());

        $this->client->request('GET', \sprintf('/admin/api/pages/%s?locale=en&webspace=sulu-io', $id));

        /** @var array{title: string, _locked: bool, activeWorkflowTransitionRequest: mixed} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Draft Awaiting Review', $content['title']);
        $this->assertTrue($content['_locked']);
        $this->assertNotNull($content['activeWorkflowTransitionRequest']);
    }

    /**
     * The preview renders what the reviewer is asked to approve: loading it goes through the same
     * mapping as every keystroke, so both have to get past the review lock without writing anything.
     */
    public function testPreviewShowsContentInReview(): void
    {
        $id = $this->createPageInReview();

        $this->assertSame('Page In Review', $this->previewTitle($id, []));
        $this->assertSame('Typed While In Review', $this->previewTitle($id, $this->previewData('Typed While In Review')));
    }

    public function testPreviewShowsDraftInReview(): void
    {
        $id = $this->createPageInReview();

        $this->trigger($id, 'publish');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->put($id, ['action' => 'draft'], 'Draft Awaiting Review');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('POST', \sprintf('/admin/api/pages/%s?locale=en&action=request_for_review_draft', $id));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertSame('Typed While In Review', $this->previewTitle($id, $this->previewData('Typed While In Review')));
    }

    public function testPreviewLeavesTheReviewLockInPlace(): void
    {
        $id = $this->createPageInReview();

        $this->previewTitle($id, $this->previewData('Typed While In Review'));
        self::getEntityManager()->clear();

        $this->put($id, ['action' => 'draft'], 'Typed While In Review');

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode(), (string) $response->getContent());

        $this->client->request('GET', \sprintf('/admin/api/pages/%s?locale=en&webspace=sulu-io', $id));

        /** @var array{title: string, workflowPlace: string, _locked: bool} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Page In Review', $content['title']);
        $this->assertSame('review', $content['workflowPlace']);
        $this->assertTrue($content['_locked']);
    }

    public function testMappingOutsideThePreviewRoutesIsStillLocked(): void
    {
        $id = $this->createPageInReview();

        $this->expectException(ContentInReviewException::class);

        $this->previewTitle($id, $this->previewData('Typed While In Review'), 'sulu_page.put_page');
    }

    /**
     * Runs the admin's preview provider the way the preview controller does, inside a request on a
     * preview route: `render` rebuilds the object from its serialized form, `update` maps the form
     * data onto it, skipped for no data.
     *
     * @param array<string, mixed> $data
     */
    private function previewTitle(string $id, array $data, string $route = 'sulu_preview.update'): mixed
    {
        /** @var CachablePreviewDefaultsProviderInterface $previewProvider */
        $previewProvider = self::getContainer()->get('sulu_page.page_preview_provider');
        $previewContext = new PreviewContext($id, 'en');

        $requestStack = self::getContainer()->get('request_stack');
        $request = new Request(attributes: ['_route' => $route]);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);

        try {
            $defaults = $previewProvider->getDefaults($previewContext);
            $defaults = $previewProvider->deserialize($previewContext, $previewProvider->serialize($previewContext, $defaults));
            if ([] !== $data) {
                $defaults = $previewProvider->updateValues($previewContext, $defaults, $data);
            }
        } finally {
            $requestStack->pop();
        }

        /** @var PageDimensionContentInterface $object */
        $object = $defaults['object'];

        return $object->getTemplateData()['title'] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function previewData(string $title): array
    {
        return ['template' => 'review', 'title' => $title, 'url' => '/page-in-review'];
    }

    private function latestVersion(string $id): int
    {
        $this->client->request(
            'GET',
            \sprintf('/admin/api/pages/%s/versions?page=1&locale=en&webspace=sulu-io&fields=title,version,changer,id', $id),
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{_embedded: array{pages_versions: array<int, array{version: int}>}} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $version = $content['_embedded']['pages_versions'][0]['version'] ?? null;
        $this->assertNotEmpty($version, 'Publishing has to leave a version behind for the restore.');

        return $version;
    }

    public function testCreateWithRequestForReviewSendsTheNewPageToReview(): void
    {
        $homepage = $this->createHomepage();

        $this->client->request(
            'POST',
            \sprintf(
                '/admin/api/pages?%s',
                \http_build_query([
                    'locale' => 'en',
                    'parentId' => $homepage->getId(),
                    'webspace' => 'sulu-io',
                    'action' => 'request_for_review',
                ]),
            ),
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'review',
                'title' => 'Created And Sent To Review',
                'url' => '/created-and-sent-to-review',
            ]),
        );
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        /** @var array{workflowPlace: string, _locked: bool, title: string, activeWorkflowTransitionRequest: mixed} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('review', $content['workflowPlace']);
        $this->assertTrue($content['_locked']);
        $this->assertSame('Created And Sent To Review', $content['title']);
        $this->assertNotNull($content['activeWorkflowTransitionRequest']);
    }

    public function testSaveAndRequestForPublishFromAPublishedPageKeepsLiveUntouched(): void
    {
        $id = $this->createPageInReview();

        $this->trigger($id, 'publish');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        // One request saves the changed draft and asks for the review, decision 17.
        $this->put($id, ['action' => 'request_for_review_draft'], 'Reworked Draft');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{workflowPlace: string, _locked: bool, title: string, activeWorkflowTransitionRequest: mixed} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('review_draft', $content['workflowPlace']);
        $this->assertTrue($content['_locked']);
        $this->assertSame('Reworked Draft', $content['title'], 'The save has to run before the transition.');
        $this->assertNotNull($content['activeWorkflowTransitionRequest']);

        $page = self::getContainer()->get(PageRepositoryInterface::class)->getOneBy(['uuid' => $id]);
        $liveContent = self::getContainer()->get(ContentManagerInterface::class)->resolve(
            $page,
            ['stage' => DimensionContentInterface::STAGE_LIVE, 'locale' => 'en'],
        );
        $this->assertSame(
            'Page In Review',
            $liveContent->getTemplateData()['title'] ?? null,
            'Readers keep the published version while the new draft is under review.',
        );
    }

    public function testDraftSaveIsAllowedWhenNoReviewIsOpen(): void
    {
        $id = $this->createPage();

        $this->put($id, ['action' => 'draft'], 'Edited Normally');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    private function trigger(string $id, string $action): void
    {
        $this->client->request(
            'POST',
            \sprintf(
                '/admin/api/pages/%s?%s',
                $id,
                \http_build_query(['action' => $action, 'locale' => 'en', 'webspace' => 'sulu-io']),
            ),
        );
    }

    /**
     * @param array<string, string> $query
     */
    private function put(string $id, array $query, string $title): void
    {
        $this->client->request(
            'PUT',
            \sprintf('/admin/api/pages/%s?%s', $id, \http_build_query($query + ['locale' => 'en', 'webspace' => 'sulu-io'])),
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'review',
                'title' => $title,
                'url' => '/page-in-review',
            ]),
        );
    }

    private function createPage(): string
    {
        $homepage = $this->createHomepage();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'review',
                'title' => 'Page In Review',
                'url' => '/page-in-review',
            ]),
        );
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);

        return $content['id'];
    }

    private function createPageInReview(): string
    {
        $id = $this->createPage();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages/%s?locale=en&action=request_for_review', $id),
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{workflowPlace: string, _locked: bool} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('review', $content['workflowPlace']);
        $this->assertTrue($content['_locked'], 'A workflow must be configured for pages in this kernel.');

        return $id;
    }

    private function createUserWithoutReviewPermission(): void
    {
        // 127 is every bit except `review` (128), so the user may edit but not act on a review.
        $this->createLimitedUser('noreviewuser', 'Reviewer Without Review', 127);
    }

    private function createUserWithoutLivePermission(): void
    {
        // 253 is every bit except `live` (2).
        $this->createLimitedUser('limiteduser', 'Reviewer Without Live', 253);
    }

    private function createLimitedUser(string $username, string $roleName, int $mask): void
    {
        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName($roleName);
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions($mask);
        $permission->setContext(PageAdmin::getPageSecurityContext('sulu-io'));
        $entityManager->persist($permission);

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

    private function createHomepage(): PageInterface
    {
        $homepage = new Page('0199ee04-c220-784e-a6fa-ac985870f2d5');
        $homepage->setLft(0);
        $homepage->setRgt(1);
        $homepage->setDepth(0);
        $homepage->setWebspaceKey('sulu-io');
        self::getEntityManager()->persist($homepage);
        self::getEntityManager()->flush();

        return $homepage;
    }
}
