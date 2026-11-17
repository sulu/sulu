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
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class MediaVersionControllerTest extends SuluTestCase
{
    private const AUTH = ['PHP_AUTH_USER' => 'editor', 'PHP_AUTH_PW' => 'editor'];

    private KernelBrowser $client;

    private int $allowedCollectionId;

    private int $forbiddenCollectionId;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();

        (new LoadCollectionTypes())->load($this->getEntityManager());

        $role = $this->createRole();
        $this->createUser('editor', $role);
        $this->allowedCollectionId = $this->createCollection('Allowed');
        $this->forbiddenCollectionId = $this->createCollection('Forbidden');
        $this->getEntityManager()->flush();

        $accessControlManager = $this->getContainer()->get('sulu_security.access_control_manager');
        $accessControlManager->setPermissions(Collection::class, (string) $this->allowedCollectionId, [
            $role->getId() => ['view' => true, 'add' => false, 'edit' => false, 'delete' => false, 'security' => false],
        ]);
        $accessControlManager->setPermissions(Collection::class, (string) $this->forbiddenCollectionId, [
            $role->getId() => ['view' => false, 'add' => false, 'edit' => false, 'delete' => false, 'security' => false],
        ]);
    }

    public function testListVersionsNewestFirst(): void
    {
        $media = $this->createMedia('photo', $this->allowedCollectionId);
        $this->addVersion($media);
        $this->addVersion($media);

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/media/%s/versions?locale=en&fields=id,version,created', $media->getId()),
            [],
            self::AUTH
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $versions = $this->getVersions();
        $this->assertCount(3, $versions);
        $this->assertSame([3, 2, 1], \array_column($versions, 'version'));
        $this->assertSame([true, false, false], \array_column($versions, 'current'));
        $this->assertSame('photo.jpeg', $versions[1]['name'] ?? null);
        $this->assertArrayHasKey('created', $versions[1]);
        $this->assertSame(
            \sprintf('/admin/media/%s/download/photo.jpeg?v=2', $media->getId()),
            $versions[1]['adminUrl'] ?? null
        );
        $this->assertSame(\sprintf('/media/%s/download/photo.jpeg?v=2', $media->getId()), $versions[1]['url'] ?? null);
    }

    public function testListVersionsSortedByRequest(): void
    {
        $media = $this->createMedia('photo', $this->allowedCollectionId);
        $this->addVersion($media);

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/media/%s/versions?locale=en&sortBy=version&sortOrder=asc', $media->getId()),
            [],
            self::AUTH
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame([1, 2], \array_column($this->getVersions(), 'version'));
    }

    public function testListVersionsOnlyOfTheGivenMedia(): void
    {
        $media = $this->createMedia('photo', $this->allowedCollectionId);
        $this->addVersion($this->createMedia('other', $this->allowedCollectionId));

        $this->client->jsonRequest('GET', \sprintf('/api/media/%s/versions?locale=en', $media->getId()), [], self::AUTH);

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame([1], \array_column($this->getVersions(), 'version'));
    }

    public function testListVersionsOfMediaInForbiddenCollection(): void
    {
        $media = $this->createMedia('secret', $this->forbiddenCollectionId);

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/media/%s/versions?locale=en&collection=%s', $media->getId(), $this->allowedCollectionId),
            [],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    public function testListVersionsWithoutMediaPermission(): void
    {
        $this->createUser('outsider', $this->createRole(false));
        $this->getEntityManager()->flush();
        $media = $this->createMedia('photo', $this->createCollection('Without ACL'));

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/media/%s/versions?locale=en', $media->getId()),
            [],
            ['PHP_AUTH_USER' => 'outsider', 'PHP_AUTH_PW' => 'outsider']
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    public function testListVersionsOfMissingMedia(): void
    {
        $this->client->jsonRequest('GET', '/api/media/999999/versions?locale=en', [], self::AUTH);

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getVersions(): array
    {
        /** @var array{_embedded: array{media_versions: list<array<string, mixed>>}} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);

        return $content['_embedded']['media_versions'];
    }

    private function createRole(bool $mediaPermission = true): RoleInterface
    {
        $role = $this->getContainer()->get('sulu.repository.role')->createNew();
        $role->setName($mediaPermission ? 'Editor Role' : 'Outsider Role');
        $role->setAnonymous(false);
        $role->setSystem('Sulu');
        $this->getEntityManager()->persist($role);

        if (!$mediaPermission) {
            return $role;
        }

        $permission = new Permission();
        $permission->setContext('sulu.media.collections');
        $permission->setPermissions(120);
        $permission->setRole($role);
        $role->addPermission($permission);
        $this->getEntityManager()->persist($permission);

        return $role;
    }

    private function createUser(string $username, RoleInterface $role): User
    {
        $contact = new Contact();
        $contact->setFirstName('Editor');
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

    private function addVersion(Media $media): void
    {
        $this->getContainer()->get('sulu_media.media_manager')->save(
            $this->createUploadedFile(),
            [
                'id' => $media->getId(),
                'locale' => 'en',
            ],
            null
        );
    }

    private function createUploadedFile(): UploadedFile
    {
        $source = __DIR__ . '/../../Fixtures/files/photo.jpeg';
        $path = \sys_get_temp_dir() . '/' . \uniqid('media-version-', true) . '.jpeg';
        \copy($source, $path);

        return new UploadedFile($path, 'photo.jpeg', 'image/jpeg');
    }
}
