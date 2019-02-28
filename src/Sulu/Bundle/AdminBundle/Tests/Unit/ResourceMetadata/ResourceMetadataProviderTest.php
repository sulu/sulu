<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\ResourceMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\Schema\Schema;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Endpoint\EndpointInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadata;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;

class ResourceMetadataProviderTest extends TestCase
{
    /**
     * @var ResourceMetadataProvider
     */
    private $formResourceMetadataProvider;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var FileLocator
     */
    private $fileLocator;

    /**
     * @var string
     */
    private $contactXml;

    /**
     * @var Schema
     */
    private $contactSchema;

    /**
     * @var string
     */
    private $contactXml2;

    /**
     * @var Schema
     */
    private $contactSchema2;

    /**
     * @var string
     */
    private $accountXml;

    /**
     * @var Schema
     */
    private $accountSchema;

    public function setUp()
    {
        $this->cacheDir = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache';

        $resourcesConfig = [
            'contacts' => [
                'endpoint' => '123',
            ],
            'accounts' => [
                'endpoint' => '123',
            ],
        ];

        $locales = [
            'de',
            'en',
        ];

        $this->formResourceMetadataProvider = new ResourceMetadataProvider(
            $resourcesConfig,
            $locales,
            $this->cacheDir,
            false
        );
    }

    public function tearDown()
    {
        $this->cleanUp();
    }

    public function testIsOptional()
    {
        $this->assertEquals(false, $this->formResourceMetadataProvider->isOptional());
    }

    public function testWarmUp()
    {
        $this->formResourceMetadataProvider->warmUp($this->cacheDir);

        $this->assertFileExists($this->cacheDir . DIRECTORY_SEPARATOR . 'de_accounts');
        $this->assertFileExists($this->cacheDir . DIRECTORY_SEPARATOR . 'de_accounts.meta');
        $this->assertFileExists($this->cacheDir . DIRECTORY_SEPARATOR . 'de_contacts');
        $this->assertFileExists($this->cacheDir . DIRECTORY_SEPARATOR . 'de_contacts.meta');
        $this->assertFileExists($this->cacheDir . DIRECTORY_SEPARATOR . 'en_accounts');
        $this->assertFileExists($this->cacheDir . DIRECTORY_SEPARATOR . 'en_accounts.meta');
        $this->assertFileExists($this->cacheDir . DIRECTORY_SEPARATOR . 'en_contacts');
        $this->assertFileExists($this->cacheDir . DIRECTORY_SEPARATOR . 'en_contacts.meta');
    }

    public function testGetResource()
    {
        /** @var ResourceMetadata $resourceMetadata */
        $resourceMetadata = $this->formResourceMetadataProvider->getResourceMetadata('contacts', 'de');
        $this->assertInstanceOf(
            ResourceMetadataInterface::class,
            $resourceMetadata
        );
        $this->assertInstanceOf(
            EndpointInterface::class,
            $resourceMetadata
        );

        // also the second one the data should be the same
        $resourceMetadata = $this->formResourceMetadataProvider->getResourceMetadata('contacts', 'de');
    }

    public function testGetUnknownResource()
    {
        $this->assertNull($this->formResourceMetadataProvider->getResourceMetadata('unknown_key', 'de'));
    }

    public function testGetAll()
    {
        /** @var ResourceMetadata $resourceMetadata */
        $resourceMetadata1 = $this->formResourceMetadataProvider->getResourceMetadata('contacts', 'de');
        $this->assertEquals($resourceMetadata1->getKey(), 'contacts');

        /** @var ResourceMetadata $resourceMetadata */
        $resourceMetadata2 = $this->formResourceMetadataProvider->getResourceMetadata('accounts', 'de');
        $this->assertEquals($resourceMetadata2->getKey(), 'accounts');

        $this->assertCount(
            2,
            $this->formResourceMetadataProvider->getAllResourceMetadata('de')
        );

        $this->assertEquals(
            [
                $resourceMetadata1,
                $resourceMetadata2,
            ],
            $this->formResourceMetadataProvider->getAllResourceMetadata('de')
        );
    }

    private function cleanUp()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->cacheDir);
    }
}
