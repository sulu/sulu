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
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search\AdminAccountReindexProvider;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminAccountReindexProviderTest extends SuluTestCase
{
    private EntityManagerInterface $entityManager;
    private AdminAccountReindexProvider $provider;

    private MediaType $imageType;

    protected Collection $collection;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new AdminAccountReindexProvider($this->entityManager);
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
        $this->assertSame('admin', AdminAccountReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        $this->createAccount('Count 1');
        $this->createAccount('Count 2');

        $this->entityManager->flush();

        $this->assertSame(2, $this->provider->total());
    }

    public function testProvideAll(): void
    {
        $account1 = $this->createAccount('Test Account 1', 'Media 1');
        $account2 = $this->createAccount('Test Account 2');

        $this->entityManager->flush();

        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE co_accounts SET changed = :changed WHERE id = :id';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'id' => $account1->getId(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'id' => $account2->getId(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $this->assertSame(
            [
                [
                    'id' => AccountInterface::RESOURCE_KEY . '__' . $account1->getId(),
                    'resourceKey' => AccountInterface::RESOURCE_KEY,
                    'resourceId' => (string) $account1->getId(),
                    'mediaId' => (string) $account1->getLogo()?->getId(),
                    'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $account1->getName(),
                ],
                [
                    'id' => AccountInterface::RESOURCE_KEY . '__' . $account2->getId(),
                    'resourceKey' => AccountInterface::RESOURCE_KEY,
                    'resourceId' => (string) $account2->getId(),
                    'mediaId' => '',
                    'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $account2->getName(),
                ],
            ],
            [...$results],
        );
    }

    public function testProvideWithSpecificIdentifiers(): void
    {
        $account1 = $this->createAccount('Account One');
        $account2 = $this->createAccount('Account Two');
        $account3 = $this->createAccount('Account Three');

        $this->entityManager->flush();

        $identifiers = [
            AccountInterface::RESOURCE_KEY . '__' . $account1->getId(),
            AccountInterface::RESOURCE_KEY . '__' . $account3->getId(),
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Account One', $resultTitles);
        $this->assertContains('Account Three', $resultTitles);
        $this->assertNotContains('Account Two', $resultTitles);
    }

    private function createAccount(string $name, ?string $mediaName = null): Account
    {
        $account = new Account();
        $account->setName($name);
        $account->setCreated(new \DateTimeImmutable('2000-01-01 12:00:00'));

        if ($mediaName) {
            $media = $this->createMedia($mediaName);
            $account->setLogo($media);
        }

        $this->entityManager->persist($account);

        return $account;
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
