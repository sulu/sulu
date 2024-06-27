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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class MediaFormatControllerTest extends SuluTestCase
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

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->setUpData();
    }

    private function setUpData(): void
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

        $this->em->persist($collection);
        $this->em->persist($iconsType);
        $this->em->persist($this->media);
        $this->em->persist($imageType);
        $this->em->persist($file);
        $this->em->persist($fileVersion);

        $this->em->flush();

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

        $this->em->persist($this->formatOptions[1]);
        $this->em->persist($this->formatOptions[0]);
        $this->em->flush();
    }

    public function testCGet(): void
    {
        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/media/%d/formats', $this->media->getId()),
            [
                'locale' => 'en',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotNull($response->{'big-squared'});
        $this->assertEquals(30, $response->{'big-squared'}->cropX);
        $this->assertEquals(31, $response->{'big-squared'}->cropY);
        $this->assertEquals(32, $response->{'big-squared'}->cropHeight);
        $this->assertEquals(33, $response->{'big-squared'}->cropWidth);

        $this->assertFalse(\property_exists($response, 'small-inset'));
        $this->assertFalse(\property_exists($response, 'one-side'));
    }

    public function testPatch(): void
    {
        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/media/%d/formats?locale=en', $this->media->getId())
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotNull($response->{'big-squared'});
        $this->assertEquals(30, $response->{'big-squared'}->cropX);
        $this->assertEquals(31, $response->{'big-squared'}->cropY);
        $this->assertEquals(32, $response->{'big-squared'}->cropHeight);
        $this->assertEquals(33, $response->{'big-squared'}->cropWidth);

        $this->assertFalse(\property_exists($response, 'small-inset'));
        $this->assertFalse(\property_exists($response, 'one-side'));

        $this->client->jsonRequest(
            'PATCH',
            \sprintf('/api/media/%d/formats', $this->media->getId()),
            [
                'big-squared' => [],
                'small-inset' => [
                    'cropX' => 50,
                    'cropY' => 51,
                    'cropHeight' => 52,
                    'cropWidth' => 53,
                ],
            ]
        );

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/media/%d/formats?locale=en', $this->media->getId())
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertFalse(\property_exists($response, 'big-squared'));

        $this->assertNotNull($response->{'small-inset'});
        $this->assertEquals(50, $response->{'small-inset'}->cropX);
        $this->assertEquals(51, $response->{'small-inset'}->cropY);
        $this->assertEquals(52, $response->{'small-inset'}->cropHeight);
        $this->assertEquals(53, $response->{'small-inset'}->cropWidth);

        $this->assertFalse(\property_exists($response, 'one-side'));
    }

    public function testPutWithFormatOptions(): void
    {
        $this->client->jsonRequest(
            'PUT',
            \sprintf('/api/media/%d/formats/small-inset?locale=de', $this->media->getId()),
            [
                'cropX' => 10,
                'cropY' => 15,
                'cropWidth' => 100,
                'cropHeight' => 100,
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(10, $response->cropX);
        $this->assertEquals(15, $response->cropY);
        $this->assertEquals(100, $response->cropWidth);
        $this->assertEquals(100, $response->cropHeight);

        // Test if the options have really been persisted

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/media/%d/formats?locale=de', $this->media->getId())
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotNull($response->{'small-inset'});
        $this->assertEquals(10, $response->{'small-inset'}->cropX);
        $this->assertEquals(15, $response->{'small-inset'}->cropY);
        $this->assertEquals(100, $response->{'small-inset'}->cropWidth);
        $this->assertEquals(100, $response->{'small-inset'}->cropHeight);
    }

    public function testPutWithEmptyFormatOptions(): void
    {
        $this->client->jsonRequest(
            'PUT',
            \sprintf('/api/media/%d/formats/big-squared?locale=en', $this->media->getId()),
            []
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals([], $response);

        // Test if the options have really been persisted

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/media/%d/formats?locale=en', $this->media->getId()),
            []
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertFalse(\property_exists($response, 'big-squared'));
    }

    public function testPutNotExistingFormat(): void
    {
        $this->client->jsonRequest(
            'PUT',
            \sprintf('/api/media/%d/formats/format-not-existing', $this->media->getId()),
            [
                'locale' => 'en',
                'options' => [],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPutNotExistingMedia(): void
    {
        $this->client->jsonRequest(
            'PUT',
            \sprintf('/api/media/%d/formats/format-not-existing', 12345),
            [
                'locale' => 'en',
                'options' => [],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }
}
