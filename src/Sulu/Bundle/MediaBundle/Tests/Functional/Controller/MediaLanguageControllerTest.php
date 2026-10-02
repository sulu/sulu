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

class MediaLanguageControllerTest extends SuluTestCase
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

    public function testPutStoresTheMediaLanguages(): void
    {
        $media = $this->createDocument('datasheet', ['de']);

        $this->putMediaLanguages($media, ['de', 'fr']);
        self::assertSame(['de', 'fr'], $this->getResponseData()['mediaLanguages']);

        $this->putMediaLanguages($media, ['fr']);
        self::assertSame(['fr'], $this->getResponseData()['mediaLanguages']);

        $this->client->jsonRequest('GET', '/api/media/' . $media->getId() . '?locale=en');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        self::assertSame(['fr'], $this->getResponseData()['mediaLanguages']);
    }

    public function testNewVersionAppliesTheSentMediaLanguages(): void
    {
        $media = $this->createDocument('datasheet', ['de', 'en']);

        $this->client->request(
            'POST',
            '/api/media/' . $media->getId() . '?locale=en&action=new-version',
            ['mediaLanguages' => ['en']],
            ['fileVersion' => new UploadedFile(__DIR__ . '/../../Fixtures/files/small.txt', 'small.txt', 'text/plain')]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        self::assertSame(['en'], $this->getResponseData()['mediaLanguages']);

        $this->entityManager->clear();
        $file = $this->entityManager->find(Media::class, $media->getId())?->getFiles()[0];
        self::assertInstanceOf(File::class, $file);
        self::assertSame(['en'], $file->getFileVersion(2)?->getMediaLanguages(), 'The removed language must not be carried over to the new version.');
        self::assertSame(['de', 'en'], $file->getFileVersion(1)?->getMediaLanguages());
    }

    public function testCgetFiltersByMediaLanguage(): void
    {
        $this->createDocument('german', ['de']);
        $this->createDocument('french', ['fr']);
        $this->createDocument('none', []);
        $this->createDocument('both', ['de', 'fr']);

        self::assertSame(['both.txt', 'german.txt'], $this->getFilteredNames('de'));
        self::assertSame(['none.txt'], $this->getFilteredNames('_none'));
        self::assertSame(['both.txt', 'french.txt', 'none.txt'], $this->getFilteredNames('fr,_none'));
    }

    /**
     * @param string[] $mediaLanguages
     */
    private function createDocument(string $name, array $mediaLanguages): MediaInterface
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
        $fileVersion->setName($name . '.txt');
        $fileVersion->setMimeType('text/plain');
        $fileVersion->setSize(10);
        $fileVersion->setStorageOptions(['segment' => '1', 'fileName' => $name . '.txt']);
        $fileVersion->setMediaLanguages($mediaLanguages);
        $fileVersion->setFile($file);
        $file->addFileVersion($fileVersion);

        $meta = new FileVersionMeta();
        $meta->setLocale('en');
        $meta->setTitle($name);
        $meta->setFileVersion($fileVersion);
        $fileVersion->addMeta($meta);
        $fileVersion->setDefaultMeta($meta);

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    /**
     * @param string[] $mediaLanguages
     */
    private function putMediaLanguages(MediaInterface $media, array $mediaLanguages): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/media/' . $media->getId() . '?locale=en',
            ['collection' => $this->collection->getId(), 'title' => 'datasheet', 'mediaLanguages' => $mediaLanguages]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    /**
     * @return string[]
     */
    private function getFilteredNames(string $filter): array
    {
        $this->client->jsonRequest('GET', '/api/media?locale=en&fields=id,name&filter[mediaLanguage]=' . $filter);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{_embedded: array{media: array<array{name: string}>}} $data */
        $data = $this->getResponseData();
        $names = \array_column($data['_embedded']['media'], 'name');
        \sort($names);

        return $names;
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
