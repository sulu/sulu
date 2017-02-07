<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class FormatControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var FormatOptions[]
     */
    private $formatOptions;

    protected function setUp()
    {
        parent::setUp();
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->setUpData();
    }

    private function setUpData()
    {
        $collection = new Collection();

        $iconsType = new CollectionType();
        $iconsType->setName('icons');
        $collection->setType($iconsType);

        $this->media = new Media();
        $this->media->setCollection($collection);

        $imageType = new MediaType();
        $imageType->setName('image');
        $this->media->setType($imageType);

        $file = new File();
        $file->setVersion(1);
        $file->setMedia($this->media);
        $this->media->addFile($file);

        $fileVersion = new FileVersion();
        $fileVersion->setName('My file version');
        $fileVersion->setSize(10);
        $fileVersion->setVersion(1);
        $fileVersion->setFile($file);
        $file->addFileVersion($fileVersion);

        $this->formatOptions[] = new FormatOptions();
        $this->formatOptions[0]->setFormatKey('big-squared');
        $this->formatOptions[0]->setCropX(30);
        $this->formatOptions[0]->setCropY(31);
        $this->formatOptions[0]->setCropHeight(32);
        $this->formatOptions[0]->setCropWidth(33);
        $this->formatOptions[0]->setFileVersion($fileVersion);
        $fileVersion->addFormatOptions($this->formatOptions[0]);

        $this->formatOptions[] = new FormatOptions();
        $this->formatOptions[1]->setFormatKey('small-squared');
        $this->formatOptions[1]->setCropX(0);
        $this->formatOptions[1]->setCropY(1);
        $this->formatOptions[1]->setCropHeight(2);
        $this->formatOptions[1]->setCropWidth(3);
        $this->formatOptions[1]->setFileVersion($fileVersion);
        $fileVersion->addFormatOptions($this->formatOptions[1]);

        $this->em->persist($collection);
        $this->em->persist($iconsType);
        $this->em->persist($this->media);
        $this->em->persist($imageType);
        $this->em->persist($file);
        $this->em->persist($fileVersion);
        $this->em->persist($this->formatOptions[0]);
        $this->em->persist($this->formatOptions[1]);

        $this->em->flush();
    }

    public function testCGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/media/%d/formats', $this->media->getId()),
            [
                'locale' => 'en',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertNotNull($response->{'big-squared'});
        $this->assertEquals('big-squared', $response->{'big-squared'}->key);
        $this->assertEquals('My big squared format', $response->{'big-squared'}->title);
        $this->assertEquals(400, $response->{'big-squared'}->scale->x);
        $this->assertEquals(400, $response->{'big-squared'}->scale->y);
        $this->assertEquals('outbound', $response->{'big-squared'}->scale->mode);
        $this->assertEquals(30, $response->{'big-squared'}->options->cropX);
        $this->assertEquals(31, $response->{'big-squared'}->options->cropY);
        $this->assertEquals(32, $response->{'big-squared'}->options->cropHeight);
        $this->assertEquals(33, $response->{'big-squared'}->options->cropWidth);

        $this->assertNotNull($response->{'small-inset'});
        $this->assertEquals('small-inset', $response->{'small-inset'}->key);
        $this->assertEquals('My small inset format', $response->{'small-inset'}->title);
        $this->assertEquals(50, $response->{'small-inset'}->scale->x);
        $this->assertEquals(50, $response->{'small-inset'}->scale->y);
        $this->assertEquals('inset', $response->{'small-inset'}->scale->mode);
        $this->assertObjectNotHasAttribute('options', $response->{'small-inset'});

        $this->assertNotNull($response->{'one-side'});
        $this->assertEquals('one-side', $response->{'one-side'}->key);
        $this->assertEquals('My one sided format', $response->{'one-side'}->title);
        $this->assertEquals(200, $response->{'one-side'}->scale->x);
        $this->assertObjectNotHasAttribute('y', $response->{'one-side'}->scale);
        $this->assertEquals('outbound', $response->{'one-side'}->scale->mode);
        $this->assertObjectNotHasAttribute('options', $response->{'one-side'});
    }

    public function testCGetWithoutLocale()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/media/%d/formats', $this->media->getId())
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testPutWithFormatOptions()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            sprintf('/api/media/%d/formats/small-inset', $this->media->getId()),
            [
                'locale' => 'de',
                'options' => [
                    'cropX' => 10,
                    'cropY' => 15,
                    'cropWidth' => 100,
                    'cropHeight' => 100,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('small-inset', $response->key);
        $this->assertEquals('Mein kleines inset Format', $response->title);
        $this->assertEquals(50, $response->scale->x);
        $this->assertEquals(50, $response->scale->y);
        $this->assertEquals('inset', $response->scale->mode);
        $this->assertEquals(10, $response->options->cropX);
        $this->assertEquals(15, $response->options->cropY);
        $this->assertEquals(100, $response->options->cropWidth);
        $this->assertEquals(100, $response->options->cropHeight);

        // Test if the options have really been persisted

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/media/%d/formats', $this->media->getId()),
            [
                'locale' => 'de',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertNotNull($response->{'small-inset'});
        $this->assertEquals(10, $response->{'small-inset'}->options->cropX);
        $this->assertEquals(15, $response->{'small-inset'}->options->cropY);
        $this->assertEquals(100, $response->{'small-inset'}->options->cropWidth);
        $this->assertEquals(100, $response->{'small-inset'}->options->cropHeight);
    }

    public function testPutWithEmptyFormatOptions()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            sprintf('/api/media/%d/formats/big-squared', $this->media->getId()),
            [
                'locale' => 'en',
                'options' => [],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('big-squared', $response->key);
        $this->assertEquals('My big squared format', $response->title);
        $this->assertEquals(400, $response->scale->x);
        $this->assertEquals(400, $response->scale->y);
        $this->assertEquals('outbound', $response->scale->mode);
        $this->assertObjectNotHasAttribute('options', $response);

        // Test if the options have really been persisted
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/media/%d/formats', $this->media->getId()),
            [
                'locale' => 'en',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertNotNull($response->{'big-squared'});
        $this->assertObjectNotHasAttribute('options', $response->{'big-squared'});
    }

    public function testPutNotExistingFormat()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            sprintf('/api/media/%d/formats/format-not-existing', $this->media->getId()),
            [
                'locale' => 'en',
                'options' => [],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPutNotExistingMedia()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            sprintf('/api/media/%d/formats/format-not-existing', 12345),
            [
                'locale' => 'en',
                'options' => [],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPutWithoutLocale()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            sprintf('/api/media/%d/formats/big-squared', $this->media->getId()),
            [
                'options' => [],
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }
}
