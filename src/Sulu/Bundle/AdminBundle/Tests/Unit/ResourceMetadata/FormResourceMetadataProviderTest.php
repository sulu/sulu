<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\ResourceMetadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\Datagrid;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\DatagridInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Endpoint\EndpointInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Form;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\FormInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\FormResourceMetadataProvider;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadata;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataMapper;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\SchemaInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;

class FormResourceMetadataProviderTest extends TestCase
{
    /**
     * @var FormResourceMetadataProvider
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
     * @var ResourceMetadataMapper
     */
    private $resourceMetadataMapper;

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
                'datagrid' => 'ContactsClass',
                'endpoint' => '123',
            ],
            'accounts' => [
                'datagrid' => 'AccountsClass',
                'endpoint' => '123',
            ],
        ];
        $this->resourceMetadataMapper = $this->prophesize(ResourceMetadataMapper::class);

        $this->resourceMetadataMapper->mapDatagrid('ContactsClass', 'de')->willReturn(new Datagrid());
        $this->resourceMetadataMapper->mapDatagrid('ContactsClass', 'en')->willReturn(new Datagrid());
        $this->resourceMetadataMapper->mapDatagrid('AccountsClass', 'de')->willReturn(new Datagrid());
        $this->resourceMetadataMapper->mapDatagrid('AccountsClass', 'en')->willReturn(new Datagrid());

        $locales = [
            'de',
            'en',
        ];

        $this->formResourceMetadataProvider = new FormResourceMetadataProvider(
            $resourcesConfig,
            $this->resourceMetadataMapper->reveal(),
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
        $this->assertInstanceOf(
            DatagridInterface::class,
            $resourceMetadata
        );
        $this->assertEquals($resourceMetadata->getDatagrid(), new Datagrid());

        // also the second one the data should be the same
        $resourceMetadata = $this->formResourceMetadataProvider->getResourceMetadata('contacts', 'de');
        $this->assertEquals($resourceMetadata->getDatagrid(), new Datagrid());
    }

    public function testGetUnknownResource()
    {
        $this->assertNull($this->formResourceMetadataProvider->getResourceMetadata('unknown_key', 'de'));
    }

    public function testGetAll()
    {
        /** @var ResourceMetadata $resourceMetadata */
        $resourceMetadata1 = $this->formResourceMetadataProvider->getResourceMetadata('contacts', 'de');
        $this->assertEquals($resourceMetadata1->getDatagrid(), new Datagrid());
        $this->assertEquals($resourceMetadata1->getKey(), 'contacts');

        /** @var ResourceMetadata $resourceMetadata */
        $resourceMetadata2 = $this->formResourceMetadataProvider->getResourceMetadata('accounts', 'de');
        $this->assertEquals($resourceMetadata2->getDatagrid(), new Datagrid());
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
