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
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * A user with rights on collection A but no rights on collection B must not be able to change a media in B by naming
 * A in the request, because the central security listener only checks the collection given in the request.
 */
class MediaControllerCollectionPermissionTest extends SuluTestCase
{
    private const AUTH = ['PHP_AUTH_USER' => 'attacker', 'PHP_AUTH_PW' => 'attacker'];

    private KernelBrowser $client;

    private int $allowedCollectionId;

    private int $forbiddenCollectionId;

    private int $viewOnlyCollectionId;

    private int $editOnlyCollectionId;

    private int $viewAddCollectionId;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();

        (new LoadCollectionTypes())->load($this->getEntityManager());
        (new LoadMediaTypes())->load($this->getEntityManager());

        $role = $this->createRole();
        $this->createUser('attacker', $role);
        $this->allowedCollectionId = $this->createCollection('Allowed');
        $this->forbiddenCollectionId = $this->createCollection('Forbidden');
        $this->viewOnlyCollectionId = $this->createCollection('View only');
        $this->editOnlyCollectionId = $this->createCollection('Edit only');
        $this->viewAddCollectionId = $this->createCollection('View and add');
        $this->getEntityManager()->flush();

        $accessControlManager = $this->getContainer()->get('sulu_security.access_control_manager');
        $accessControlManager->setPermissions(Collection::class, (string) $this->allowedCollectionId, [
            $role->getId() => ['view' => true, 'add' => true, 'edit' => true, 'delete' => true, 'security' => false],
        ]);
        $accessControlManager->setPermissions(Collection::class, (string) $this->viewOnlyCollectionId, [
            $role->getId() => ['view' => true, 'add' => false, 'edit' => false, 'delete' => false, 'security' => false],
        ]);
        $accessControlManager->setPermissions(Collection::class, (string) $this->editOnlyCollectionId, [
            $role->getId() => ['view' => true, 'add' => false, 'edit' => true, 'delete' => false, 'security' => false],
        ]);
        $accessControlManager->setPermissions(Collection::class, (string) $this->viewAddCollectionId, [
            $role->getId() => ['view' => true, 'add' => true, 'edit' => false, 'delete' => false, 'security' => false],
        ]);
        $accessControlManager->setPermissions(Collection::class, (string) $this->forbiddenCollectionId, [
            $role->getId() => ['view' => false, 'add' => false, 'edit' => false, 'delete' => false, 'security' => false],
        ]);
    }

    public function testUpdateCannotChangeMediaInForbiddenCollection(): void
    {
        $media = $this->createMedia('secret', $this->forbiddenCollectionId);

        $this->client->jsonRequest(
            'PUT',
            \sprintf('/api/media/%s?locale=en&collection=%s', $media->getId(), $this->allowedCollectionId),
            ['title' => 'changed'],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $reloaded = $this->reloadMedia($media->getId());
        $this->assertSame($this->forbiddenCollectionId, $reloaded->getCollection()->getId());
        $this->assertSame('secret', $this->getTitle($media->getId()));
    }

    public function testNewVersionCannotReplaceFileOfMediaInForbiddenCollection(): void
    {
        $media = $this->createMedia('secret', $this->forbiddenCollectionId);

        $this->client->request(
            'POST',
            \sprintf('/api/media/%s?action=new-version&locale=en&collection=%s', $media->getId(), $this->allowedCollectionId),
            [],
            ['fileVersion' => $this->createUploadedFile()],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $reloaded = $this->reloadMedia($media->getId());
        $this->assertSame($this->forbiddenCollectionId, $reloaded->getCollection()->getId());
        $this->assertSame(1, $this->getFile($media->getId())->getVersion());
    }

    public function testUpdateCannotMoveMediaIntoForbiddenCollection(): void
    {
        $media = $this->createMedia('own', $this->allowedCollectionId);

        $this->client->jsonRequest(
            'PUT',
            \sprintf('/api/media/%s?locale=en', $media->getId()),
            ['title' => 'own', 'collection' => $this->forbiddenCollectionId],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame($this->allowedCollectionId, $this->reloadMedia($media->getId())->getCollection()->getId());
    }

    public function testUpdateMediaInAllowedCollection(): void
    {
        $media = $this->createMedia('own', $this->allowedCollectionId);

        $this->client->jsonRequest(
            'PUT',
            \sprintf('/api/media/%s?locale=en&collection=%s', $media->getId(), $this->allowedCollectionId),
            ['title' => 'changed'],
            self::AUTH
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame('changed', $this->getTitle($media->getId()));
    }

    public function testDeleteVersionCannotRemoveVersionOfMediaInForbiddenCollection(): void
    {
        $media = $this->createMedia('secret', $this->forbiddenCollectionId);
        $this->addVersion($media);

        $this->client->jsonRequest(
            'DELETE',
            \sprintf('/api/media/%s/versions/1?collection=%s', $media->getId(), $this->allowedCollectionId),
            [],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertCount(2, $this->getFile($media->getId())->getFileVersions());
    }

    public function testDeleteVersionOfMediaInAllowedCollection(): void
    {
        $media = $this->createMedia('own', $this->allowedCollectionId);
        $this->addVersion($media);

        $this->client->jsonRequest(
            'DELETE',
            \sprintf('/api/media/%s/versions/1?collection=%s', $media->getId(), $this->allowedCollectionId),
            [],
            self::AUTH
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertCount(1, $this->getFile($media->getId())->getFileVersions());
    }

    public function testUpdateRequiresEditPermissionOnTheMediasCollection(): void
    {
        $media = $this->createMedia('read only', $this->viewOnlyCollectionId);

        $this->client->jsonRequest(
            'PUT',
            \sprintf('/api/media/%s?locale=en&collection=%s', $media->getId(), $this->allowedCollectionId),
            ['title' => 'changed'],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame('read only', $this->getTitle($media->getId()));
    }

    public function testNewVersionOfMediaInAllowedCollection(): void
    {
        $media = $this->createMedia('own', $this->allowedCollectionId);

        $this->client->request(
            'POST',
            \sprintf('/api/media/%s?action=new-version&locale=en&collection=%s', $media->getId(), $this->allowedCollectionId),
            [],
            ['fileVersion' => $this->createUploadedFile()],
            self::AUTH
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame(2, $this->getFile($media->getId())->getVersion());
    }

    public function testDeleteVersionRequiresDeletePermissionOnTheMediasCollection(): void
    {
        $media = $this->createMedia('read only', $this->viewOnlyCollectionId);
        $this->addVersion($media);

        $this->client->jsonRequest(
            'DELETE',
            \sprintf('/api/media/%s/versions/1?collection=%s', $media->getId(), $this->allowedCollectionId),
            [],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertCount(2, $this->getFile($media->getId())->getFileVersions());
    }

    public function testRequestsWithoutCollectionAreCheckedAgainstTheMediasCollection(): void
    {
        $media = $this->createMedia('secret', $this->forbiddenCollectionId);
        $this->addVersion($media);

        $this->client->jsonRequest('PUT', \sprintf('/api/media/%s?locale=en', $media->getId()), ['title' => 'changed'], self::AUTH);
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $this->client->request(
            'POST',
            \sprintf('/api/media/%s?action=new-version&locale=en', $media->getId()),
            [],
            ['fileVersion' => $this->createUploadedFile()],
            self::AUTH
        );
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $this->client->jsonRequest('DELETE', \sprintf('/api/media/%s/versions/1', $media->getId()), [], self::AUTH);
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $this->assertSame('secret', $this->getTitle($media->getId()));
        $this->assertSame(2, $this->getFile($media->getId())->getVersion());
        $this->assertCount(2, $this->getFile($media->getId())->getFileVersions());
    }

    public function testDeleteVersionNeedsDeleteAndNotOnlyEditPermission(): void
    {
        $media = $this->createMedia('edit only', $this->editOnlyCollectionId);
        $this->addVersion($media);

        $this->client->jsonRequest('DELETE', \sprintf('/api/media/%s/versions/1', $media->getId()), [], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertCount(2, $this->getFile($media->getId())->getFileVersions());

        $this->client->jsonRequest('PUT', \sprintf('/api/media/%s?locale=en', $media->getId()), ['title' => 'changed'], self::AUTH);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame('changed', $this->getTitle($media->getId()));
    }

    public function testPreviewImageOfMediaInForbiddenCollectionCannotBeChanged(): void
    {
        $previewImageId = $this->createPreviewImage($this->createMedia('secret', $this->forbiddenCollectionId));
        $systemCollectionId = $this->reloadMedia($previewImageId)->getCollection()->getId();

        $this->client->jsonRequest(
            'PUT',
            \sprintf('/api/media/%s?locale=en', $previewImageId),
            ['title' => 'moved', 'collection' => $this->allowedCollectionId],
            self::AUTH
        );
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $this->client->request(
            'POST',
            \sprintf('/api/media/%s?action=new-version&locale=en', $previewImageId),
            [],
            ['fileVersion' => $this->createUploadedFile()],
            self::AUTH
        );
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $this->assertSame($systemCollectionId, $this->reloadMedia($previewImageId)->getCollection()->getId());
        $this->assertSame(1, $this->getFile($previewImageId)->getVersion());
    }

    public function testPreviewImageOfMediaInAllowedCollectionCanBeChanged(): void
    {
        $previewImageId = $this->createPreviewImage($this->createMedia('own', $this->allowedCollectionId));

        $this->client->jsonRequest('PUT', \sprintf('/api/media/%s?locale=en', $previewImageId), ['title' => 'changed'], self::AUTH);

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame('changed', $this->getTitle($previewImageId));
    }

    public function testDeleteVersionNeedsDeleteAndNotOnlyAddPermission(): void
    {
        $media = $this->createMedia('view and add', $this->viewAddCollectionId);
        $this->addVersion($media);

        $this->client->jsonRequest('DELETE', \sprintf('/api/media/%s/versions/1', $media->getId()), [], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertCount(2, $this->getFile($media->getId())->getFileVersions());
    }

    public function testDeleteCannotRemoveMediaInForbiddenCollection(): void
    {
        $media = $this->createMedia('secret', $this->forbiddenCollectionId);

        $this->client->jsonRequest('DELETE', \sprintf('/api/media/%s', $media->getId()), [], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertNotNull($this->getEntityManager()->find(MediaEntity::class, $media->getId()));
    }

    public function testPreviewImageOfMediaInViewOnlyCollectionCannotBeChanged(): void
    {
        $previewImageId = $this->createPreviewImage($this->createMedia('read only', $this->viewOnlyCollectionId));

        $this->client->jsonRequest('PUT', \sprintf('/api/media/%s?locale=en', $previewImageId), ['title' => 'changed'], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    public function testPreviewImageOfMediaInForbiddenCollectionCannotBeMoved(): void
    {
        $previewImageId = $this->createPreviewImage($this->createMedia('secret', $this->forbiddenCollectionId));
        $systemCollectionId = $this->reloadMedia($previewImageId)->getCollection()->getId();

        $this->client->jsonRequest(
            'POST',
            \sprintf('/api/media/%s?action=move&locale=en&destination=%s', $previewImageId, $this->allowedCollectionId),
            [],
            self::AUTH
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame($systemCollectionId, $this->reloadMedia($previewImageId)->getCollection()->getId());
    }

    public function testDeleteMissingMediaAnswersNotFound(): void
    {
        $this->client->jsonRequest('DELETE', '/api/media/999999', [], self::AUTH);

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
        $this->assertSame(0, $content['code'] ?? null);
        $this->assertIsString($content['message'] ?? null);
        $this->assertStringContainsString('999999', $content['message']);
    }

    public function testPreviewImageOfMediaInForbiddenCollectionCannotBeDeleted(): void
    {
        $previewImageId = $this->createPreviewImage($this->createMedia('secret', $this->forbiddenCollectionId));

        $this->client->jsonRequest('DELETE', \sprintf('/api/media/%s', $previewImageId), [], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertNotNull($this->getEntityManager()->find(MediaEntity::class, $previewImageId));
    }

    public function testDeleteDoesNotListTheReferencesOfMediaInForbiddenCollection(): void
    {
        $media = $this->createMedia('secret', $this->forbiddenCollectionId);
        $this->createMediaReference((int) $media->getId(), 'Hidden page');

        $this->client->jsonRequest('DELETE', \sprintf('/api/media/%s?locale=en', $media->getId()), [], self::AUTH);

        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertStringNotContainsString('Hidden page', (string) $this->client->getResponse()->getContent());
        $this->assertNotNull($this->getEntityManager()->find(MediaEntity::class, $media->getId()));
    }

    public function testDeleteListsTheReferencesOfMediaInAllowedCollection(): void
    {
        $media = $this->createMedia('photo', $this->allowedCollectionId);
        $this->createMediaReference((int) $media->getId(), 'Referencing page');

        $this->client->jsonRequest('DELETE', \sprintf('/api/media/%s?locale=en', $media->getId()), [], self::AUTH);

        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertStringContainsString('Referencing page', (string) $this->client->getResponse()->getContent());
        $this->assertNotNull($this->getEntityManager()->find(MediaEntity::class, $media->getId()));
    }

    private function createPreviewImage(Media $owner): int
    {
        $this->getContainer()->get('sulu_media.system_collections.cache')->invalidate();

        $this->client->request(
            'POST',
            \sprintf('/api/media/%s/preview?locale=en', $owner->getId()),
            [],
            ['previewImage' => $this->createUploadedFile()]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $previewImage = $this->reloadMedia($owner->getId())->getPreviewImage();
        $this->assertNotNull($previewImage);

        return $previewImage->getId();
    }

    private function createMediaReference(int $mediaId, string $title): void
    {
        /** @var ReferenceRepositoryInterface $referenceRepository */
        $referenceRepository = $this->getContainer()->get('sulu_reference.reference_repository');

        $referenceRepository->add($referenceRepository->create(
            MediaInterface::RESOURCE_KEY,
            (string) $mediaId,
            'pages',
            'page-uuid-1',
            'en',
            $title,
            'default',
            'image'
        ));
        $referenceRepository->flush();
    }

    private function reloadMedia(int $id): MediaEntity
    {
        $this->getEntityManager()->clear();
        $media = $this->getEntityManager()->find(MediaEntity::class, $id);
        $this->assertNotNull($media);

        return $media;
    }

    private function getFile(int $id): File
    {
        $file = $this->reloadMedia($id)->getFiles()[0] ?? null;
        $this->assertNotNull($file);

        return $file;
    }

    private function getTitle(int $id): ?string
    {
        return $this->getFile($id)->getLatestFileVersion()?->getMeta()[0]?->getTitle();
    }

    private function createRole(): RoleInterface
    {
        $role = $this->getContainer()->get('sulu.repository.role')->createNew();
        $role->setName('Attacker Role');
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
        $path = \sys_get_temp_dir() . '/' . \uniqid('media-permission-', true) . '.jpeg';
        \copy($source, $path);

        return new UploadedFile($path, 'photo.jpeg', 'image/jpeg');
    }
}
