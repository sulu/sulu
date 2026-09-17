<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Controller;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Copying creates a new snippet, so it needs the add permission.
 */
class SnippetControllerCopyPermissionTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
    }

    public function testCopyWithoutAddPermissionIsForbidden(): void
    {
        $this->createUser('editor', 80); // view and edit
        $snippet = $this->createSnippet();

        $this->copy('editor', $snippet);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    public function testCopyWithAddPermissionIsAllowed(): void
    {
        $this->createUser('author', 112); // view, add and edit
        $snippet = $this->createSnippet();

        $this->copy('author', $snippet);

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    private function copy(string $username, SnippetDocument $snippet): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/snippets/' . $snippet->getUuid() . '?locale=de&action=copy',
            [],
            ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => $username]
        );
    }

    private function createSnippet(): SnippetDocument
    {
        /** @var SnippetDocument $snippet */
        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('hotel');
        $snippet->setTitle('The Grand Budapest');
        $this->documentManager->persist($snippet, 'de');
        $this->documentManager->publish($snippet, 'de');
        $this->documentManager->flush();

        return $snippet;
    }

    private function createUser(string $username, int $permissions): void
    {
        $entityManager = $this->getEntityManager();

        $role = $this->getContainer()->get('sulu.repository.role')->createNew();
        $role->setName('Limited Role');
        $role->setAnonymous(false);
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setContext('sulu.global.snippets');
        $permission->setPermissions($permissions);
        $role->addPermission($permission);
        $entityManager->persist($permission);

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
        $userRole->setLocale(\json_encode(['de', 'en']) ?: '');
        $user->addUserRole($userRole);

        $entityManager->persist($user);
        $entityManager->persist($userRole);
        $entityManager->flush();
        $entityManager->clear();
    }
}
