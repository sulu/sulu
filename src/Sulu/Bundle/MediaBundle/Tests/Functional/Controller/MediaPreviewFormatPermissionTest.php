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

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * The preview image and crop format endpoints must check the permissions of the media's real collection.
 */
class MediaPreviewFormatPermissionTest extends SuluTestCase
{
    private const AUTH = ['PHP_AUTH_USER' => 'attacker', 'PHP_AUTH_PW' => 'attacker'];

    private const CROP = ['cropX' => 1, 'cropY' => 2, 'cropWidth' => 3, 'cropHeight' => 4];

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

        $this->getContainer()->get('sulu_media.system_collections.cache')->invalidate();
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function provideRestrictedUsers(): iterable
    {
        yield 'user without any media permission' => [false];
        yield 'media user denied on the collection' => [true];
    }

    #[DataProvider('provideRestrictedUsers')]
    public function testAttackerCannotReadMedia(bool $hasMediaPermission): void
    {
        $media = $this->createForbiddenMedia($hasMediaPermission);

        $this->client->jsonRequest('GET', '/api/media/' . $media->getId() . '?locale=en', [], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    #[DataProvider('provideRestrictedUsers')]
    public function testGetFormatsIsDenied(bool $hasMediaPermission): void
    {
        $media = $this->createForbiddenMedia($hasMediaPermission);

        $this->client->jsonRequest('GET', '/api/media/' . $media->getId() . '/formats?locale=en', [], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    #[DataProvider('provideRestrictedUsers')]
    public function testPostPreviewIsDenied(bool $hasMediaPermission): void
    {
        $media = $this->createForbiddenMedia($hasMediaPermission);

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '/preview?locale=en',
            [],
            ['previewImage' => $this->createUploadedFile()],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertNull($this->reloadMedia($media)->getPreviewImage());
    }

    #[DataProvider('provideRestrictedUsers')]
    public function testDeletePreviewIsDenied(bool $hasMediaPermission): void
    {
        $media = $this->createForbiddenMedia($hasMediaPermission);

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '/preview?locale=en',
            [],
            ['previewImage' => $this->createUploadedFile()]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest('DELETE', '/api/media/' . $media->getId() . '/preview?locale=en', [], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertNotNull($this->reloadMedia($media)->getPreviewImage());
    }

    #[DataProvider('provideRestrictedUsers')]
    public function testPutFormatIsDenied(bool $hasMediaPermission): void
    {
        $media = $this->createForbiddenMedia($hasMediaPermission);

        $this->client->jsonRequest(
            'PUT',
            '/api/media/' . $media->getId() . '/formats/big-squared?locale=en',
            self::CROP,
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame([], $this->getFormatOptions($media));
    }

    #[DataProvider('provideRestrictedUsers')]
    public function testPutEmptyFormatIsDenied(bool $hasMediaPermission): void
    {
        $media = $this->createForbiddenMedia($hasMediaPermission);
        $this->getContainer()->get('sulu_media.format_options_manager')->save($media->getId(), 'big-squared', self::CROP);
        $this->getEntityManager()->flush();

        $this->client->jsonRequest('PUT', '/api/media/' . $media->getId() . '/formats/big-squared?locale=en', [], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame(self::CROP, $this->getFormatOptions($media));
    }

    #[DataProvider('provideRestrictedUsers')]
    public function testPatchFormatsIsDenied(bool $hasMediaPermission): void
    {
        $media = $this->createForbiddenMedia($hasMediaPermission);

        $this->client->jsonRequest(
            'PATCH',
            '/api/media/' . $media->getId() . '/formats?locale=en',
            ['big-squared' => self::CROP],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame([], $this->getFormatOptions($media));
    }

    public function testMediaUserCanStillEditPreviewAndFormatsInAllowedCollection(): void
    {
        $role = $this->createRole(true);
        $this->createUser('attacker', $role);
        $collectionId = $this->createCollection('Allowed');
        $this->getEntityManager()->flush();

        $media = $this->createMedia($collectionId);

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '/preview?locale=en',
            [],
            ['previewImage' => $this->createUploadedFile()],
            self::AUTH
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest('DELETE', '/api/media/' . $media->getId() . '/preview?locale=en', [], self::AUTH);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest(
            'PUT',
            '/api/media/' . $media->getId() . '/formats/big-squared?locale=en',
            self::CROP,
            self::AUTH
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest('PATCH', '/api/media/' . $media->getId() . '/formats?locale=en', ['big-squared' => []], self::AUTH);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    private function createForbiddenMedia(bool $hasMediaPermission): Media
    {
        $role = $this->createRole($hasMediaPermission);
        $this->createUser('attacker', $role);
        $collectionId = $this->createCollection('Forbidden');
        $this->getEntityManager()->flush();

        $this->getContainer()->get('sulu_security.access_control_manager')->setPermissions(
            Collection::class,
            (string) $collectionId,
            [$role->getId() => ['view' => false, 'add' => false, 'edit' => false, 'delete' => false, 'security' => false]]
        );

        return $this->createMedia($collectionId);
    }

    private function reloadMedia(Media $media): MediaEntity
    {
        $this->getEntityManager()->clear();

        $entity = $this->getEntityManager()->find(MediaEntity::class, $media->getId());
        $this->assertNotNull($entity);

        return $entity;
    }

    /**
     * @return array<string, int>
     */
    private function getFormatOptions(Media $media): array
    {
        $this->getEntityManager()->clear();

        return $this->getContainer()->get('sulu_media.format_options_manager')->get($media->getId(), 'big-squared');
    }

    private function createRole(bool $hasMediaPermission): RoleInterface
    {
        $role = $this->getContainer()->get('sulu.repository.role')->createNew();
        $role->setName('Attacker Role');
        $role->setAnonymous(false);
        $role->setSystem('Sulu');
        $this->getEntityManager()->persist($role);

        if ($hasMediaPermission) {
            $permission = new Permission();
            $permission->setContext(MediaAdmin::SECURITY_CONTEXT);
            $permission->setPermissions(127);
            $permission->setRole($role);
            $role->addPermission($permission);
            $this->getEntityManager()->persist($permission);
        }

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
        return $this->getContainer()->get('sulu_media.collection_manager')->save(
            ['title' => $title, 'locale' => 'en', 'type' => ['id' => 1]],
            1
        )->getId();
    }

    private function createMedia(int $collectionId): Media
    {
        return $this->getContainer()->get('sulu_media.media_manager')->save(
            $this->createUploadedFile(),
            ['title' => 'secret', 'collection' => $collectionId, 'locale' => 'en'],
            null
        );
    }

    private function createUploadedFile(): UploadedFile
    {
        $path = \sys_get_temp_dir() . '/' . \uniqid('media-permission-', true) . '.jpeg';
        \copy(__DIR__ . '/../../Fixtures/files/photo.jpeg', $path);

        return new UploadedFile($path, 'photo.jpeg', 'image/jpeg');
    }
}
