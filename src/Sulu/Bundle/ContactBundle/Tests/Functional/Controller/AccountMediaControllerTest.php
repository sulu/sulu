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

use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AccountMediaControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

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

    /**
     * @var MediaType
     */
    protected $imageType;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var ObjectRepository<ActivityInterface>
     */
    private $activityRepository;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->activityRepository = $this->em->getRepository(ActivityInterface::class);
        $this->purgeDatabase();

        $this->account = new Account();
        $this->account->setName('Company');

        $this->imageType = new MediaType();
        $this->imageType->setName('image');

        $this->collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');

        $this->collection->setType($collectionType);

        $this->em->persist($this->account);
        $this->em->persist($this->collection);
        $this->em->persist($collectionType);
        $this->em->persist($this->imageType);
        $this->em->flush();
    }

    public function testGetList(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->account->addMedia($media1);

        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/accounts/' . $this->account->getId() . '/medias?flat=true');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals($media1->getId(), $response->_embedded->account_media[0]->id);
        $this->assertObjectHasAttribute('thumbnails', $response->_embedded->account_media[0]);
        $this->assertObjectHasAttribute('sulu-100x100', $response->_embedded->account_media[0]->thumbnails);
        $this->assertTrue(\is_string($response->_embedded->account_media[0]->thumbnails->{'sulu-100x100'}));
    }

    public function testAccountMediaPost(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->account->addMedia($media1);

        $media2 = $this->createMedia('photo.jpeg');

        $this->em->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->medias));

        $this->client->jsonRequest(
            'POST',
            '/api/accounts/' . $this->account->getId() . '/medias',
            [
                'mediaId' => $media2->getId(),
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertNotNull($response->id);

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'media_added']);
        $this->assertSame((string) $this->account->getId(), $activity->getResourceId());

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(2, \count($response->medias));
        $this->assertIsInt($response->medias[0]);
        $this->assertIsInt($response->medias[1]);
    }

    public function testAccountMediaPostNotExistingMedia(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->account->addMedia($media1);

        $this->em->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $response->medias);

        $this->client->jsonRequest(
            'POST',
            '/api/accounts/' . $this->account->getId() . '/medias',
            [
                'mediaId' => 99,
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $response->medias);
    }

    public function testAccountMediaDelete(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->account->addMedia($media1);

        $this->em->flush();

        $this->client->jsonRequest(
            'DELETE',
            '/api/accounts/' . $this->account->getId() . '/medias/' . $media1->getId()
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'media_removed']);
        $this->assertSame((string) $this->account->getId(), $activity->getResourceId());

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->medias));
    }

    public function testAccountMediaDeleteNotExistingRelation(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->account->addMedia($media1);

        $this->em->flush();

        $this->client->jsonRequest(
            'DELETE',
            '/api/accounts/' . $this->account->getId() . '/medias/99'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $this->account->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $response->medias);
    }

    private function createMedia(string $name)
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
        $fileVersion->setChanged(new \DateTime('1950-04-20'));
        $fileVersion->setCreated(new \DateTime('1950-04-20'));
        $file->addFileVersion($fileVersion);
        $this->em->persist($fileVersion);

        $media = new Media();
        $media->setType($this->imageType);
        $media->setCollection($this->collection);
        $media->addFile($file);
        $file->setMedia($media);
        $this->em->persist($media);
        $this->em->persist($file);

        return $media;
    }
}
