<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Functional\UserInterface\Controller;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Kernel\SuluKernelBrowser;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function Symfony\Component\String\u;

/**
 * Reproduces GHSA-65cv-w493-7vhq.
 *
 * Sulu\Bundle\PreviewBundle\Application\Manager\PreviewLinkManager::generate() and ::revoke() used to
 * create/remove a public preview link for any page, article or snippet without checking whether the
 * acting user actually has Sulu VIEW permission on the resource's security context (e.g.
 * "sulu.webspaces.sulu_io" for a page). This allowed any authenticated admin user to mint (or revoke)
 * a public, unauthenticated preview URL for content they are not allowed to see, bypassing the normal
 * webspace/role based access control entirely.
 *
 * These tests prove that a user without VIEW permission on the page's webspace is rejected with a 403
 * when trying to generate or revoke a preview link, and that no preview link is created/removed as a
 * side effect of the denied request.
 *
 * Reading a preview link (GHSA-w7xr-qjf5-rqvg) needs the same permission, and for a page its own
 * permissions count in all three actions.
 */
class PreviewLinkControllerPermissionTest extends SuluTestCase
{
    /**
     * @var SuluKernelBrowser
     */
    private $client;

    /**
     * @var BasePageDocument
     */
    private $homePage;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var string
     */
    private $resourceKey = 'pages';

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_io';

    /**
     * @var string
     */
    private $locale = 'en';

    /**
     * @var mixed[]
     */
    private $attackerAuth = ['PHP_AUTH_USER' => 'attacker', 'PHP_AUTH_PW' => 'attacker'];

    /**
     * @var mixed[]
     */
    private $viewerAuth = ['PHP_AUTH_USER' => 'viewer', 'PHP_AUTH_PW' => 'viewer'];

    public function setUp(): void
    {
        /** @var SuluKernelBrowser $client */
        $client = static::createAuthenticatedClient();
        $this->client = $client;

        static::initPhpcr();
        $this->documentManager = static::getContainer()->get('sulu_document_manager.document_manager');

        /** @var BasePageDocument $document */
        $document = $this->documentManager->find(\sprintf('/cmf/%s/contents', $this->webspaceKey), $this->locale);
        $this->homePage = $document;

        $this->initOrm();
    }

