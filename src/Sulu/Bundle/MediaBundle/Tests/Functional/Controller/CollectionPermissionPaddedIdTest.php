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
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * A collection id with leading zeros loads the same collection as the plain id, so it must not skip the permissions.
 */
class CollectionPermissionPaddedIdTest extends SuluTestCase
{
    private KernelBrowser $client;

    private int $allowedCollectionId;

    private int $forbiddenCollectionId;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $entityManager = $this->getEntityManager();
        (new LoadCollectionTypes())->load($entityManager);

        $role = $this->createRole();
        $this->createUser($role);

        $this->allowedCollectionId = $this->createCollection('allowed');
        $this->forbiddenCollectionId = $this->createCollection('forbidden');
        $entityManager->flush();

        $accessControlManager = $this->getContainer()->get('sulu_security.access_control_manager');
        $accessControlManager->setPermissions(Collection::class, (string) $this->allowedCollectionId, [
            $role->getId() => ['view' => true, 'add' => true, 'edit' => true, 'delete' => true],
        ]);
        $accessControlManager->setPermissions(Collection::class, (string) $this->forbiddenCollectionId, [
            $role->getId() => ['view' => false, 'add' => false, 'edit' => false, 'delete' => false],
        ]);
        $entityManager->clear();
    }

    public function testForbiddenCollectionIsDeniedWithPlainAndPaddedId(): void
    {
        foreach (['', '0', '00'] as $padding) {
            $id = $padding . $this->forbiddenCollectionId;

            $this->assertSame(403, $this->send('GET', '/api/collections/' . $id . '?locale=en'), 'get ' . $id);
            $this->assertSame(
                403,
                $this->send('PUT', '/api/collections/' . $id . '?locale=en', ['title' => 'changed', 'type' => ['id' => 1]]),
                'put ' . $id
            );
            $this->assertSame(403, $this->send('DELETE', '/api/collections/' . $id), 'delete ' . $id);
            $this->assertSame(403, $this->send('GET', '/api/media?locale=en&collection=' . $id), 'list ' . $id);
        }

        $collection = $this->getEntityManager()->find(Collection::class, $this->forbiddenCollectionId);
        $this->assertNotNull($collection);
        $this->assertNotContains('changed', \array_map(
            static fn ($meta) => $meta->getTitle(),
            $collection->getMeta()->toArray()
        ));
    }

    public function testMediaCannotBeMovedIntoForbiddenCollectionWithPaddedId(): void
    {
        $mediaId = $this->createMedia($this->allowedCollectionId);

        foreach (['', '0', '00'] as $padding) {
            $this->assertSame(
                403,
                $this->send(
                    'PUT',
                    '/api/media/' . $mediaId . '?locale=en',
                    ['title' => 'moved', 'collection' => $padding . $this->forbiddenCollectionId]
                ),
                'put collection ' . $padding . $this->forbiddenCollectionId
            );
            $this->assertSame(
                '' === $padding ? 403 : 400, // a padded destination is not a valid integer
                $this->send(
                    'POST',
                    '/api/media/' . $mediaId . '?action=move&locale=en&destination=' . $padding . $this->forbiddenCollectionId
                ),
                'move to ' . $padding . $this->forbiddenCollectionId
            );
        }

        $media = $this->getEntityManager()->find(Media::class, $mediaId);
        $this->assertNotNull($media);
        $this->assertSame($this->allowedCollectionId, $media->getCollection()->getId());
    }

    public function testMediaCannotBeCreatedInForbiddenCollectionWithPaddedId(): void
    {
        foreach (['', '0', '00'] as $padding) {
            $this->client->request(
                'POST',
                '/api/media?locale=en&collection=' . $padding . $this->forbiddenCollectionId,
                [],
                ['fileVersion' => $this->createUploadedFile()],
                ['PHP_AUTH_USER' => 'attacker', 'PHP_AUTH_PW' => 'attacker']
            );
            $this->assertSame(403, $this->client->getResponse()->getStatusCode(), 'create in ' . $padding . $this->forbiddenCollectionId);
        }

        $this->assertCount(0, $this->getEntityManager()->getRepository(Media::class)->findAll());
    }

    public function testAllowedCollectionStillWorksWithPlainAndPaddedId(): void
    {
        $mediaId = $this->createMedia($this->allowedCollectionId);

        $this->assertSame(200, $this->send('GET', '/api/collections/' . $this->allowedCollectionId . '?locale=en'));
        $this->assertSame(200, $this->send('GET', '/api/collections/0' . $this->allowedCollectionId . '?locale=en'));
        $this->assertSame(
            200,
            $this->send('PUT', '/api/media/' . $mediaId . '?locale=en', ['title' => 'own', 'collection' => '0' . $this->allowedCollectionId])
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function send(string $method, string $path, array $parameters = []): int
    {
        $this->client->jsonRequest($method, $path, $parameters, ['PHP_AUTH_USER' => 'attacker', 'PHP_AUTH_PW' => 'attacker']);
        $this->getEntityManager()->clear();

        return $this->client->getResponse()->getStatusCode();
    }

    private function createRole(): RoleInterface
    {
        $role = $this->getContainer()->get('sulu.repository.role')->createNew();
        $role->setName('Attacker');
        $role->setAnonymous(false);
        $role->setSystem('Sulu');
        $this->getEntityManager()->persist($role);

        $permission = new Permission();
        $permission->setContext('sulu.media.collections');
        $permission->setPermissions(120);
        $permission->setRole($role);
        $role->addPermission($permission);
        $this->getEntityManager()->persist($permission);

        return $role;
    }

    private function createUser(RoleInterface $role): void
    {
        $contact = new Contact();
        $contact->setFirstName('attacker');
        $contact->setLastName('User');
        $this->getEntityManager()->persist($contact);

        $user = new User();
        $user->setUsername('attacker');
        $user->setContact($contact);
        $user->setSalt('');
        $user->setLocale('en');
        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $user->setPassword($passwordHasherFactory->getPasswordHasher($user)->hash('attacker'));

        $userRole = new UserRole();
        $userRole->setUser($user);
        $userRole->setRole($role);
        $userRole->setLocale(\json_encode(['en']) ?: '');
        $user->addUserRole($userRole);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->persist($userRole);
    }

    private function createCollection(string $title): int
    {
        return $this->getContainer()->get('sulu_media.collection_manager')
            ->save(['title' => $title, 'locale' => 'en', 'type' => ['id' => 1]], 1)
            ->getId();
    }

    private function createMedia(int $collectionId): int
    {
        return $this->getContainer()->get('sulu_media.media_manager')->save(
            $this->createUploadedFile(),
            ['title' => 'media', 'collection' => $collectionId, 'locale' => 'en'],
            null
        )->getId();
    }

    private function createUploadedFile(): UploadedFile
    {
        $path = \sys_get_temp_dir() . '/' . \uniqid('padded-id-', true) . '.jpeg';
        \copy(__DIR__ . '/../../Fixtures/files/photo.jpeg', $path);

        return new UploadedFile($path, 'photo.jpeg', 'image/jpeg');
    }
}
