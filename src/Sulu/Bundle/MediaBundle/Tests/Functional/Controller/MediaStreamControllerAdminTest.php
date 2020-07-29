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

use Prophecy\Argument;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaStreamControllerAdminTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    public function testDownloadActionCheckPermissionCalled()
    {
        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');

        // teardown needed to set a mocked service
        $this->tearDown();
        $this->client = $this->createAuthenticatedClient();

        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->checkPermission(Argument::that(function(SecurityCondition $securityCondition) use ($media) {
            $this->assertSame(MediaAdmin::SECURITY_CONTEXT, $securityCondition->getSecurityContext());
            $this->assertSame(null, $securityCondition->getLocale());
            $this->assertSame(Collection::class, $securityCondition->getObjectType());
            $this->assertSame($media->getCollection(), $securityCondition->getObjectId());

            return true;
        }), PermissionTypes::VIEW)
            ->shouldBeCalled();

        self::$container->set('sulu_security.security_checker', $securityChecker->reveal());

        $this->client->request('GET', $media->getAdminUrl());
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
    }

    private function createUploadedFile($path)
    {
        return new UploadedFile($path, \basename($path), \mime_content_type($path));
    }

    private function createCollection($title = 'Test')
    {
        $collection = $this->getCollectionManager()->save(
            [
                'title' => $title,
                'locale' => 'en',
                'type' => ['id' => 1],
            ],
            1
        );

        return $collection->getId();
    }

    private function createMedia($path, $title)
    {
        return $this->getMediaManager()->save(
            $this->createUploadedFile($path),
            [
                'title' => $title,
                'collection' => $this->createCollection(),
                'locale' => 'en',
            ],
            null
        );
    }

    private function createMediaVersion($id, $path, $title)
    {
        return $this->getMediaManager()->save(
            $this->createUploadedFile($path),
            [
                'id' => $id,
                'title' => $title,
                'collection' => $this->createCollection(),
                'locale' => 'en',
            ],
            null
        );
    }

    private function getMediaManager()
    {
        return $this->getContainer()->get('sulu_media.media_manager');
    }

    private function getCollectionManager()
    {
        return $this->getContainer()->get('sulu_media.collection_manager');
    }

    private function createMediaFile(string $name, string $fileName = 'photo.jpeg')
    {
        $filePath = \sys_get_temp_dir() . '/' . $name;
        \copy(__DIR__ . '/../../Fixtures/files/' . $fileName, $filePath);

        return $filePath;
    }
}