    public function testGenerateDeniedForUserWithoutViewPermission(): void
    {
        $page = $this->createPage(__METHOD__);
        $resourceId = $page->getUuid();

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=generate&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            ),
            [],
            $this->attackerAuth
        );

        static::assertHttpStatusCode(403, $this->client->getResponse());

        $previewLink = static::getContainer()->get('sulu_preview.preview_link_repository')
            ->findByResource($this->resourceKey, $resourceId, $this->locale);
        static::assertNull($previewLink);
    }

    public function testRevokeDeniedForUserWithoutViewPermission(): void
    {
        $page = $this->createPage(__METHOD__);
        $resourceId = $page->getUuid();

        $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=revoke&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            ),
            [],
            $this->attackerAuth
        );

        static::assertHttpStatusCode(403, $this->client->getResponse());

        $previewLink = static::getContainer()->get('sulu_preview.preview_link_repository')
            ->findByResource($this->resourceKey, $resourceId, $this->locale);
        static::assertNotNull($previewLink);
    }

    public function testGetDeniedForUserWithoutViewPermission(): void
    {
        $page = $this->createPage(__METHOD__);
        $resourceId = $page->getUuid();

        $previewLink = $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/preview-links/%s?resourceKey=%s&locale=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale
            ),
            [],
            $this->attackerAuth
        );

        static::assertHttpStatusCode(403, $this->client->getResponse());
        static::assertStringNotContainsString(
            $previewLink->getToken(),
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testGetAllowedForUserWithViewPermission(): void
    {
        $page = $this->createPage(__METHOD__);
        $resourceId = $page->getUuid();

        $previewLink = $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/preview-links/%s?resourceKey=%s&locale=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale
            ),
            [],
            $this->viewerAuth
        );

        static::assertHttpStatusCode(200, $this->client->getResponse());
        static::assertStringContainsString(
            $previewLink->getToken(),
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testGetDeniedWhenThePageDeniesViewToTheRole(): void
    {
        $resourceId = $this->createPage(__METHOD__)->getUuid();
        $previewLink = $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);
        $this->setViewerPermissionOnPage($resourceId, false);

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/preview-links/%s?resourceKey=%s&locale=%s', $resourceId, $this->resourceKey, $this->locale),
            [],
            $this->viewerAuth
        );

        static::assertHttpStatusCode(403, $this->client->getResponse());
        static::assertStringNotContainsString(
            $previewLink->getToken(),
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testGetAllowedWhenThePageGrantsViewToTheRole(): void
    {
        $resourceId = $this->createPage(__METHOD__)->getUuid();
        $previewLink = $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);
        $this->setViewerPermissionOnPage($resourceId, true);

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/preview-links/%s?resourceKey=%s&locale=%s', $resourceId, $this->resourceKey, $this->locale),
            [],
            $this->viewerAuth
        );

        static::assertHttpStatusCode(200, $this->client->getResponse());
        static::assertStringContainsString(
            $previewLink->getToken(),
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testGenerateDeniedWhenThePageDeniesViewToTheRole(): void
    {
        $resourceId = $this->createPage(__METHOD__)->getUuid();
        $this->setViewerPermissionOnPage($resourceId, false);

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=generate&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            ),
            [],
            $this->viewerAuth
        );

        static::assertHttpStatusCode(403, $this->client->getResponse());
        static::assertNull(
            static::getContainer()->get('sulu_preview.preview_link_repository')
                ->findByResource($this->resourceKey, $resourceId, $this->locale)
        );
    }

    public function testRevokeDeniedWhenThePageDeniesViewToTheRole(): void
    {
        $resourceId = $this->createPage(__METHOD__)->getUuid();
        $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);
        $this->setViewerPermissionOnPage($resourceId, false);

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=revoke&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            ),
            [],
            $this->viewerAuth
        );

        static::assertHttpStatusCode(403, $this->client->getResponse());
        static::assertNotNull(
            static::getContainer()->get('sulu_preview.preview_link_repository')
                ->findByResource($this->resourceKey, $resourceId, $this->locale)
        );
    }

    public function testActivityListHidesThePreviewLinkOfAPageDeniedToTheRole(): void
    {
        $allowedId = $this->createPage(__METHOD__ . ' allowed')->getUuid();
        $deniedId = $this->createPage(__METHOD__ . ' denied')->getUuid();
        $this->setViewerPermissionOnPage($allowedId, true);
        $this->setViewerPermissionOnPage($deniedId, false);

        $tokens = [];
        foreach ([$allowedId, $deniedId] as $resourceId) {
            $this->client->jsonRequest(
                'POST',
                \sprintf(
                    '/api/preview-links/%s?action=generate&resourceKey=%s&locale=%s&webspaceKey=%s',
                    $resourceId,
                    $this->resourceKey,
                    $this->locale,
                    $this->webspaceKey
                )
            );
            static::assertHttpStatusCode(201, $this->client->getResponse());
            /** @var array{token: string} $link */
            $link = \json_decode((string) $this->client->getResponse()->getContent(), true);
            $tokens[$resourceId] = $link['token'];
        }

        $container = static::getContainer();
        $viewer = $container->get('sulu.repository.user')->findOneBy(['username' => 'viewer']);
        static::assertNotNull($viewer);
        $container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($viewer, 'admin', $viewer->getRoles())
        );

        $request = new Request(['locale' => $this->locale]);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->setRequestFormat('json');
        $request->attributes->set('_format', 'json');
        $container->get('request_stack')->push($request);

        $response = $container->get('sulu_activity.activity_controller')->cgetAction($request);

        static::assertSame(200, $response->getStatusCode());
        $content = (string) $response->getContent();
        static::assertStringContainsString($tokens[$allowedId], $content);
        static::assertStringNotContainsString($tokens[$deniedId], $content);
    }

    public function testGetUnknownResourceKeyReturnsNotFound(): void
    {
        $resourceId = $this->createPage(__METHOD__)->getUuid();

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/preview-links/%s?resourceKey=unknown&locale=%s', $resourceId, $this->locale)
        );

        static::assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGenerateAllowedForSuperadmin(): void
    {
        $page = $this->createPage(__METHOD__);
        $resourceId = $page->getUuid();

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=generate&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            )
        );

        static::assertHttpStatusCode(201, $this->client->getResponse());
    }

    /**
     * Purges the ORM database and rebuilds the minimal set of users/roles/permissions needed by this
     * test class:
     *  - the default "test" user (recreated via test_user_provider, as it was just purged) is granted
     *    full permissions (including VIEW) on the "sulu.webspaces.sulu_io" context, so it behaves like
     *    a superadmin for testGenerateAllowedForSuperadmin().
     *  - an "attacker" user with a role that has no permissions on any context, used by the denial
     *    tests above via HTTP basic auth.
     *  - a "viewer" user whose role holds only VIEW (64) on the webspace context, used by the tests that
     *    need a real voter, not the test user, to decide.
     *
     * This mirrors the pattern used by SmartContentItemControllerTest::initOrm(). Purging is safe here
     * because pages are created afterwards directly through the DocumentManager (PHPCR), which does not
     * depend on the ORM users/roles created here, nor on the HTTP security layer.
     */
    private function initOrm(): void
    {
        static::purgeDatabase();

        $testUser = static::getTestUser();

        $testRole = $this->createRole('Test Role');

        $testUserRole = new UserRole();
        $testUserRole->setUser($testUser);
        $testUserRole->setRole($testRole);
        $testUserRole->setLocale(\json_encode([$this->locale]) ?: '');
        $testUser->addUserRole($testUserRole);
        $this->getEntityManager()->persist($testUserRole);

        $testPermission = new Permission();
        $testPermission->setPermissions(122);
        $testPermission->setRole($testRole);
        $testPermission->setContext('sulu.webspaces.' . $this->webspaceKey);
        $this->getEntityManager()->persist($testPermission);

        $this->createUser('attacker', $this->createRole('Attacker Role'));

        $viewerRole = $this->createRole('Viewer Role');
        $viewerPermission = new Permission();
        $viewerPermission->setPermissions(64);
        $viewerPermission->setRole($viewerRole);
        $viewerRole->addPermission($viewerPermission);
        $viewerPermission->setContext('sulu.webspaces.' . $this->webspaceKey);
        $this->getEntityManager()->persist($viewerPermission);
        $this->createUser('viewer', $viewerRole);

        $this->getEntityManager()->flush();
    }

    private function setViewerPermissionOnPage(string $uuid, bool $view): void
    {
        $viewerRole = static::getContainer()->get('sulu.repository.role')->findOneBy(['name' => 'Viewer Role']);
        static::assertNotNull($viewerRole);

        static::getContainer()->get('sulu_security.access_control_manager')->setPermissions(
            SecurityBehavior::class,
            $uuid,
            [
                $viewerRole->getId() => [
                    'view' => $view,
                    'add' => false,
                    'edit' => false,
                    'delete' => false,
                    'archive' => false,
                    'live' => false,
                    'security' => false,
                ],
            ]
        );
    }

    private function createRole(string $name): RoleInterface
    {
        $role = static::getContainer()->get('sulu.repository.role')->createNew();
        $role->setName($name);
        $role->setAnonymous(false);
        $role->setSystem('Sulu');
        $this->getEntityManager()->persist($role);

        return $role;
    }

    private function createUser(string $username, RoleInterface $role): User
    {
        $contact = new Contact();
        $contact->setFirstName('Attacker');
        $contact->setLastName('User');
        $this->getEntityManager()->persist($contact);

        $user = new User();
        $user->setUsername($username);
        $user->setContact($contact);
        $user->setSalt('');
        $user->setLocale('en');

        $passwordHasherFactory = static::getContainer()->get('sulu_security.encoder_factory');
        if ($passwordHasherFactory instanceof PasswordHasherFactoryInterface) {
            $hasher = $passwordHasherFactory->getPasswordHasher($user);
            $password = $hasher->hash($username);
        } else {
            $encoder = $passwordHasherFactory->getEncoder($user);
            $password = $encoder->encodePassword($username, $user->getSalt());
        }

        $user->setPassword($password);

        $userRole = new UserRole();
        $userRole->setUser($user);
        $userRole->setRole($role);
        $userRole->setLocale(\json_encode([$this->locale]) ?: '');
        $user->addUserRole($userRole);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->persist($userRole);

        return $user;
    }

    protected function createPreviewLink(
        string $resourceKey,
        string $resourceId,
        string $locale,
        string $webspaceKey
    ): PreviewLinkInterface {
        $repository = static::getContainer()->get('sulu_preview.preview_link_repository');
        $previewLink = $repository->create($resourceKey, $resourceId, $locale, ['webspaceKey' => $webspaceKey]);
        $repository->add($previewLink);
        $repository->commit();

        return $previewLink;
    }

    private function createPage(string $title): BasePageDocument
    {
        $page = new PageDocument();
        $page->setTitle($title);
        $page->setResourceSegment('/' . u($title)->ascii()->toString());
        $page->setParent($this->homePage);
        $page->setStructureType('default');
        $page->getStructure()->bind(
            [
                'title' => 'World',
            ],
            true
        );

        $this->documentManager->persist($page, $this->locale);
        $this->documentManager->flush();

        return $page;
    }
}
