<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Reproduces the media move authorization bypass (GHSA-h6cx-gjxx-v25c).
 *
 * A user with edit rights on collection A but no rights on the restricted collection B must not
 * be able to move a media out of B by naming A in the request, because the central security
 * listener only checks the request collection and not the media's real source collection.
 */
class MediaControllerMovePermissionTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();

        (new LoadCollectionTypes())->load($this->getEntityManager());
        $this->getEntityManager()->flush();
    }

    public function testMoveCannotStealMediaFromForbiddenCollection(): void
    {
        $role = $this->createRole();
        $this->createUser('attacker', $role);

        $allowedCollectionId = $this->createCollection('Allowed');
        $forbiddenCollectionId = $this->createCollection('Forbidden');

        $this->getEntityManager()->flush();

        $accessControlManager = $this->getContainer()->get('sulu_security.access_control_manager');
        // attacker role may edit collection A
        $accessControlManager->setPermissions(Collection::class, (string) $allowedCollectionId, [
            $role->getId() => ['view' => true, 'add' => true, 'edit' => true, 'delete' => true, 'security' => false],
        ]);
        // attacker role has no access to the restricted collection B
        $accessControlManager->setPermissions(Collection::class, (string) $forbiddenCollectionId, [
            $role->getId() => ['view' => false, 'add' => false, 'edit' => false, 'delete' => false, 'security' => false],
        ]);

        $media = $this->createMedia('secret', $forbiddenCollectionId);

        $auth = ['PHP_AUTH_USER' => 'attacker', 'PHP_AUTH_PW' => 'attacker'];

        // baseline: the attacker cannot even read the media inside the forbidden collection
        $this->client->jsonRequest('GET', '/api/media/' . $media->getId() . '?locale=en', [], $auth);
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        // exploit attempt: move the media out of B while naming collection A (which the attacker controls)
        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/media/%s?action=move&collection=%s&destination=%s&locale=en',
                $media->getId(),
                $allowedCollectionId,
                $allowedCollectionId
            ),
            [],
            $auth
        );

        // with the fix the move is rejected, because the attacker lacks edit rights on the real source collection B
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        // and the media must still live in the forbidden collection
        $this->getEntityManager()->clear();
        $reloaded = $this->getEntityManager()->find(MediaEntity::class, $media->getId());
        $this->assertNotNull($reloaded);
        $this->assertSame($forbiddenCollectionId, $reloaded->getCollection()->getId());
    }

    private function createRole(): RoleInterface
    {
        $role = $this->getContainer()->get('sulu.repository.role')->createNew();
        $role->setName('Attacker Role');
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

        /** @var PasswordHasherFactoryInterface $passwordHasherFactory */
        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $user->setPassword($passwordHasherFactory->getPasswordHasher($user)->hash($username));

        $userRole = new UserRole();
        $userRole->setUser($user);
        $userRole->setRole($role);
        $userRole->setLocale(\json_encode(['en']) ?: '');
        $user->addUserRole($userRole);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->persist($userRole);

        return $user;
    }

    private function createCollection(string $title): int
    {
        $collection = $this->getContainer()->get('sulu_media.collection_manager')->save(
            [
                'title' => $title,
                'locale' => 'en',
                'type' => ['id' => 1],
            ],
            1
        );

        return $collection->getId();
    }

    private function createMedia(string $title, int $collectionId): Media
    {
        return $this->getContainer()->get('sulu_media.media_manager')->save(
            $this->createUploadedFile(),
            [
                'title' => $title,
                'collection' => $collectionId,
                'locale' => 'en',
            ],
            null
        );
    }

    private function createUploadedFile(): UploadedFile
    {
        $source = __DIR__ . '/../../Fixtures/files/photo.jpeg';
        $path = \sys_get_temp_dir() . '/' . \uniqid('media-move-', true) . '.jpeg';
        \copy($source, $path);

        return new UploadedFile($path, 'photo.jpeg', 'image/jpeg');
    }
}
