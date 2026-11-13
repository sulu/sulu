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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaValidityControllerTest extends SuluTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private Collection $collection;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->entityManager = $this->getEntityManager();

        (new LoadCollectionTypes())->load($this->entityManager);
        $collectionType = $this->entityManager->find(CollectionType::class, 1);
        self::assertInstanceOf(CollectionType::class, $collectionType);
        $this->collection = new Collection();
        $this->collection->setType($collectionType);
        $this->entityManager->persist($this->collection);
        $this->entityManager->flush();
    }

    public function testGetWithoutValidity(): void
    {
        $media = $this->createDocument();

        $this->client->jsonRequest('GET', '/api/media/' . $media->getId() . '?locale=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $data = $this->getResponseData();
        self::assertNull($data['validFrom']);
        self::assertNull($data['validTo']);
    }

    public function testPutStoresAndClearsTheValidity(): void
    {
        $media = $this->createDocument();

        $this->putValidity($media, ['validFrom' => '2026-01-15', 'validTo' => '2026-12-31']);
        $data = $this->getResponseData();
        self::assertSame('2026-01-15', $this->getDate($data['validFrom']));
        self::assertSame('2026-12-31', $this->getDate($data['validTo']));

        $this->putValidity($media, ['validFrom' => null, 'validTo' => '2026-12-31']);
        $data = $this->getResponseData();
        self::assertNull($data['validFrom']);
        self::assertSame('2026-12-31', $this->getDate($data['validTo']));

        $this->putValidity($media, ['validFrom' => '2026-02-01', 'validTo' => null]);
        $data = $this->getResponseData();
        self::assertSame('2026-02-01', $this->getDate($data['validFrom']));
        self::assertNull($data['validTo']);

        $this->client->jsonRequest('GET', '/api/media/' . $media->getId() . '?locale=en');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $data = $this->getResponseData();
        self::assertSame('2026-02-01', $this->getDate($data['validFrom']));
        self::assertNull($data['validTo']);
    }

    public function testPutRejectsAnEndBeforeTheStart(): void
    {
        $media = $this->createDocument();
        $this->putValidity($media, ['validFrom' => '2026-05-01', 'validTo' => '2026-12-31']);

        $this->client->jsonRequest(
            'PUT',
            '/api/media/' . $media->getId() . '?locale=en',
            ['collection' => $this->collection->getId(), 'title' => 'datasheet', 'validTo' => '2026-04-30']
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $data = $this->getResponseData();
        self::assertSame(5030, $data['code']);
        self::assertSame('The validity end cannot be before the validity start.', $data['detail']);

        $this->entityManager->clear();
        $file = $this->entityManager->find(Media::class, $media->getId())?->getFiles()[0];
        self::assertInstanceOf(File::class, $file);
        $fileVersion = $file->getFileVersion(1);
        self::assertInstanceOf(FileVersion::class, $fileVersion);
        self::assertSame('2026-05-01', $fileVersion->getValidFrom()?->format('Y-m-d'));
        self::assertSame('2026-12-31', $fileVersion->getValidTo()?->format('Y-m-d'));
    }

    #[TestWith(['not-a-date'])]
    #[TestWith([20260115])]
    #[TestWith([true])]
    #[TestWith([['x']])]
    #[TestWith(['2025-12-31T23:00:00Z'])]
    #[TestWith(['2026-01-01T25:99'])]
    public function testPutRejectsAnInvalidDate(mixed $validFrom): void
    {
        $media = $this->createDocument();
        $this->putValidity($media, ['validFrom' => '2026-01-15']);

        $this->client->jsonRequest(
            'PUT',
            '/api/media/' . $media->getId() . '?locale=en',
            ['collection' => $this->collection->getId(), 'title' => 'datasheet', 'validFrom' => $validFrom]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $data = $this->getResponseData();
        self::assertSame(5031, $data['code']);
        self::assertSame('The validity start and end must be valid dates.', $data['detail']);

        $this->entityManager->clear();
        $file = $this->entityManager->find(Media::class, $media->getId())?->getFiles()[0];
        self::assertInstanceOf(File::class, $file);
        $fileVersion = $file->getFileVersion(1);
        self::assertInstanceOf(FileVersion::class, $fileVersion);
        self::assertSame('2026-01-15', $fileVersion->getValidFrom()?->format('Y-m-d'));
    }

    public function testNewVersionKeepsTheValidity(): void
    {
        $media = $this->createDocument();
        $this->putValidity($media, ['validFrom' => '2026-01-15', 'validTo' => '2026-12-31']);

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '?locale=en&action=new-version',
            [],
            ['fileVersion' => new UploadedFile(__DIR__ . '/../../Fixtures/files/small.txt', 'small.txt', 'text/plain')]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->entityManager->clear();
        $file = $this->entityManager->find(Media::class, $media->getId())?->getFiles()[0];
        self::assertInstanceOf(File::class, $file);
        $newVersion = $file->getFileVersion(2);
        self::assertInstanceOf(FileVersion::class, $newVersion);
        self::assertSame('2026-01-15', $newVersion->getValidFrom()?->format('Y-m-d'));
        self::assertSame('2026-12-31', $newVersion->getValidTo()?->format('Y-m-d'));
    }

    private function createDocument(): MediaInterface
    {
        $media = new Media();
        $media->setType(MediaInterface::TYPE_DOCUMENT);
        $media->setCollection($this->collection);

        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);
        $media->addFile($file);

        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('datasheet.txt');
        $fileVersion->setMimeType('text/plain');
        $fileVersion->setSize(10);
        $fileVersion->setStorageOptions(['segment' => '1', 'fileName' => 'datasheet.txt']);
        $fileVersion->setFile($file);
        $file->addFileVersion($fileVersion);

        $meta = new FileVersionMeta();
        $meta->setLocale('en');
        $meta->setTitle('datasheet');
        $meta->setFileVersion($fileVersion);
        $fileVersion->addMeta($meta);
        $fileVersion->setDefaultMeta($meta);

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    /**
     * @param array<string, string|null> $validity
     */
    private function putValidity(MediaInterface $media, array $validity): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/media/' . $media->getId() . '?locale=en',
            ['collection' => $this->collection->getId(), 'title' => 'datasheet', ...$validity]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    private function getDate(mixed $value): string
    {
        self::assertIsString($value);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $value);

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function getResponseData(): array
    {
        /** @var array<string, mixed> $data */
        $data = \json_decode((string) $this->client->getResponse()->getContent(), true);

        return $data;
    }
}
