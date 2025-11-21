<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search\AdminContactReindexProvider;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminContactReindexProviderTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;

    private EntityManagerInterface $entityManager;
    private AdminContactReindexProvider $provider;

    private MediaType $imageType;

    protected Collection $collection;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new AdminContactReindexProvider($this->entityManager);
        $this->purgeDatabase();

        $this->imageType = new MediaType();
        $this->imageType->setName('image');
        $this->imageType->setDescription('This is an image');

        $this->collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');

        $this->collection->setType($collectionType);

        $this->entityManager->persist($this->imageType);
        $this->entityManager->persist($collectionType);
        $this->entityManager->persist($this->collection);

        $this->entityManager->flush();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', AdminContactReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        $this->createContact();
        $this->createContact();

        $this->entityManager->flush();

        $this->assertSame(2, $this->provider->total());
    }

    public function testProvideAll(): void
    {
        $contact1 = $this->createContact('Tom', 'Turbo', 'avatar1');
        $contact2 = $this->createContact();

        $this->entityManager->flush();

        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE co_contacts SET changed = :changed WHERE id = :id';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'id' => $contact1->getId(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'id' => $contact2->getId(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $this->assertSame(
            [
                [
                    'id' => ContactInterface::RESOURCE_KEY . '__' . $contact1->getId(),
                    'resourceKey' => ContactInterface::RESOURCE_KEY,
                    'resourceId' => (string) $contact1->getId(),
                    'mediaId' => (string) $contact1->getAvatar()?->getId(),
                    'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $contact1->getFullName(),
                    'securityContext' => ContactAdmin::CONTACT_SECURITY_CONTEXT,
                ],
                [
                    'id' => ContactInterface::RESOURCE_KEY . '__' . $contact2->getId(),
                    'resourceKey' => ContactInterface::RESOURCE_KEY,
                    'resourceId' => (string) $contact2->getId(),
                    'mediaId' => '',
                    'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $contact2->getFullName(),
                    'securityContext' => ContactAdmin::CONTACT_SECURITY_CONTEXT,
                ],
            ],
            [...$results],
        );
    }

    public function testProvideWithSpecificIdentifiers(): void
    {
        $contact1 = $this->createContact();
        $contact2 = $this->createContact('Fritz', 'Fantom');
        $contact3 = $this->createContact('Thomas', 'Brezina');

        $this->entityManager->flush();

        $identifiers = [
            ContactInterface::RESOURCE_KEY . '__' . $contact1->getId(),
            ContactInterface::RESOURCE_KEY . '__' . $contact3->getId(),
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Tom Turbo', $resultTitles);
        $this->assertContains('Thomas Brezina', $resultTitles);
        $this->assertNotContains('Fritz Fantom', $resultTitles);
    }

    private function createContact(string $firstName = 'Tom', string $lastName = 'Turbo', ?string $avatarName = null): Contact
    {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setCreated(new \DateTimeImmutable('2000-01-01 12:00:00'));

        if (null !== $avatarName) {
            $media = $this->createMedia($avatarName);
            $contact->setAvatar($media);
        }

        $this->entityManager->persist($contact);

        return $contact;
    }

    private function createMedia(string $name): Media
    {
        $file = new File();
        $file->setVersion(1);

        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName($name);
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(111111);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTimeImmutable('1950-04-20'));
        $fileVersion->setCreated(new \DateTimeImmutable('1950-04-20'));
        $file->addFileVersion($fileVersion);
        $this->entityManager->persist($fileVersion);

        $media = new Media();
        $media->setType($this->imageType);
        $media->setCollection($this->collection);
        $media->addFile($file);
        $file->setMedia($media);
        $this->entityManager->persist($media);
        $this->entityManager->persist($file);

        return $media;
    }
}
