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

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AccountMediaControllerTest extends SuluTestCase
{
    /**
     * @var Account
     */
    protected $account;

    /**
     * @var Media
     */
    protected $media;

    /**
     * @var Media
     */
    protected $media2;

    public function setUp(): void
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
        $this->initOrm();
    }

    private function initOrm()
    {
        $this->account = new Account();
        $this->account->setName('Company');

        $this->setUpMediaEntities();

        $this->em->persist($this->account);

        $this->em->flush();
    }

    public function setUpMediaEntities()
    {
        $imageType = new MediaType();
        $imageType->setName('image');
        $imageType->setDescription('This is an image');

        $media = new Media();
        $media->setType($imageType);

        $this->media = $media;

        $media2 = new Media();
        $media2->setType($imageType);

        $this->media2 = $media2;

        $this->account->addMedia($media2);

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        $file2 = new File();
        $file2->setVersion(1);
        $file2->setMedia($media2);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('photo.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setStorageOptions(['segment' => '01', 'fileName' => 'photo.jpeg']);
        $file->addFileVersion($fileVersion);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('photo.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file2);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setStorageOptions(['segment' => '01', 'fileName' => 'photo.jpeg']);
        $file2->addFileVersion($fileVersion);

        $collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');

        $collection->setType($collectionType);

        $media->setCollection($collection);
        $media2->setCollection($collection);

        $this->em->persist($collection);
        $this->em->persist($collectionType);
        $this->em->persist($media);
        $this->em->persist($media2);
        $this->em->persist($collection);
        $this->em->persist($file);
        $this->em->persist($file2);
        $this->em->persist($imageType);
    }

    public function testGetList()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/accounts/' . $this->account->getId() . '/medias?flat=true');
        $response = \json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals($this->media2->getId(), $response->_embedded->account_media[0]->id);
        $this->assertObjectHasAttribute('thumbnails', $response->_embedded->account_media[0]);
        $this->assertObjectHasAttribute('sulu-100x100', $response->_embedded->account_media[0]->thumbnails);
        $this->assertTrue(\is_string($response->_embedded->account_media[0]->thumbnails->{'sulu-100x100'}));
    }

    public function testAccountMediaPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->medias));

        $client->request(
            'POST',
            '/api/accounts/' . $this->account->getId() . '/medias',
            [
                'mediaId' => $this->media->getId(),
            ]
        );

        $response = \json_decode($client->getResponse()->getContent());
        $this->assertNotNull($response->id);

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($client->getResponse()->getContent());
        $this->assertEquals(2, \count($response->medias));
        $this->assertIsInt($response->medias[0]);
        $this->assertIsInt($response->medias[1]);
    }

    public function testAccountMediaPostNotExistingMedia()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->medias));

        $client->request(
            'POST',
            '/api/accounts/' . $this->account->getId() . '/medias',
            [
                'mediaId' => 99,
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->medias));
    }

    public function testAccountMediaDelete()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/accounts/' . $this->account->getId() . '/medias/' . $this->media2->getId()
        );

        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->medias));
    }

    public function testAccountMediaDeleteNotExistingRelation()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/accounts/' . $this->account->getId() . '/medias/99'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->medias));
    }
}
