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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ContactMediaControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var MediaType
     */
    private $imageType;

    /**
     * @var Collection
     */
    protected $collection;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        $this->contact = new Contact();
        $this->contact->setFirstName('Max');
        $this->contact->setLastName('Mustermann');
        $this->contact->setFormOfAddress(1);
        $this->contact->setSalutation('Sehr geehrter Herr Dr Mustermann');

        $this->imageType = new MediaType();
        $this->imageType->setName('image');
        $this->imageType->setDescription('This is an image');

        $this->collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');

        $this->collection->setType($collectionType);

        $this->em->persist($this->contact);
        $this->em->persist($this->imageType);
        $this->em->persist($collectionType);
        $this->em->persist($this->collection);

        $this->em->flush();
    }

    public function testGetList(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->contact->addMedia($media1);

        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contacts/' . $this->contact->getId() . '/medias?flat=true');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals($media1->getId(), $response->_embedded->contact_media[0]->id);
        $this->assertObjectHasAttribute('thumbnails', $response->_embedded->contact_media[0]);
        $this->assertObjectHasAttribute('sulu-100x100', $response->_embedded->contact_media[0]->thumbnails);
        $this->assertTrue(\is_string($response->_embedded->contact_media[0]->thumbnails->{'sulu-100x100'}));
    }

    public function testContactMediaPost(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->contact->addMedia($media1);

        $media2 = $this->createMedia('photo.jpeg');

        $this->em->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->medias));

        $this->client->jsonRequest(
            'POST',
            '/api/contacts/' . $this->contact->getId() . '/medias',
            [
                'mediaId' => $media2->getId(),
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertNotNull($response->id);

        $this->client->jsonRequest(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(2, \count($response->medias));

        $this->assertIsInt($response->medias[0]);
        $this->assertIsInt($response->medias[1]);
    }

    public function testContactMediaPostNotExistingMedia(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->contact->addMedia($media1);

        $this->em->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->medias));

        $this->client->jsonRequest(
            'POST',
            '/api/contacts/' . $this->contact->getId() . '/medias',
            [
                'mediaId' => 99,
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->medias));
    }

    public function testContactMediaDelete(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->contact->addMedia($media1);

        $this->em->flush();

        $this->client->jsonRequest(
            'DELETE',
            '/api/contacts/' . $this->contact->getId() . '/medias/' . $media1->getId()
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->medias));
    }

    public function testContactMediaDeleteNotExistingRelation(): void
    {
        $media1 = $this->createMedia('photo.jpeg');
        $this->contact->addMedia($media1);

        $this->em->flush();

        $this->client->jsonRequest(
            'DELETE',
            '/api/contacts/' . $this->contact->getId() . '/medias/99'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/contacts/' . $this->contact->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, \count($response->medias));
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
