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
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class SmartContentItemControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();

        $this->purgeDatabase();
    }

    public function testGetItems(): void
    {
        $collection = $this->createCollection('Test');
        $type = $this->createType('image');
        $media2 = $this->createMedia('media-2', $collection, 'image/jpeg', $type);
        $media1 = $this->createMedia('media-1', $collection, 'image/jpeg', $type);
        $media4 = $this->createMedia('media-4', $collection, 'image/jpeg', $type);
        $media3 = $this->createMedia('media-3', $collection, 'image/jpeg', $type);

        $this->em->persist($collection);
        $this->em->persist($media2);
        $this->em->persist($media1);
        $this->em->persist($media4);
        $this->em->persist($media3);
        $this->em->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/items?provider=media&locale=en&dataSource=' . $collection->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(4, $result['_embedded']['items']);
        $this->assertEquals($media2->getId(), $result['_embedded']['items'][0]['id']);
        $this->assertEquals($media1->getId(), $result['_embedded']['items'][1]['id']);
        $this->assertEquals($media4->getId(), $result['_embedded']['items'][2]['id']);
        $this->assertEquals($media3->getId(), $result['_embedded']['items'][3]['id']);
    }

    public function testGetItemsSorted(): void
    {
        $collection = $this->createCollection('Test');
        $type = $this->createType('image');
        $media2 = $this->createMedia('media-2', $collection, 'image/jpeg', $type);
        $media1 = $this->createMedia('media-1', $collection, 'image/jpeg', $type);
        $media4 = $this->createMedia('media-4', $collection, 'image/jpeg', $type);
        $media3 = $this->createMedia('media-3', $collection, 'image/jpeg', $type);

        $this->em->persist($collection);
        $this->em->persist($media2);
        $this->em->persist($media1);
        $this->em->persist($media4);
        $this->em->persist($media3);
        $this->em->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/items?provider=media&sortBy=fileVersionMeta.title&locale=en&dataSource=' . $collection->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(4, $result['_embedded']['items']);
        $this->assertEquals($media1->getId(), $result['_embedded']['items'][0]['id']);
        $this->assertEquals($media2->getId(), $result['_embedded']['items'][1]['id']);
        $this->assertEquals($media3->getId(), $result['_embedded']['items'][2]['id']);
        $this->assertEquals($media4->getId(), $result['_embedded']['items'][3]['id']);
    }

    private function createType(string $name): MediaType
    {
        $type = new MediaType();
        $type->setName($name);

        $this->em->persist($type);

        return $type;
    }

    private function createMedia(string $title, Collection $collection, string $mimeType, MediaType $type, string $locale = 'en'): Media
    {
        $media = new Media();
        $media->setType($type);
        $file = new File();
        $fileVersion = new FileVersion();
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setTitle($title);
        $fileVersionMeta->setLocale($locale);
        $fileVersionMeta->setFileVersion($fileVersion);
        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setVersion(1);
        $fileVersion->setName($title);
        $fileVersion->setSize(0);
        $fileVersion->setMimeType($mimeType);
        $fileVersion->setFile($file);
        $file->setVersion(1);
        $file->addFileVersion($fileVersion);
        $file->setMedia($media);
        $media->addFile($file);
        $media->setCollection($collection);

        $this->em->persist($media);

        return $media;
    }

    public function testGetItemsWithLocaleParameterOverridesRequestLocale(): void
    {
        $collection = $this->createCollection('Test');
        $type = $this->createType('image');

        $media1En = $this->createMedia('media-1-en', $collection, 'image/jpeg', $type, 'en');
        $media2En = $this->createMedia('media-2-en', $collection, 'image/jpeg', $type, 'en');

        $media1De = $this->createMedia('media-1-de', $collection, 'image/jpeg', $type, 'de');
        $media2De = $this->createMedia('media-2-de', $collection, 'image/jpeg', $type, 'de');

        $this->em->persist($collection);
        $this->em->persist($media1En);
        $this->em->persist($media2En);
        $this->em->persist($media1De);
        $this->em->persist($media2De);
        $this->em->flush();

        // Set the client's default locale to German
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'de');

        // But explicitly request English locale via query parameter
        $this->client->jsonRequest(
            'GET',
            '/api/items?provider=media&locale=en&dataSource=' . $collection->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertIsString($responseContent);
        /** @var array{_embedded: array{items: array<array{title: string}>}} $result */
        $result = \json_decode($responseContent, true);

        $this->assertCount(2, $result['_embedded']['items']);

        $items = $result['_embedded']['items'];
        $titles = \array_map(function(array $item): string {
            return $item['title'];
        }, $items);

        $this->assertContains('media-1-en', $titles);
        $this->assertContains('media-2-en', $titles);

        $this->assertNotContains('media-1-de', $titles);
        $this->assertNotContains('media-2-de', $titles);
    }

    public function testGetItemsWithoutLocaleParameterUsesRequestLocale(): void
    {
        $collection = $this->createCollection('Test');
        $type = $this->createType('image');

        $media1En = $this->createMedia('media-1-en', $collection, 'image/jpeg', $type, 'en');
        $media2En = $this->createMedia('media-2-en', $collection, 'image/jpeg', $type, 'en');

        $this->em->persist($collection);
        $this->em->persist($media1En);
        $this->em->persist($media2En);
        $this->em->flush();

        // Make request WITHOUT locale parameter - should use request's default locale
        // The default locale is typically 'en' unless explicitly changed
        $this->client->jsonRequest(
            'GET',
            '/api/items?provider=media&dataSource=' . $collection->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertIsString($responseContent);
        /** @var array{_embedded: array{items: array<array{title: string}>}} $result */
        $result = \json_decode($responseContent, true);
        $this->assertCount(2, $result['_embedded']['items']);

        $items = $result['_embedded']['items'];
        $titles = \array_map(function(array $item): string {
            return $item['title'];
        }, $items);

        $this->assertContains('media-1-en', $titles);
        $this->assertContains('media-2-en', $titles);
    }

    private function createCollection(string $name): Collection
    {
        $collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName($name);
        $collectionType->setDescription('Default Collection Type');
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');

        $collection->setType($collectionType);
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        $this->em->persist($collection);
        $this->em->persist($collectionMeta);
        $this->em->persist($collectionType);

        return $collection;
    }
}
