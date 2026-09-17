<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Controller;

use PHPCR\SessionInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Copying creates a page below the destination, so it needs the add permission on the webspace of the destination.
 */
class PageControllerCopyPermissionTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SessionInterface
     */
    private $session;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->client->disableReboot();
        $this->purgeDatabase();
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');
    }

    public function testCopyWithoutAddPermissionIsForbidden(): void
    {
        $this->createUser('editor', ['sulu.webspaces.sulu_io' => 80]); // view and edit
        $page = $this->createPage();

        $this->copy('editor', $page, $this->getHomepageUuid('sulu_io'));

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    public function testCopyIntoWebspaceWithoutPermissionIsForbidden(): void
    {
        $this->createUser('author', ['sulu.webspaces.sulu_io' => 112]); // view, add and edit
        $page = $this->createPage();

        $this->copy('author', $page, $this->getHomepageUuid('test_io'));

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    public function testCopyWithAddPermissionIsAllowed(): void
    {
        $this->createUser('author', ['sulu.webspaces.sulu_io' => 112]); // view, add and edit
        $page = $this->createPage();

        $this->copy('author', $page, $this->getHomepageUuid('sulu_io'));

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    private function copy(string $username, PageDocument $page, string $destinationUuid): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $page->getUuid() . '?language=en&action=copy&destination=' . $destinationUuid,
            [],
            ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => $username]
        );
    }

    private function createPage(): PageDocument
    {
        /** @var PageDocument $page */
        $page = $this->documentManager->create('page');
        $page->setTitle('Testpage');
        $page->setResourceSegment('/testpage');
        $page->setStructureType('default');
        $this->documentManager->persist($page, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();
        $this->documentManager->clear();

        return $page;
    }

    private function getHomepageUuid(string $webspaceKey): string
    {
        return $this->session->getNode('/cmf/' . $webspaceKey . '/contents')->getIdentifier();
    }

    /**
     * @param array<string, int> $permissions
     */
    private function createUser(string $username, array $permissions): void
    {
        $entityManager = $this->getEntityManager();

        $role = $this->getContainer()->get('sulu.repository.role')->createNew();
        $role->setName('Limited Role');
        $role->setAnonymous(false);
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        foreach ($permissions as $context => $mask) {
            $permission = new Permission();
            $permission->setRole($role);
            $permission->setContext($context);
            $permission->setPermissions($mask);
            $role->addPermission($permission);
            $entityManager->persist($permission);
        }

        $contact = new Contact();
        $contact->setFirstName('Limited');
        $contact->setLastName('User');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername($username);
        $user->setContact($contact);
        $user->setSalt('');
        $user->setLocale('en');

        $passwordHasherFactory = self::getContainer()->get('sulu_security.encoder_factory');
        $user->setPassword($passwordHasherFactory->getPasswordHasher($user)->hash($username));

        $userRole = new UserRole();
        $userRole->setUser($user);
        $userRole->setRole($role);
        $userRole->setLocale(\json_encode(['en']) ?: '');
        $user->addUserRole($userRole);

        $entityManager->persist($user);
        $entityManager->persist($userRole);
        $entityManager->flush();
        $entityManager->clear();
    }
}
