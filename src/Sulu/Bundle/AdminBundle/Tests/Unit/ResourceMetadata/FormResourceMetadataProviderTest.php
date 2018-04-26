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
use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
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
     * @var FormXmlLoader
     */
    private $formXmlLoader;

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
     * @var string
     */
    private $contactXml2;

    /**
     * @var string
     */
    private $accountXml;

    public function setUp()
    {
        $this->cacheDir = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache';

        $this->contactXml = implode(DIRECTORY_SEPARATOR, [__DIR__, 'data', 'contact.xml']);
        $this->contactXml2 = implode(DIRECTORY_SEPARATOR, [__DIR__, 'data', 'contact2.xml']);
        $this->accountXml = implode(DIRECTORY_SEPARATOR, [__DIR__, 'data', 'account.xml']);

        $resourcesConfig = [
            'contacts' => [
                'datagrid' => 'ContactsClass',
                'form' => [
                    '@SuluContactBundle/Resources/forms/contact.xml',
                    '@SuluContactBundle/Resources/forms/contact2.xml',
                ],
                'endpoint' => '123',
            ],
            'accounts' => [
                'datagrid' => 'AccountsClass',
                'form' => [
                    '@SuluAccountBundle/Resources/forms/account.xml',
                ],
                'endpoint' => '123',
            ],
        ];

        $this->formXmlLoader = $this->prophesize(FormXmlLoader::class);

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getProperties()->willReturn(['property_array']);
        $formMetadata->getChildren()->willReturn(['children_array']);

        $formMetadata2 = $this->prophesize(FormMetadata::class);
        $formMetadata2->getProperties()->willReturn(['property_array2']);
        $formMetadata2->getChildren()->willReturn(['children_array2']);

        $formMetadataAccount = $this->prophesize(FormMetadata::class);
        $formMetadataAccount->getProperties()->willReturn(['property_array_account']);
        $formMetadataAccount->getChildren()->willReturn(['children_array_account']);

        $this->formXmlLoader->load($this->contactXml, Argument::any())->willReturn($formMetadata->reveal());
        $this->formXmlLoader->load($this->contactXml2, Argument::any())->willReturn($formMetadata2->reveal());
        $this->formXmlLoader->load($this->accountXml, Argument::any())->willReturn($formMetadataAccount->reveal());

        $this->resourceMetadataMapper = $this->prophesize(ResourceMetadataMapper::class);

        $this->resourceMetadataMapper->mapForm(['children_array', 'children_array2'], 'de')
            ->willReturn(new Form());
        $this->resourceMetadataMapper->mapForm(['children_array', 'children_array2'], 'en')
            ->willReturn(new Form());
        $this->resourceMetadataMapper->mapForm(['children_array_account'], 'de')
            ->willReturn(new Form());
        $this->resourceMetadataMapper->mapForm(['children_array_account'], 'en')
            ->willReturn(new Form());

        $this->resourceMetadataMapper->mapDatagrid('ContactsClass', 'de')->willReturn(new Datagrid());
        $this->resourceMetadataMapper->mapDatagrid('ContactsClass', 'en')->willReturn(new Datagrid());
        $this->resourceMetadataMapper->mapDatagrid('AccountsClass', 'de')->willReturn(new Datagrid());
        $this->resourceMetadataMapper->mapDatagrid('AccountsClass', 'en')->willReturn(new Datagrid());

        $this->resourceMetadataMapper->mapSchema(['property_array', 'property_array2'])->willReturn(new Schema());
        $this->resourceMetadataMapper->mapSchema(['property_array_account'])->willReturn(new Schema());

        $this->fileLocator = $this->prophesize(FileLocator::class);

        $this->fileLocator->locate('@SuluContactBundle/Resources/forms/contact.xml')
            ->willReturn($this->contactXml);

        $this->fileLocator->locate('@SuluContactBundle/Resources/forms/contact2.xml')
            ->willReturn($this->contactXml2);

        $this->fileLocator->locate('@SuluAccountBundle/Resources/forms/account.xml')
            ->willReturn($this->accountXml);

        $locales = [
            'de',
            'en',
        ];

        $this->formResourceMetadataProvider = new FormResourceMetadataProvider(
            $resourcesConfig,
            $this->formXmlLoader->reveal(),
            $this->resourceMetadataMapper->reveal(),
            $locales,
            $this->fileLocator->reveal(),
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
        // load should happen only one time for each given locale
        $this->formXmlLoader->load($this->contactXml, Argument::any())->shouldBeCalledTimes(1);
        $this->formXmlLoader->load($this->contactXml2, Argument::any())->shouldBeCalledTimes(1);
        $this->formXmlLoader->load($this->accountXml, Argument::any())->shouldBeCalledTimes(1);

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
            SchemaInterface::class,
            $resourceMetadata
        );
        $this->assertInstanceOf(
            FormInterface::class,
            $resourceMetadata
        );
        $this->assertInstanceOf(
            DatagridInterface::class,
            $resourceMetadata
        );
        $this->assertEquals($resourceMetadata->getForm(), new Form());
        $this->assertEquals($resourceMetadata->getDatagrid(), new Datagrid());
        $this->assertEquals($resourceMetadata->getSchema(), new Schema());

        // also the second one the data should be the same
        $resourceMetadata = $this->formResourceMetadataProvider->getResourceMetadata('contacts', 'de');
        $this->assertEquals($resourceMetadata->getForm(), new Form());
        $this->assertEquals($resourceMetadata->getDatagrid(), new Datagrid());
        $this->assertEquals($resourceMetadata->getSchema(), new Schema());
    }

    public function testGetUnknownResource()
    {
        $this->assertNull($this->formResourceMetadataProvider->getResourceMetadata('unknown_key', 'de'));
    }

    public function testGetAll()
    {
        // load should happen only one time for each given locale
        $this->formXmlLoader->load($this->contactXml, Argument::any())->shouldBeCalledTimes(1);
        $this->formXmlLoader->load($this->contactXml2, Argument::any())->shouldBeCalledTimes(1);
        $this->formXmlLoader->load($this->accountXml, Argument::any())->shouldBeCalledTimes(1);

        /** @var ResourceMetadata $resourceMetadata */
        $resourceMetadata1 = $this->formResourceMetadataProvider->getResourceMetadata('contacts', 'de');
        $this->assertEquals($resourceMetadata1->getForm(), new Form());
        $this->assertEquals($resourceMetadata1->getDatagrid(), new Datagrid());
        $this->assertEquals($resourceMetadata1->getSchema(), new Schema());
        $this->assertEquals($resourceMetadata1->getKey(), 'contacts');

        /** @var ResourceMetadata $resourceMetadata */
        $resourceMetadata2 = $this->formResourceMetadataProvider->getResourceMetadata('accounts', 'de');
        $this->assertEquals($resourceMetadata2->getForm(), new Form());
        $this->assertEquals($resourceMetadata2->getDatagrid(), new Datagrid());
        $this->assertEquals($resourceMetadata2->getSchema(), new Schema());
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
