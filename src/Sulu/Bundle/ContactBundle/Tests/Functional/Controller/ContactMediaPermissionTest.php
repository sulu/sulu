<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Controller;

use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * The media endpoints of contacts and accounts must be secured by the security context of the entity they belong to.
 */
class ContactMediaPermissionTest extends SuluTestCase
{
    private KernelBrowser $client;

    private Contact $contact;

    private Account $account;

    private Media $media;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();

        (new LoadCollectionTypes())->load($this->getEntityManager());
        $this->getEntityManager()->flush();
        $this->client->getContainer()->get('sulu_media.system_collections.manager')->warmUp();

        $this->contact = new Contact();
        $this->contact->setFirstName('Max');
        $this->contact->setLastName('Mustermann');
        $this->getEntityManager()->persist($this->contact);

        $this->account = new Account();
        $this->account->setName('Company');
        $this->getEntityManager()->persist($this->account);

        $this->media = $this->createMedia();

        $this->createUser('people', ContactAdmin::CONTACT_SECURITY_CONTEXT);
        $this->createUser('organizations', ContactAdmin::ACCOUNT_SECURITY_CONTEXT);

        $this->getEntityManager()->flush();
    }

    public function testContactMediaRequiresContactPermission(): void
    {
        $url = '/api/contacts/' . $this->contact->getId() . '/medias?locale=en';

        $this->client->jsonRequest('GET', $url, [], $this->auth('organizations'));
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $this->client->jsonRequest('GET', $url, [], $this->auth('people'));
        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testAccountMediaRequiresAccountPermission(): void
    {
        $url = '/api/accounts/' . $this->account->getId() . '/medias?locale=en';

        $this->client->jsonRequest('GET', $url, [], $this->auth('people'));
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $this->client->jsonRequest('GET', $url, [], $this->auth('organizations'));
        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testAddAndRemoveMediaRequireTheEntitiesPermission(): void
    {
        $contactUrl = '/api/contacts/' . $this->contact->getId() . '/medias';
        $accountUrl = '/api/accounts/' . $this->account->getId() . '/medias';
        $mediaId = $this->media->getId();

        $this->client->jsonRequest('POST', $contactUrl, ['mediaId' => $mediaId], $this->auth('organizations'));
        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame(0, $this->countContactMedias());

        $this->client->jsonRequest('POST', $accountUrl, ['mediaId' => $mediaId], $this->auth('people'));
        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame(0, $this->countAccountMedias());

        $connection = $this->getEntityManager()->getConnection();
        $connection->insert('co_contact_medias', ['idContacts' => $this->contact->getId(), 'idMedias' => $mediaId]);
        $connection->insert('co_account_medias', ['idAccounts' => $this->account->getId(), 'idMedias' => $mediaId]);

        $this->client->jsonRequest('DELETE', $contactUrl . '/' . $mediaId, [], $this->auth('organizations'));
        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame(1, $this->countContactMedias());

        $this->client->jsonRequest('DELETE', $accountUrl . '/' . $mediaId, [], $this->auth('people'));
        $this->assertHttpStatusCode(403, $this->client->getResponse());
        $this->assertSame(1, $this->countAccountMedias());
    }

    public function testUserWithContactPermissionCanAddAndRemoveContactMedia(): void
    {
        $url = '/api/contacts/' . $this->contact->getId() . '/medias';

        $this->client->jsonRequest('POST', $url, ['mediaId' => $this->media->getId()], $this->auth('people'));
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame(1, $this->countContactMedias());

        $this->client->jsonRequest('DELETE', $url . '/' . $this->media->getId(), [], $this->auth('people'));
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertSame(0, $this->countContactMedias());
    }

    public function testUserWithAccountPermissionCanAddAndRemoveAccountMedia(): void
    {
        $url = '/api/accounts/' . $this->account->getId() . '/medias';

        $this->client->jsonRequest('POST', $url, ['mediaId' => $this->media->getId()], $this->auth('organizations'));
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertSame(1, $this->countAccountMedias());

        $this->client->jsonRequest('DELETE', $url . '/' . $this->media->getId(), [], $this->auth('organizations'));
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertSame(0, $this->countAccountMedias());
    }

    private function countContactMedias(): int
    {
        $count = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM co_contact_medias WHERE idContacts = ?',
            [$this->contact->getId()]
        );
        $this->assertIsNumeric($count);

        return (int) $count;
    }

    private function countAccountMedias(): int
    {
        $count = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM co_account_medias WHERE idAccounts = ?',
            [$this->account->getId()]
        );
        $this->assertIsNumeric($count);

        return (int) $count;
    }

    private function createMedia(): Media
    {
        $collection = new Collection();
        $collectionType = $this->getEntityManager()->getReference(CollectionType::class, 1);
        $this->assertNotNull($collectionType);
        $collection->setType($collectionType);

        $file = new File();
        $file->setVersion(1);

        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('photo.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(111111);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1950-04-20'));
        $fileVersion->setCreated(new \DateTime('1950-04-20'));
        $file->addFileVersion($fileVersion);

        $media = new Media();
        $media->setType(MediaInterface::TYPE_IMAGE);
        $media->setCollection($collection);
        $media->addFile($file);
        $file->setMedia($media);

        $entityManager = $this->getEntityManager();
        foreach ([$collection, $fileVersion, $media, $file] as $entity) {
            $entityManager->persist($entity);
        }

        return $media;
    }

    /**
     * @return array{PHP_AUTH_USER: string, PHP_AUTH_PW: string}
     */
    private function auth(string $username): array
    {
        return ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => $username];
    }

    private function createUser(string $username, string $grantedContext): void
    {
        $role = $this->createRole($username, $grantedContext);

        $contact = new Contact();
        $contact->setFirstName($username);
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
    }

    private function createRole(string $name, string $grantedContext): RoleInterface
    {
        $role = $this->getContainer()->get('sulu.repository.role')->createNew();
        $role->setName($name);
        $role->setAnonymous(false);
        $role->setSystem('Sulu');
        $this->getEntityManager()->persist($role);

        foreach ([ContactAdmin::CONTACT_SECURITY_CONTEXT, ContactAdmin::ACCOUNT_SECURITY_CONTEXT] as $context) {
            $permission = new Permission();
            $permission->setContext($context);
            $permission->setPermissions($context === $grantedContext ? 127 : 0);
            $permission->setRole($role);
            $role->addPermission($permission);
            $this->getEntityManager()->persist($permission);
        }

        return $role;
    }
}
