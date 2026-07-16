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
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

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
     *  - an "attacker" user with a role that has no permissions on any context, used by the two denial
     *    tests above via HTTP basic auth.
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

        $this->getEntityManager()->flush();
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
